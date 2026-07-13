<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $bookedSeats = $this->relationLoaded('bookings')
            ? $this->bookings->sum('seats')
            : $this->bookings()->sum('seats');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'starts_at' => $this->starts_at->toIso8601String(),
            'capacity' => $this->capacity,
            'seats_available' => $this->capacity - $bookedSeats,
            'cover_image_url' => $this->cover_image_path
                ? Storage::disk('public')->url($this->cover_image_path)
                : null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
