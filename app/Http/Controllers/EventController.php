<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Requests\UploadEventCoverImageRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        return EventResource::collection(Event::query()->paginate());
    }

    public function store(StoreEventRequest $request)
    {
        $event = Event::create($request->validated());

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
