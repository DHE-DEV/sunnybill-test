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
        Schema::create('solar_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('solar_plant_id')->constrained()->onDelete('cascade');
            $table->string('fusion_solar_device_id')->nullable()->index();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->integer('rated_power_wp')->nullable();
            $table->decimal('efficiency_percent', 5, 2)->nullable();
            $table->date('installation_date')->nullable();
            $table->enum('status', ['normal', 'alarm', 'offline', 'maintenance', 'degraded'])->default('normal');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            
            // Technische Spezifikationen
            $table->enum('cell_type', ['mono', 'poly', 'thin_film', 'perc', 'bifacial'])->nullable();
            $table->string('module_type')->nullable();
            $table->decimal('voltage_vmp', 6, 2)->nullable();
            $table->decimal('current_imp', 6, 2)->nullable();
            $table->decimal('voltage_voc', 6, 2)->nullable();
            $table->decimal('current_isc', 6, 2)->nullable();
            $table->decimal('temperature_coefficient', 8, 4)->nullable();
            $table->string('dimensions')->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->string('frame_color')->nullable();
            $table->string('glass_type')->nullable();
            
            // String/Array Zuordnung
            $table->integer('string_number')->nullable();
            $table->integer('position_in_string')->nullable();
            $table->integer('orientation_degrees')->nullable();
            $table->integer('tilt_degrees')->nullable();
            $table->decimal('shading_factor', 3, 2)->nullable();
            
            // Aktuelle Werte
            $table->decimal('current_power_w', 8, 1)->nullable();
            $table->decimal('current_voltage_v', 6, 2)->nullable();
            $table->decimal('current_current_a', 6, 3)->nullable();
            $table->decimal('current_temperature_c', 5, 1)->nullable();
            $table->decimal('daily_yield_kwh', 8, 3)->nullable();
            $table->decimal('total_yield_kwh', 10, 2)->nullable();
            
            $table->timestamps();
            
            // Indizes
            $table->index(['solar_plant_id', 'status']);
            $table->index(['string_number', 'position_in_string']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_modules');
    }
};
