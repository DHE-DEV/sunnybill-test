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
            $table->string('app_code', 16)->unique()->nullable()->after('plant_number');
        });
        
        // Generiere AppCode für alle existierenden Solaranlagen
        \App\Models\SolarPlant::withTrashed()->get()->each(function ($plant) {
            $plant->app_code = \App\Models\SolarPlant::generateUniqueAppCode();
            $plant->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->dropColumn('app_code');
        });
    }
};
