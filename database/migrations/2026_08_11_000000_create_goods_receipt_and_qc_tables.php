<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->string('receipt_number');
            $table->foreignId('received_by')->constrained('users');
            $table->timestamp('received_at');
            $table->string('status')->default('PENDING_QC');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'receipt_number']);
            // v1 has no partial supplier fulfilment: a Purchase Order can only
            // ever have one normal Goods Receipt covering its full delivery.
            $table->unique('purchase_order_id');
            $table->index(['warehouse_id', 'status']);
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items');
            $table->integer('expected_quantity');
            $table->integer('received_quantity');
            $table->timestamps();

            $table->unique(['goods_receipt_id', 'purchase_order_item_id']);
            $table->index(['warehouse_id', 'goods_receipt_id']);
        });

        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            // One receipt item can only ever have one final QC decision.
            $table->foreignId('goods_receipt_item_id')->unique()->constrained('goods_receipt_items')->cascadeOnDelete();
            $table->string('result');
            $table->string('condition');
            $table->text('notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('evidence_mime')->nullable();
            $table->foreignId('inspected_by')->constrained('users');
            $table->timestamp('inspected_at');
            $table->foreignId('stock_transaction_id')->nullable()->constrained('stock_transactions');
            $table->timestamps();

            $table->index(['warehouse_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_inspections');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
