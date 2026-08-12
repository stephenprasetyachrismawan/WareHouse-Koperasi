<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Null warehouse_id/notification_type act as a wildcard for
            // "all warehouses" / "all types" — rows only ever exist for an
            // explicit user override, never pre-created per combination.
            $table->foreignId('warehouse_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('notification_type')->nullable();

            // Only 'push' is user-configurable today (Inbox is mandatory,
            // realtime is automatic) — kept as a string for future channels.
            $table->string('channel');
            $table->boolean('enabled')->default(true);

            $table->timestamps();

            $table->unique(['user_id', 'warehouse_id', 'notification_type', 'channel'], 'notification_preferences_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
