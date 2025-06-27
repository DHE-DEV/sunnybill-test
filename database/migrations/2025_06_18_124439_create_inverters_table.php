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
        Schema::create('inverters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('solar_plant_id');
            $table->string('model');
            $table->string('manufacturer');
            $table->string('serial_number')->unique();
            $table->timestamps();
            
            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->onDelete('cascade');
            $table->index('manufacturer');
            $table->index('model');
            $table->index('serial_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inverters');
    }
};
