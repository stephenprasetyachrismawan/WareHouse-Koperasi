<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbox_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();

            // Nullable: a genuine platform/super-admin notification has no
            // single tenant warehouse — never fake warehouse_id = 0 for that.
            $table->foreignId('warehouse_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->string('title');
            $table->text('message');

            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Server-resolved path only (e.g. route('procurement.show', $uuid)
            // computed inside the listener) — never a client-supplied URL.
            $table->string('action_route')->nullable();

            // Deterministic dedup identity, e.g.
            // "purchase_request:{id}:approval_required:{membership_id}".
            // Not a random UUID: replaying the same domain event must resolve
            // to the same key so retries can never duplicate a row.
            $table->string('correlation_key');

            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['recipient_id', 'correlation_key']);
            $table->index(['recipient_id', 'warehouse_id', 'read_at']);
            $table->index(['recipient_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbox_notifications');
    }
};
