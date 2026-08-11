<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cooperative_membership_id')->constrained('warehouse_memberships')->cascadeOnDelete();
            $table->foreignId('pickup_request_id')->constrained()->cascadeOnDelete();
            $table->string('return_number');
            $table->string('status')->default('SUBMITTED');
            $table->string('reason_code');
            $table->text('reason_notes')->nullable();
            $table->foreignId('submitted_by')->constrained('users');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamp('waiting_approval_at')->nullable();
            $table->unsignedInteger('version')->default(1);

            // Future-compatible nullable fields for Phase 5.2/5.3 (approval decision,
            // fault attribution, disposal, replacement) — not written to in Phase 5.1.
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->string('fault_attribution')->nullable();
            $table->string('fault_rule_version')->nullable();
            $table->timestamp('disposed_at')->nullable();
            $table->foreignId('replacement_pickup_request_id')->nullable()->constrained('pickup_requests');

            $table->timestamps();

            $table->unique(['warehouse_id', 'return_number']);
            $table->index(['warehouse_id', 'status']);
            $table->index(['cooperative_membership_id']);
            $table->index(['pickup_request_id']);
        });

        Schema::create('return_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_request_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('return_quantity');
            $table->boolean('barcode_verified')->default(false);
            $table->string('verified_barcode')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['pickup_request_item_id']);
        });

        Schema::create('return_evidence', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('purpose');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('path');
            $table->string('mime');
            $table->timestamps();

            $table->index(['return_request_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_evidence');
        Schema::dropIfExists('return_request_items');
        Schema::dropIfExists('return_requests');
    }
};
