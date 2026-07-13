<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'participant_name' => $this->participant_name,
            'participant_email' => $this->participant_email,
            'seats' => $this->seats,
            'created_at' => $this->created_at->toIso8601String(),
            // Null for a booking created without an authenticated user (e.g. via
            // CreateBookingCommand, Capitolo 2): a guest booking has no platform account to
            // describe, only the participant_name/participant_email snapshot above.
            'participant' => $this->participant ? [
                'id' => $this->participant->id,
                'role' => $this->participant->role->value,
            ] : null,
        ];
    }
}
