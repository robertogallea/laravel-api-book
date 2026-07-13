<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListBookingsRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Event;

class BookingController extends Controller
{
    // A shallow nested resource: index/store are scoped to an event,
    // show/update/destroy address a booking directly.

    public function index(ListBookingsRequest $request, Event $event)
    {
        $bookings = $event->bookings()
            ->paginate($request->integer('per_page', 15));

        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request, Event $event)
    {
        $booking = $event->bookings()->create($request->validated());

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }

    public function show(Booking $booking)
    {
        return new BookingResource($booking);
    }

    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $booking->update($request->validated());

        return new BookingResource($booking);
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();

        return response()->json(status: 204);
    }
}
