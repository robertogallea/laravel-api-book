<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $with = ['bookings'];

    protected $fillable = [
        'organizer_id',
        'title',
        'description',
        'location',
        'starts_at',
        'capacity',
        'cover_image_path',
        'sold_out_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'capacity' => 'integer',
            'sold_out_at' => 'datetime',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now());
    }

    public function scopeStartingBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween('starts_at', [$from, $to]);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('sold_out_at');
    }

    // without('bookings') first: $with would otherwise still eager load every booking row for
    // every event in the result, on top of the count computed here, the same over-fetching the
    // previous section opened with, just for a different reason (an aggregate, not a relation
    // read one row at a time). withCount() answers "how many" with a single subquery per event,
    // computed by the database, without ever pulling a single booking row into PHP.
    public function scopeMostBooked(Builder $query): Builder
    {
        return $query->without('bookings')->withCount('bookings')->orderByDesc('bookings_count');
    }
}
