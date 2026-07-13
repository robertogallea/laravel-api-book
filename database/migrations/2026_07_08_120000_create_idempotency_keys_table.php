<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('request_fingerprint');
            // Nullable: a row exists as soon as a request claims the key, before it has
            // finished processing. These three columns stay empty until the request that
            // owns the key completes and fills them in.
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
