<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogFailedRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($response->getStatusCode() < 400) {
            return;
        }

        // The response body is already the Problem Details envelope assembled once in
        // bootstrap/app.php: no need to inspect the exception again, just read the same
        // fields the client received.
        $problem = json_decode($response->getContent() ?: '{}', true) ?? [];

        Log::channel('structured')->warning('request.failed', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'code' => $problem['code'] ?? null,
            'detail' => $problem['detail'] ?? null,
        ]);
    }
}
