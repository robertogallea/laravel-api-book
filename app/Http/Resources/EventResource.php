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
        $canSeeOperationalData = $request->user()?->can('update', $this->resource);

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
            // Visible only to the event's organizer or an admin: EventPolicy::update already
            // draws this same line for who may change the event, so it is reused here for who
            // may see who owns it.
            'organizer_id' => $this->when($canSeeOperationalData, $this->organizer_id),
            // Visible only to the event's organizer or an admin: how many seats have already
            // been booked is operational data, not part of the public catalog.
            'bookings_count' => $this->when($canSeeOperationalData, fn () => $this->relationLoaded('bookings')
                ? $this->bookings->count()
                : $this->bookings()->count()),
        ];
    }
}
