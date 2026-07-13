<?php

namespace App\Http\Middleware;

use App\Exceptions\IdempotencyKeyConflictException;
use App\Exceptions\IdempotencyKeyInProgressException;
use App\Support\Idempotency\IdempotencyStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotentRequest
{
    public function __construct(
        private readonly IdempotencyStore $store,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        // A client that does not send the header is not asking for this protection: the
        // request is processed exactly as it always has been, with no dedup at all.
        if (! $key) {
            return $next($request);
        }

        $fingerprint = $this->store->fingerprint($request);
        $record = $this->store->claim($key, $fingerprint);

        // wasRecentlyCreated is true only for the request that just won the unique
        // constraint race on `key`: every other request, whether it arrives a millisecond
        // later or after the first has long since finished, falls into the branch below.
        if (! $record->wasRecentlyCreated) {
            if ($record->request_fingerprint !== $fingerprint) {
                throw new IdempotencyKeyConflictException;
            }

            if ($record->response_status === null) {
                throw new IdempotencyKeyInProgressException;
            }

            return $this->store->toResponse($record);
        }

        $response = $next($request);

        $this->store->complete($record, $response);

        return $response;
    }
}
