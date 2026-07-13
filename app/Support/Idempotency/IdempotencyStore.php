<?php

namespace App\Support\Idempotency;

use App\Models\IdempotencyKey;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyStore
{
    public function fingerprint(Request $request): string
    {
        // $request->all() rather than getContent(): both a JSON body and a form-encoded one
        // (as sent by the test suite's post() helper) normalize to the same input array, so
        // the fingerprint reflects what the application actually sees, not how it was encoded
        // on the wire. Sorted by key so the same data in a different order still matches.
        $input = $request->all();
        ksort($input);

        return hash('sha256', $request->method().'|'.$request->getPathInfo().'|'.json_encode($input));
    }

    public function claim(string $key, string $fingerprint): IdempotencyKey
    {
        try {
            return IdempotencyKey::create([
                'key' => $key,
                'request_fingerprint' => $fingerprint,
            ]);
        } catch (QueryException $e) {
            if (! $this->isUniqueKeyViolation($e)) {
                throw $e;
            }

            // The unique constraint on `key`, not this code, is what just made this safe:
            // another request (possibly still in flight) already claimed it first.
            return IdempotencyKey::where('key', $key)->firstOrFail();
        }
    }

    public function complete(IdempotencyKey $record, Response $response): void
    {
        $record->update([
            'response_status' => $response->getStatusCode(),
            'response_headers' => $response->headers->all(),
            'response_body' => $response->getContent(),
        ]);
    }

    public function toResponse(IdempotencyKey $record): Response
    {
        return response($record->response_body, $record->response_status, $record->response_headers);
    }

    private function isUniqueKeyViolation(QueryException $e): bool
    {
        return in_array($e->getCode(), ['23000', '23505'], true);
    }
}
