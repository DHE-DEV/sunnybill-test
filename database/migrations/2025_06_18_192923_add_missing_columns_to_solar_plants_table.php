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
        Schema::table('solar_plants', function (Blueprint $table) {
            // Technische Daten
            $table->decimal('total_capacity_kw', 15, 6)->nullable()->after('description');
            $table->integer('panel_count')->nullable()->after('total_capacity_kw');
            $table->integer('inverter_count')->nullable()->after('panel_count');
            $table->decimal('battery_capacity_kwh', 15, 6)->nullable()->after('inverter_count');
            $table->decimal('expected_annual_yield_kwh', 15, 6)->nullable()->after('battery_capacity_kwh');
            
            // Finanzielle Daten
            $table->decimal('total_investment', 15, 6)->nullable()->after('expected_annual_yield_kwh');
            $table->decimal('annual_operating_costs', 15, 6)->nullable()->after('total_investment');
            $table->decimal('feed_in_tariff_per_kwh', 15, 6)->nullable()->after('annual_operating_costs');
            $table->decimal('electricity_price_per_kwh', 15, 6)->nullable()->after('feed_in_tariff_per_kwh');
            
            // Status und Termine
            $table->enum('status', ['planned', 'under_construction', 'active', 'maintenance', 'inactive'])->default('planned')->after('electricity_price_per_kwh');
            $table->boolean('is_active')->default(true)->after('status');
            $table->date('commissioning_date')->nullable()->after('installation_date');
            $table->text('notes')->nullable()->after('is_active');
            
            // monthly_result entfernen da es durch plant_monthly_results ersetzt wird
            $table->dropColumn('monthly_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->dropColumn([
                'total_capacity_kw',
                'panel_count',
                'inverter_count',
                'battery_capacity_kwh',
                'expected_annual_yield_kwh',
                'total_investment',
                'annual_operating_costs',
                'feed_in_tariff_per_kwh',
                'electricity_price_per_kwh',
                'status',
                'is_active',
                'commissioning_date',
                'notes'
            ]);
            
            $table->decimal('monthly_result', 15, 6)->default(0);
        });
    }
};
