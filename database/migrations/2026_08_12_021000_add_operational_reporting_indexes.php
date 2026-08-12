<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->index(['warehouse_id', 'status', 'created_at'], 'purchase_requests_reporting_status_created_idx');
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->index(['warehouse_id', 'status', 'created_at'], 'purchase_orders_reporting_status_created_idx');
        });

        Schema::table('pickup_requests', function (Blueprint $table): void {
            $table->index(['warehouse_id', 'status', 'submitted_at'], 'pickup_requests_reporting_status_submitted_idx');
        });

        Schema::table('return_requests', function (Blueprint $table): void {
            $table->index(['warehouse_id', 'status', 'submitted_at'], 'return_requests_reporting_status_submitted_idx');
        });
    }

    public function down(): void
    {
        Schema::table('return_requests', fn (Blueprint $table) => $table->dropIndex('return_requests_reporting_status_submitted_idx'));
        Schema::table('pickup_requests', fn (Blueprint $table) => $table->dropIndex('pickup_requests_reporting_status_submitted_idx'));
        Schema::table('purchase_orders', fn (Blueprint $table) => $table->dropIndex('purchase_orders_reporting_status_created_idx'));
        Schema::table('purchase_requests', fn (Blueprint $table) => $table->dropIndex('purchase_requests_reporting_status_created_idx'));
    }
};
