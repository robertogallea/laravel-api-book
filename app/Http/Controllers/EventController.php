<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchEventsRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Requests\UploadEventCoverImageRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Support\Caching\EventCache;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;

class EventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // location itself is not going away: it is being superseded by a more structured
            // address field the platform will introduce later. Every action whose response can
            // include it carries the deprecation signal, unlike destroy, which does not.
            new Middleware(
                'deprecated:2026-10-04,'
                    .'https://eventhub.test/deprecations/event-location',
                only: ['index', 'show', 'store', 'update', 'uploadCoverImage'],
            ),
            // Browsing the catalog stays open to anyone; only an authenticated organizer can
            // create or manage an event.
            new Middleware('auth:sanctum', except: ['index', 'show']),
            // Consulting the catalog is public and comparatively cheap, so it gets its own,
            // far more permissive, named limiter than creating a booking does.
            new Middleware('throttle:events', only: ['index', 'show']),
            // Being authenticated is not enough: only an organizer (or an admin) may create an
            // event, and only the organizer who owns a given event (or an admin) may change or
            // remove it. EventPolicy checks the role first, the ownership second.
            new Middleware('can:create,'.Event::class, only: ['store']),
            new Middleware('can:update,event', only: ['update', 'uploadCoverImage']),
            new Middleware('can:delete,event', only: ['destroy']),
        ];
    }

    public function index(SearchEventsRequest $request)
    {
        $events = Event::query()
            ->when($request->boolean('upcoming'), fn ($query) => $query->upcoming())
            ->when($request->boolean('available'), fn ($query) => $query->available())
            ->when($request->filled('from'), fn ($query) => $query->startingBetween(
                Carbon::parse($request->query('from')),
                Carbon::parse($request->query('to')),
            ))
            // mostBooked() opts out of Event::$with on purpose: it only needs a count to sort
            // by, not every booking row. This endpoint's EventResource disagrees, it needs the
            // full collection for every event regardless of sort, seats_available among them:
            // with('bookings') restores it after the scope removed it.
            ->when($request->query('sort') === 'most_booked', fn ($query) => $query->mostBooked()->with('bookings'))
            ->paginate($request->integer('per_page', 15));

        return EventResource::collection($events);
    }

    public function store(StoreEventRequest $request)
    {
        $event = Event::create([
            ...$request->validated(),
            'organizer_id' => $request->user()->id,
        ]);

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    public function show(int $event, EventCache $cache)
    {
        // int, not Event: an implicitly bound $event would be fetched (with every booking
        // row, Event::$with) before this method even runs, on every request, cache hit or
        // not. findOrFail() below only runs on a miss.
        $cached = $cache->remember($event, fn () => Event::without('bookings')->findOrFail($event));

        return new EventResource($cached);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->update($request->validated());

        return new EventResource($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json(status: 204);
    }

    public function uploadCoverImage(UploadEventCoverImageRequest $request, Event $event)
    {
        $path = $request->file('cover_image')->store('event-covers', 'public');

        $event->update(['cover_image_path' => $path]);

        return new EventResource($event);
    }
}
