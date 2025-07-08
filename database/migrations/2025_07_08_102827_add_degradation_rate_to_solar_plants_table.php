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
            $table->decimal('degradation_rate', 5, 2)->nullable()->after('electricity_price_per_kwh')
                ->comment('Jährliche Degradationsrate in Prozent (z.B. 0.50 für 0,5% pro Jahr)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->dropColumn('degradation_rate');
        });
    }
};
