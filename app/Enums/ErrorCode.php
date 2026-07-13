<?php

namespace App\Enums;

enum ErrorCode: string
{
    case ValidationFailed = 'validation_failed';
    case ResourceNotFound = 'resource_not_found';
    case ApiVersionRemoved = 'api_version_removed';
    case ServerError = 'server_error';

    public function title(): string
    {
        return match ($this) {
            self::ValidationFailed => 'The given data was invalid.',
            self::ResourceNotFound => 'The requested resource could not be found.',
            self::ApiVersionRemoved => 'This API version is no longer available.',
            self::ServerError => 'An unexpected error occurred.',
        };
    }
}
