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
        Schema::create('supplier_contract_solar_plants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('supplier_contract_id');
            $table->uuid('solar_plant_id');
            $table->decimal('percentage', 5, 2); // 999.99% max, 2 Nachkommastellen
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('supplier_contract_solar_plants');
    }
};