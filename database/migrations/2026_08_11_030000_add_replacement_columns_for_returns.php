<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->string('source')->default('KOPERASI')->after('user_id');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('return_request_id')->nullable()->after('pickup_request_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('return_request_id');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
