<?php

use App\Enums\ErrorCode;
use App\Exceptions\ApiVersionRemovedException;
use App\Http\Middleware\DeprecatedApiVersion;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Responses\ProblemDetails;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Http\Middleware\CheckToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [ForceJsonResponse::class]);
        $middleware->alias([
            'deprecated' => DeprecatedApiVersion::class,
            'client' => CheckToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // EventHub has no browser-facing routes: every response is JSON.
        $exceptions->shouldRenderJsonWhen(fn () => true);

        // Every error response (validation, not found, unexpected server errors, ...) is
        // rewritten here, once, into the same Problem Details envelope. Later chapters
        // (security, idempotency, webhooks) add their own ErrorCode cases and match arms
        // instead of inventing an alternative error format.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if ($status < 400) {
                return $response;
            }

            $code = match (true) {
                $e instanceof ValidationException => ErrorCode::ValidationFailed,
                $e instanceof ApiVersionRemovedException => ErrorCode::ApiVersionRemoved,
                $e instanceof AuthenticationException => ErrorCode::Unauthenticated,
                $e instanceof AccessDeniedHttpException => ErrorCode::Forbidden,
                $e instanceof NotFoundHttpException => ErrorCode::ResourceNotFound,
                $e instanceof ThrottleRequestsException => ErrorCode::TooManyRequests,
                default => ErrorCode::ServerError,
            };

            $original = json_decode($response->getContent(), true) ?? [];

            // Only validation messages are specific and safe to expose as-is (e.g. "The
            // title field is required."). Other exceptions (a missing Eloquent model, an
            // unexpected server error, ...) can carry internal details in their message
            // that should not leak into the API response, so they fall back to the
            // catalog's stable, generic title instead.
            $detail = $code === ErrorCode::ValidationFailed
                ? ($original['message'] ?? $code->title())
                : $code->title();

            $problemResponse = (new ProblemDetails(
                code: $code,
                status: $status,
                detail: $detail,
                extra: array_filter(['errors' => $original['errors'] ?? null]),
            ))->toResponse($request);

            // A throttled request carries Retry-After and X-RateLimit-* headers on $response,
            // set by Laravel's throttle middleware: without them a client has no standard way
            // to know when it is safe to retry. Content-Type is left alone, since ProblemDetails
            // already set it to application/problem+json above.
            foreach ($response->headers->all() as $name => $values) {
                if (! $problemResponse->headers->has($name)) {
                    $problemResponse->headers->set($name, $values);
                }
            }

            return $problemResponse;
        });
    })->create();
