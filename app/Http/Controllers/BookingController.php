<?php

namespace App\Http\Controllers;

use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Http\Requests\ListBookingsRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class BookingController extends Controller implements HasMiddleware
{
    // A shallow nested resource: index/store are scoped to an event,
    // show/update/destroy address a booking directly.

    public static function middleware(): array
    {
        return [
            // Every booking action, including reading one, requires a logged-in user: unlike
            // an event, a booking is personal data tied to whoever made it.
            new Middleware('auth:sanctum'),
            // Creating a booking writes data and consumes an event's capacity, unlike reading
            // one: it gets its own, stricter, named limiter (AppServiceProvider::boot()) instead
            // of sharing whatever default throttling other actions might get later.
            new Middleware('throttle:bookings', only: ['store']),
            // Listing an event's bookings is the event owner's business, not any single
            // booking's: the ability lives on EventPolicy. Reading, changing or cancelling one
            // specific booking is instead a question of who that booking belongs to.
            new Middleware('can:viewBookings,event', only: ['index']),
            new Middleware('can:view,booking', only: ['show']),
            new Middleware('can:update,booking', only: ['update']),
            new Middleware('can:delete,booking', only: ['destroy']),
        ];
    }

    public function index(ListBookingsRequest $request, Event $event)
    {
        $bookings = $event->bookings()
            ->with('participant')
            ->paginate($request->integer('per_page', 15));

        return BookingResource::collection($bookings);
    }

    public function store(StoreBookingRequest $request, Event $event, CreateBookingAction $action)
    {
        $booking = $action($event, CreateBookingData::fromArray([
            ...$request->validated(),
            // The owner of a booking is always the authenticated caller, never a client-supplied
            // field: accepting it from validated() input would let anyone book on someone else's
            // behalf just by naming them.
            'participant_id' => $request->user()->id,
        ]));

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
