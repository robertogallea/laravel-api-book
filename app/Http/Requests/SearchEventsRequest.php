<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchEventsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'upcoming' => ['sometimes', 'boolean'],
            'available' => ['sometimes', 'boolean'],
            // Not "sometimes": that rule skips a field's other rules entirely when it is
            // absent, which would silently defeat required_with below, exactly the field it
            // is meant to require. "nullable" allows the same absence without that side effect.
            'from' => ['nullable', 'date', 'required_with:to'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'required_with:from'],
            'sort' => ['sometimes', 'in:most_booked'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
