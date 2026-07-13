<?php

namespace App\Enums;

enum ErrorCode: string
{
    case ValidationFailed = 'validation_failed';
    case ResourceNotFound = 'resource_not_found';
    case ApiVersionRemoved = 'api_version_removed';
    case Unauthenticated = 'unauthenticated';
    case Forbidden = 'forbidden';
    case TooManyRequests = 'too_many_requests';
    case IdempotencyKeyConflict = 'idempotency_key_conflict';
    case IdempotencyKeyInProgress = 'idempotency_key_in_progress';
    case ServerError = 'server_error';

    public function title(): string
    {
        return match ($this) {
            self::ValidationFailed => 'The given data was invalid.',
            self::ResourceNotFound => 'The requested resource could not be found.',
            self::ApiVersionRemoved => 'This API version is no longer available.',
            self::Unauthenticated => 'Authentication is required to access this resource.',
            self::Forbidden => 'You do not have permission to perform this action.',
            self::TooManyRequests => 'Too many requests. Try again later.',
            self::IdempotencyKeyConflict => 'This Idempotency-Key was already used with a different request payload.',
            self::IdempotencyKeyInProgress => 'A request with this Idempotency-Key is still being processed.',
            self::ServerError => 'An unexpected error occurred.',
        };
    }
}
