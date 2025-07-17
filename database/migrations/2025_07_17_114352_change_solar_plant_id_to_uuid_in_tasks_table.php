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
        Schema::table('tasks', function (Blueprint $table) {
            // Erst den Index entfernen, falls vorhanden
            $table->dropIndex(['solar_plant_id', 'status']);
            
            // Spalte zu UUID ändern
            $table->uuid('solar_plant_id')->nullable()->change();
            
            // Index wieder hinzufügen
            $table->index(['solar_plant_id', 'status']);
            
            // Foreign Key Constraint hinzufügen
            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Foreign Key Constraint entfernen
            $table->dropForeign(['solar_plant_id']);
            
            // Index entfernen
            $table->dropIndex(['solar_plant_id', 'status']);
            
            // Spalte zurück zu unsignedBigInteger ändern
            $table->unsignedBigInteger('solar_plant_id')->nullable()->change();
            
            // Index wieder hinzufügen
            $table->index(['solar_plant_id', 'status']);
        });
    }
};
