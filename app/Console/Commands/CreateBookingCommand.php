<?php

namespace App\Console\Commands;

use App\Domain\Booking\Actions\CreateBookingAction;
use App\Domain\Booking\DataTransferObjects\CreateBookingData;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class CreateBookingCommand extends Command
{
    protected $signature = 'bookings:create
        {event : The ID of the event}
        {participant_name : Full name of the participant}
        {participant_email : Email of the participant}
        {seats : Number of seats to book}';

    protected $description = 'Create a booking for an event from the command line';

    public function handle(CreateBookingAction $action): int
    {
        $event = Event::findOrFail($this->argument('event'));

        $data = new CreateBookingData(
            participantName: $this->argument('participant_name'),
            participantEmail: $this->argument('participant_email'),
            seats: (int) $this->argument('seats'),
        );

        try {
            $booking = $action($event, $data);
        } catch (ValidationException $e) {
            $this->error(collect($e->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }

        $this->info("Booking #{$booking->id} created for event #{$event->id}.");

        return self::SUCCESS;
    }
}
