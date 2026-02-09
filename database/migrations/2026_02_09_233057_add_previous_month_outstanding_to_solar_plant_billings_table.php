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
        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->decimal('previous_month_outstanding', 10, 2)->nullable()->default(0)->after('net_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->dropColumn('previous_month_outstanding');
        });
    }
};
