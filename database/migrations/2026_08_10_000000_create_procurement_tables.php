<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('request_number');
            $table->string('source');
            $table->string('urgency')->default('NORMAL');
            $table->string('status')->default('DRAFT');
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->foreignId('pickup_request_id')->nullable()->constrained('pickup_requests')->cascadeOnDelete();
            $table->boolean('is_duplicate_override')->default(false);
            $table->text('duplicate_override_reason')->nullable();
            $table->foreignId('duplicate_overridden_by')->nullable()->constrained('users');
            $table->timestamp('duplicate_overridden_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'request_number']);
            $table->index(['warehouse_id', 'status']);
            $table->index(['warehouse_id', 'source']);
            $table->index(['warehouse_id', 'created_by']);
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('requested_quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users');
            $table->text('reason');
            $table->string('status')->default('PENDING');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->text('decision_reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_requests');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
    }
};
