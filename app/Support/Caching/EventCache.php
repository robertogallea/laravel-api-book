<?php

namespace App\Support\Caching;

use App\Models\Event;
use Closure;
use Illuminate\Support\Facades\Cache;

class EventCache
{
    // A safety net, not the primary invalidation mechanism: Event::saved()/deleted()
    // (AppServiceProvider::boot()) forget the entry the moment it changes. This bounds how
    // long a missed invalidation, one this code did not anticipate, can stay wrong for.
    private const TTL_MINUTES = 10;

    public function remember(int $eventId, Closure $callback): Event
    {
        // Attributes, not the Event instance itself: config/cache.php sets
        // serializable_classes to false, Laravel's secure-by-default posture against
        // deserializing arbitrary objects out of a cache store. A raw Eloquent model
        // survives the round trip on array/file stores (no real serialization involved)
        // but comes back as an unusable __PHP_Incomplete_Class on the database store this
        // book actually configures, a difference invisible until this cache is read back
        // for real, not just written to. newFromBuilder() re-hydrates a genuine Event from
        // the plain array either way, with the same casts a fresh query would apply.
        $attributes = Cache::remember(
            $this->key($eventId),
            now()->addMinutes(self::TTL_MINUTES),
            fn () => $callback()->getAttributes(),
        );

        return (new Event)->newFromBuilder($attributes);
    }

    public function forget(int $eventId): void
    {
        Cache::forget($this->key($eventId));
    }

    private function key(int $eventId): string
    {
        return "events:{$eventId}";
    }
}
