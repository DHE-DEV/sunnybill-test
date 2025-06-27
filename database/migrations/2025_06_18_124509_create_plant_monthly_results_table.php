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
        Schema::create('plant_monthly_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('solar_plant_id');
            $table->string('month'); // Format: "2025-06"
            $table->decimal('total_revenue', 15, 6); // Kompletter Monatsgewinn
            $table->timestamps();
            
            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->onDelete('cascade');
            
            // Unique constraint: Pro Anlage nur ein Ergebnis pro Monat
            $table->unique(['solar_plant_id', 'month']);
            
            $table->index('solar_plant_id');
            $table->index('month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plant_monthly_results');
    }
};
