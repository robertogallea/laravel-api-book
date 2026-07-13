<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeprecatedApiVersion
{
    /**
     * Mark the response of a deprecated endpoint (or of an endpoint whose response includes a
     * deprecated field) with the standard deprecation headers.
     *
     * @param  string  $sunset  the date (Y-m-d) after which the endpoint or field stops working
     * @param  string|null  $link  a stable URI identifying what specifically is deprecated, sent
     *                             as a Link header with rel="deprecation" (RFC 8594). Needed
     *                             because Deprecation/Sunset apply to the whole HTTP response:
     *                             without it, there is no way to tell "this endpoint is going
     *                             away" apart from "one field in this endpoint's response is".
     */
    public function handle(Request $request, Closure $next, string $sunset, ?string $link = null): Response
    {
        $response = $next($request);

        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', Carbon::parse($sunset)->toRfc7231String());

        if ($link !== null) {
            $response->headers->set('Link', "<{$link}>; rel=\"deprecation\"");
        }

        return $response;
    }
}
