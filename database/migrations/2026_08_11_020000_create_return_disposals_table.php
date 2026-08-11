<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_disposals', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('return_request_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->integer('quantity');
            $table->foreignId('disposed_by')->constrained('users');
            $table->timestamp('disposed_at');
            $table->timestamps();

            // One disposal per approved Return Item, enforced at the DB level
            // as defense-in-depth against duplicate/replayed approval retries.
            $table->unique('return_request_item_id');
            $table->index(['warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_disposals');
    }
};
