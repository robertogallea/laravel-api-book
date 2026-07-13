<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Requests\UploadEventCoverImageRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
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

    public function index()
    {
        return EventResource::collection(Event::query()->paginate());
    }

    public function store(StoreEventRequest $request)
    {
        $event = Event::create([
            ...$request->validated(),
            'organizer_id' => $request->user()->id,
        ]);

        return (new EventResource($event))->response()->setStatusCode(201);
    }

    public function show(Event $event)
    {
        return new EventResource($event);
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
