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
        Schema::table('supplier_contract_solar_plants', function (Blueprint $table) {
            // Prüfe ob Indizes bereits existieren, bevor sie hinzugefügt werden
            try {
                $table->index(['supplier_contract_id', 'is_active'], 'idx_contract_active');
            } catch (\Exception $e) {
                // Index existiert bereits
            }
            
            try {
                $table->index(['solar_plant_id', 'is_active'], 'idx_plant_active');
            } catch (\Exception $e) {
                // Index existiert bereits
            }
            
            try {
                $table->index('percentage', 'idx_percentage');
            } catch (\Exception $e) {
                // Index existiert bereits
            }

            try {
                // Unique Constraint: Ein Vertrag kann nur einmal pro Solaranlage zugeordnet werden
                $table->unique(['supplier_contract_id', 'solar_plant_id'], 'unique_contract_plant');
            } catch (\Exception $e) {
                // Unique constraint existiert bereits
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_solar_plants', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_contract_active');
            } catch (\Exception $e) {
                // Index existiert nicht
            }
            
            try {
                $table->dropIndex('idx_plant_active');
            } catch (\Exception $e) {
                // Index existiert nicht
            }
            
            try {
                $table->dropIndex('idx_percentage');
            } catch (\Exception $e) {
                // Index existiert nicht
            }
            
            try {
                $table->dropUnique('unique_contract_plant');
            } catch (\Exception $e) {
                // Unique constraint existiert nicht
            }
        });
    }
};