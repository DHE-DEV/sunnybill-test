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
        Schema::table('costs', function (Blueprint $table) {
            // Kunde oder Lieferant (polymorphe Beziehung)
            $table->string('costable_type')->nullable()->after('supplier');
            $table->uuid('costable_id')->nullable()->after('costable_type');
            
            // Solaranlage
            $table->uuid('solar_plant_id')->nullable()->after('costable_id');
            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->nullOnDelete();
            
            // Projekt
            $table->uuid('project_id')->nullable()->after('solar_plant_id');
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            
            // Indices
            $table->index(['costable_type', 'costable_id']);
            $table->index('solar_plant_id');
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('costs', function (Blueprint $table) {
            $table->dropForeign(['solar_plant_id']);
            $table->dropForeign(['project_id']);
            
            $table->dropIndex(['costable_type', 'costable_id']);
            $table->dropIndex(['solar_plant_id']);
            $table->dropIndex(['project_id']);
            
            $table->dropColumn(['costable_type', 'costable_id', 'solar_plant_id', 'project_id']);
        });
    }
};