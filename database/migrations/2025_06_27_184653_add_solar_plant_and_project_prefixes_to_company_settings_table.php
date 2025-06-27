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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('solar_plant_number_prefix', 10)->nullable()->after('invoice_number_include_year');
            $table->string('project_number_prefix', 10)->nullable()->after('solar_plant_number_prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['solar_plant_number_prefix', 'project_number_prefix']);
        });
    }
};
