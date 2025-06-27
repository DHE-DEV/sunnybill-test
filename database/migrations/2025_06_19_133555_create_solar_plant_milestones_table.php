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
        Schema::create('solar_plant_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('solar_plant_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('planned_date');
            $table->date('actual_date')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'delayed', 'cancelled'])->default('planned');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['solar_plant_id', 'sort_order']);
            $table->index(['solar_plant_id', 'status']);
            $table->index('planned_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_plant_milestones');
    }
};