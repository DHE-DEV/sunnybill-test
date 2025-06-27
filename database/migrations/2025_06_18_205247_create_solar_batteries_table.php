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
        Schema::create('solar_batteries', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('solar_plant_id')->constrained()->onDelete('cascade');
            $table->string('fusion_solar_device_id')->nullable()->index();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->decimal('capacity_kwh', 8, 2)->nullable();
            $table->decimal('usable_capacity_kwh', 8, 2)->nullable();
            $table->decimal('rated_power_kw', 8, 2)->nullable();
            $table->date('installation_date')->nullable();
            $table->enum('status', ['normal', 'charging', 'discharging', 'standby', 'alarm', 'offline', 'maintenance'])->default('normal');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            
            // Technische Spezifikationen
            $table->string('battery_type')->nullable();
            $table->enum('chemistry', ['li_ion', 'lifepo4', 'lead_acid', 'saltwater', 'flow'])->nullable();
            $table->decimal('nominal_voltage_v', 6, 1)->nullable();
            $table->decimal('max_charge_power_kw', 8, 2)->nullable();
            $table->decimal('max_discharge_power_kw', 8, 2)->nullable();
            $table->decimal('efficiency_percent', 5, 2)->nullable();
            $table->integer('cycle_life')->nullable();
            $table->integer('warranty_years')->nullable();
            $table->integer('operating_temp_min')->nullable();
            $table->integer('operating_temp_max')->nullable();
            $table->string('dimensions')->nullable();
            $table->decimal('weight_kg', 6, 1)->nullable();
            $table->string('protection_class')->nullable();
            
            // Aktuelle Werte
            $table->decimal('current_soc_percent', 5, 1)->nullable();
            $table->decimal('current_voltage_v', 6, 2)->nullable();
            $table->decimal('current_current_a', 6, 2)->nullable();
            $table->decimal('current_power_kw', 8, 3)->nullable();
            $table->decimal('current_temperature_c', 5, 1)->nullable();
            $table->integer('charge_cycles')->nullable();
            $table->decimal('daily_charge_kwh', 8, 2)->nullable();
            $table->decimal('daily_discharge_kwh', 8, 2)->nullable();
            $table->decimal('total_charge_kwh', 10, 2)->nullable();
            $table->decimal('total_discharge_kwh', 10, 2)->nullable();
            
            // Zustand und Gesundheit
            $table->decimal('health_percent', 5, 1)->nullable();
            $table->decimal('remaining_capacity_kwh', 8, 2)->nullable();
            $table->decimal('degradation_percent', 5, 2)->nullable();
            
            $table->timestamps();
            
            // Indizes
            $table->index(['solar_plant_id', 'status']);
            $table->index(['current_soc_percent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solar_batteries');
    }
};
