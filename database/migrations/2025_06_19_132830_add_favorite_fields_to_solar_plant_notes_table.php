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
        Schema::table('solar_plant_notes', function (Blueprint $table) {
            $table->boolean('is_favorite')->default(false)->after('type');
            $table->integer('sort_order')->default(0)->after('is_favorite');
            
            // Index für bessere Performance
            $table->index(['solar_plant_id', 'is_favorite', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plant_notes', function (Blueprint $table) {
            $table->dropIndex(['solar_plant_id', 'is_favorite', 'sort_order']);
            $table->dropColumn(['is_favorite', 'sort_order']);
        });
    }
};