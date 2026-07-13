<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
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
            // event_id, participant_id and seats are deliberately not accepted here: changing
            // any of them is a capacity decision that belongs to SeatsAvailability and the lock
            // in CreateBookingAction, not a field this request merely forgot to validate.
            'participant_name' => ['sometimes', 'string', 'max:255'],
            'participant_email' => ['sometimes', 'email'],
        ];
    }
}
