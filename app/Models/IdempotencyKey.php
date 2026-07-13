<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = [
        'key',
        'request_fingerprint',
        'response_status',
        'response_headers',
        'response_body',
    ];

    protected $casts = [
        'response_headers' => 'array',
    ];
}
