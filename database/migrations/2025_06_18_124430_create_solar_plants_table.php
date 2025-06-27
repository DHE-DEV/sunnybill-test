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
        Schema::create('solar_plants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('location');
            $table->text('description')->nullable();
            $table->date('installation_date');
            $table->decimal('monthly_result', 15, 6)->default(0); // Erzeugter Gewinn pro Monat
            $table->timestamps();
            
            $table->index('name');
            $table->index('location');
            $table->index('installation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_plants');
    }
};
