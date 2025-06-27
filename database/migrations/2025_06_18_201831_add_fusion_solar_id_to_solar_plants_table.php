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
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->string('fusion_solar_id')->nullable()->after('id');
            $table->timestamp('last_sync_at')->nullable()->after('notes');
            
            $table->index('fusion_solar_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->dropIndex(['fusion_solar_id']);
            $table->dropColumn(['fusion_solar_id', 'last_sync_at']);
        });
    }
};
