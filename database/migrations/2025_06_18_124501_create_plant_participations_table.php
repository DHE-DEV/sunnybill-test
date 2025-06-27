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
        Schema::create('plant_participations', function (Blueprint $table) {
            $table->id();
            $table->uuid('customer_id');
            $table->uuid('solar_plant_id');
            $table->decimal('percentage', 5, 2); // z.B. 25.50 = 25,5%
            $table->timestamps();
            
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->onDelete('cascade');
            
            // Unique constraint: Ein Kunde kann nur einmal pro Anlage beteiligt sein
            $table->unique(['customer_id', 'solar_plant_id']);
            
            $table->index('customer_id');
            $table->index('solar_plant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plant_participations');
    }
};
