<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('router_webhook_logs', function (Blueprint $table) {
            // Make router_id nullable to allow logging of invalid token requests
            $table->unsignedBigInteger('router_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('router_webhook_logs', function (Blueprint $table) {
            // Revert to non-nullable (will fail if NULL values exist)
            $table->unsignedBigInteger('router_id')->nullable(false)->change();
        });
    }
};