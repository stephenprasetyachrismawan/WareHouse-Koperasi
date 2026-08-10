<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE warehouse_memberships ALTER COLUMN permissions TYPE jsonb USING permissions::jsonb');
        } else {
            Schema::table('warehouse_memberships', function (Blueprint $table) {
                $table->json('permissions')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE warehouse_memberships ALTER COLUMN permissions TYPE json USING permissions::jsonb');
        } else {
            Schema::table('warehouse_memberships', function (Blueprint $table) {
                $table->json('permissions')->nullable()->change();
            });
        }
    }
};
