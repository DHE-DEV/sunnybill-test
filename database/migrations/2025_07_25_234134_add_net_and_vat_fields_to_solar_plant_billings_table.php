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
            $table->decimal('total_costs_net', 10, 2)->default(0)->after('total_costs');
            $table->decimal('total_credits_net', 10, 2)->default(0)->after('total_credits');
            $table->decimal('total_vat_amount', 10, 2)->default(0)->after('net_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->dropColumn(['total_costs_net', 'total_credits_net', 'total_vat_amount']);
        });
    }
};
