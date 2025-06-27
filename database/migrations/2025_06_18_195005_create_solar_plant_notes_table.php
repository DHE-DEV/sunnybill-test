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
        Schema::create('solar_plant_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('solar_plant_id');
            $table->unsignedBigInteger('user_id'); // Verwende bigint für user_id
            $table->string('title')->nullable();
            $table->text('content');
            $table->enum('type', ['general', 'maintenance', 'issue', 'improvement'])->default('general');
            $table->timestamps();
            
            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('solar_plant_id');
            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_plant_notes');
    }
};
