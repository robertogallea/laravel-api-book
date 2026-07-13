<?php

namespace App\Support\OpenApi;

use App\Enums\ErrorCode;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

/**
 * Scramble infers request and success-response schemas straight from Form Requests and API
 * Resources, but it has no way to know that every error EventHub returns is reshaped into a
 * single Problem Details envelope by the exception handler in bootstrap/app.php: left alone,
 * it documents Laravel's generic {"message": "...", "errors": {...}} shape instead. This
 * rewrites the generated error responses once, centrally, the same way the exception handler
 * already does for the real API, so every documented endpoint inherits it automatically.
 */
class ProblemDetailsResponses
{
    public function __invoke(OpenApi $openApi): void
    {
        $openApi->components->responses['ValidationException'] = $this->response(422, ErrorCode::ValidationFailed, withFieldErrors: true);
        $openApi->components->responses['AuthenticationException'] = $this->response(401, ErrorCode::Unauthenticated);
        $openApi->components->responses['ModelNotFoundException'] = $this->response(404, ErrorCode::ResourceNotFound);
    }

    private function response(int $status, ErrorCode $code, bool $withFieldErrors = false): Response
    {
        $schema = (new ObjectType)
            ->addProperty('type', (new StringType)->setDescription('A stable URI identifying this problem type.'))
            ->addProperty('title', new StringType)
            ->addProperty('status', new IntegerType)
            ->addProperty('detail', new StringType)
            ->addProperty('code', (new StringType)->setDescription('Stable, machine-readable error code (see ErrorCode).'))
            ->setRequired(['type', 'title', 'status', 'detail', 'code']);

        if ($withFieldErrors) {
            $schema->addProperty(
                'errors',
                (new ObjectType)
                    ->setDescription('Validation errors, keyed by field name.')
                    ->additionalProperties((new ArrayType)->setItems(new StringType)),
            );
        }

        return Response::make($status)
            ->setDescription($code->title())
            ->setContent('application/problem+json', Schema::fromType($schema));
    }
}
