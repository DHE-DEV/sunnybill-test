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
            $table->decimal('latitude', 10, 8)->nullable()->after('location')->comment('Breitengrad der Solaranlage');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('Längengrad der Solaranlage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plants', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
