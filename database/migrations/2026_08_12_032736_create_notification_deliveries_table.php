<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('inbox_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_token_id')->constrained()->cascadeOnDelete();

            $table->string('channel')->default('push');
            $table->string('status');
            $table->unsignedInteger('attempts')->default(0);

            $table->string('provider_message_id')->nullable();

            // A short, sanitized classification only (e.g. "unregistered",
            // "unavailable") — never the raw provider response body, which
            // may contain sensitive request/account details.
            $table->string('last_error_code')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // The idempotency key: retries of the same notification/device/
            // channel combination always resolve to this one row.
            $table->unique(['inbox_notification_id', 'device_token_id', 'channel'], 'notification_deliveries_idempotency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
