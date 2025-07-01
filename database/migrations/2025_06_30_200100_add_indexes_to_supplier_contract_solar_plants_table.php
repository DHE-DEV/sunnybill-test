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
            // Foreign Key Constraints
            $table->foreign('supplier_contract_id')
                  ->references('id')
                  ->on('supplier_contracts')
                  ->onDelete('cascade');
                  
            $table->foreign('solar_plant_id')
                  ->references('id')
                  ->on('solar_plants')
                  ->onDelete('cascade');

            // Indizes für Performance mit kürzeren Namen
            $table->index(['supplier_contract_id', 'is_active'], 'idx_contract_active');
            $table->index(['solar_plant_id', 'is_active'], 'idx_plant_active');
            $table->index('percentage', 'idx_percentage');

            // Unique Constraint: Ein Vertrag kann nur einmal pro Solaranlage zugeordnet werden
            $table->unique(['supplier_contract_id', 'solar_plant_id'], 'unique_contract_plant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_contract_solar_plants', function (Blueprint $table) {
            // Entferne Indizes
            $table->dropIndex('idx_contract_active');
            $table->dropIndex('idx_plant_active');
            $table->dropIndex('idx_percentage');
            $table->dropUnique('unique_contract_plant');
            
            // Entferne Foreign Keys
            $table->dropForeign(['supplier_contract_id']);
            $table->dropForeign(['solar_plant_id']);
        });
    }
};