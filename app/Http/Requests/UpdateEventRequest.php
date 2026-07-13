<?php

namespace App\Http\Requests;

use App\Domain\Event\ValueObjects\SeatsAvailability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is not part of the API yet: it is introduced in the security chapter.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // organizer_id and sold_out_at are deliberately not accepted here: ownership is not
            // reassignable through a generic update, and sold_out_at is only ever set or cleared
            // by CreateBookingAction/CancelBookingAction, not by direct edit. cover_image_path
            // has its own endpoint (uploadCoverImage).
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'date'],
            'capacity' => [
                'sometimes', 'integer', 'min:1',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $booked = SeatsAvailability::forEvent($this->route('event'))->booked;

                    if ($value < $booked) {
                        $fail("The {$attribute} field must be at least {$booked}, the number of seats already booked.");
                    }
                },
            ],
        ];
    }
}
