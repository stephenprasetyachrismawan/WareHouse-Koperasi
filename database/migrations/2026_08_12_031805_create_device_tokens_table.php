<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('provider');
            $table->string('platform')->default('web');

            // Encrypted at rest via the model's Eloquent cast. Never unique
            // or looked up directly: Laravel's encryption is randomized per
            // value, so token_fingerprint (a deterministic hash) is the only
            // safe way to detect "this is the same device token again".
            $table->text('encrypted_token');
            $table->string('token_fingerprint', 64)->unique();

            $table->string('device_name')->nullable();
            $table->string('browser')->nullable();
            $table->string('user_agent_summary')->nullable();

            $table->timestamp('consented_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
