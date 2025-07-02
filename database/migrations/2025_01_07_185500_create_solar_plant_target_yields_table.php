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
        Schema::create('solar_plant_target_yields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('solar_plant_id');
            $table->integer('year');
            $table->decimal('january_kwh', 10, 2)->nullable();
            $table->decimal('february_kwh', 10, 2)->nullable();
            $table->decimal('march_kwh', 10, 2)->nullable();
            $table->decimal('april_kwh', 10, 2)->nullable();
            $table->decimal('may_kwh', 10, 2)->nullable();
            $table->decimal('june_kwh', 10, 2)->nullable();
            $table->decimal('july_kwh', 10, 2)->nullable();
            $table->decimal('august_kwh', 10, 2)->nullable();
            $table->decimal('september_kwh', 10, 2)->nullable();
            $table->decimal('october_kwh', 10, 2)->nullable();
            $table->decimal('november_kwh', 10, 2)->nullable();
            $table->decimal('december_kwh', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->onDelete('cascade');
            $table->unique(['solar_plant_id', 'year']);
            $table->index(['solar_plant_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_plant_target_yields');
    }
};