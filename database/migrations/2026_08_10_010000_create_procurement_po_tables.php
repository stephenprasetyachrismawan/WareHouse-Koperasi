<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('group_number');
            $table->foreignId('created_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'group_number']);
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->string('po_number');
            $table->string('status')->default('DRAFT');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('sent_by')->nullable()->constrained('users');
            $table->timestamp('sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('purchase_request_group_id')->nullable()->constrained('purchase_request_groups')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['warehouse_id', 'po_number']);
            $table->index(['warehouse_id', 'status']);
            $table->index(['warehouse_id', 'supplier_id']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('ordered_quantity');
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_allocations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('purchase_request_item_id')->constrained('purchase_request_items')->cascadeOnDelete();
            $table->foreignId('purchase_request_group_id')->nullable()->constrained('purchase_request_groups')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->cascadeOnDelete();
            $table->integer('allocated_quantity');
            $table->foreignId('allocated_by')->constrained('users');
            $table->timestamps();

            $table->index(['warehouse_id', 'purchase_request_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_allocations');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_request_groups');
    }
};
