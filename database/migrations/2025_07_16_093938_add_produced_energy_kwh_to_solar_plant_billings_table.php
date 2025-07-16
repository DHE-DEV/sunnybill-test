<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->decimal('produced_energy_kwh', 10, 3)->nullable()->after('participation_percentage');
        });
    }

    public function down()
    {
        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->dropColumn('produced_energy_kwh');
        });
    }
};
