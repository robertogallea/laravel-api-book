<?php

namespace App\Http\Controllers;

use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Http\Requests\StoreLegacyEnrollmentRequest;
use App\Http\Resources\BookingResource;
use App\Models\Event;

class LegacyEnrollmentController extends Controller
{
    // Illustrative and deliberately provisional, same caveat as
    // legacy/migrated_courses.php: a real migration needs a real, maintained
    // mapping for more than one course. Duplicated here, not shared, because
    // this lives in a separate Composer project from the legacy gestionale.
    public const COURSE_TO_EVENT = [3 => 3];

    public function store(StoreLegacyEnrollmentRequest $request, CreateBookingAction $action)
    {
        $event = Event::findOrFail(self::COURSE_TO_EVENT[$request->validated('course_id')]);

        $booking = $action($event, CreateBookingData::fromArray([
            ...$request->validated(),
            // Every enrollment arriving through this path is, by construction, exactly the
            // same guest booking already legitimate since Chapter 2: the legacy gestionale
            // never had seats-per-enrollment or user accounts to carry over.
            'seats' => 1,
            'participant_id' => null,
        ]));

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }
}
