<?php

namespace App\Http\Responses;

use App\Enums\ErrorCode;
use Illuminate\Contracts\Support\Responsable;

class ProblemDetails implements Responsable
{
    public function __construct(
        protected ErrorCode $code,
        protected int $status,
        protected string $detail,
        protected array $extra = [],
    ) {}

    public function toResponse($request)
    {
        return response()->json([
            'type' => "https://eventhub.test/errors/{$this->code->value}",
            'title' => $this->code->title(),
            'status' => $this->status,
            'detail' => $this->detail,
            'code' => $this->code->value,
            ...$this->extra,
        ], $this->status)->header('Content-Type', 'application/problem+json');
    }
}
