<?php

namespace App\Http\Requests;

use App\Http\Controllers\LegacyEnrollmentController;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegacyEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Only a course the migration already knows how to map to an event is accepted:
            // anything else would have nowhere real to be created.
            'course_id' => ['required', 'integer', Rule::in(array_keys(LegacyEnrollmentController::COURSE_TO_EVENT))],
            'participant_name' => ['required', 'string', 'max:255'],
            'participant_email' => ['required', 'email'],
        ];
    }
}
