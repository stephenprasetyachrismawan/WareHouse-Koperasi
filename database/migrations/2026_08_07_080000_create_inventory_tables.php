<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit');
            $table->integer('minimum_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
            $table->index(['warehouse_id', 'is_active']);
        });

        Schema::create('item_barcodes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('barcode');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['warehouse_id', 'barcode']);
            $table->index('item_id');
        });

        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'is_active']);
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['warehouse_id', 'item_id']);
        });

        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('movement_type');
            $table->integer('signed_quantity');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('performed_by')->constrained('users');
            $table->string('idempotency_key');
            $table->timestamp('occurred_at');
            $table->foreignId('reversal_of_id')->nullable()->constrained('stock_transactions');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->unique(['warehouse_id', 'idempotency_key']);
            $table->index(['warehouse_id', 'item_id', 'created_at']);
            $table->index(['warehouse_id', 'movement_type']);
            $table->index(['warehouse_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('item_barcodes');
        Schema::dropIfExists('items');
    }
};
