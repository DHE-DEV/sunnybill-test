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
        Schema::create('solar_inverters', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('solar_plant_id')->constrained()->onDelete('cascade');
            $table->string('fusion_solar_device_id')->nullable()->index();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->decimal('rated_power_kw', 8, 2)->nullable();
            $table->decimal('efficiency_percent', 5, 2)->nullable();
            $table->date('installation_date')->nullable();
            $table->string('firmware_version')->nullable();
            $table->enum('status', ['normal', 'alarm', 'offline', 'maintenance'])->default('normal');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            
            // Technische Details
            $table->string('input_voltage_range')->nullable();
            $table->string('output_voltage')->nullable();
            $table->string('max_dc_current')->nullable();
            $table->string('max_ac_current')->nullable();
            $table->string('protection_class')->nullable();
            $table->string('cooling_method')->nullable();
            $table->string('dimensions')->nullable();
            $table->decimal('weight_kg', 6, 1)->nullable();
            
            // Aktuelle Werte
            $table->decimal('current_power_kw', 8, 3)->nullable();
            $table->decimal('current_voltage_v', 6, 1)->nullable();
            $table->decimal('current_current_a', 6, 2)->nullable();
            $table->decimal('current_frequency_hz', 5, 2)->nullable();
            $table->decimal('current_temperature_c', 5, 1)->nullable();
            $table->decimal('daily_yield_kwh', 8, 2)->nullable();
            $table->decimal('total_yield_kwh', 10, 2)->nullable();
            
            $table->timestamps();
            
            // Indizes
            $table->index(['solar_plant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_inverters');
    }
};
