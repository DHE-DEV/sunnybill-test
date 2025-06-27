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
        Schema::table('plant_monthly_results', function (Blueprint $table) {
            $table->decimal('energy_produced_kwh', 15, 6)->nullable()->after('month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_monthly_results', function (Blueprint $table) {
            $table->dropColumn('energy_produced_kwh');
        });
    }
};
