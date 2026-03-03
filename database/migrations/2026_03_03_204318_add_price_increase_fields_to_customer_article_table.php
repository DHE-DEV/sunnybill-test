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
        Schema::table('customer_article', function (Blueprint $table) {
            $table->decimal('price_increase_percentage', 8, 4)->nullable()->after('solar_plant_id');
            $table->integer('price_increase_interval_months')->nullable()->after('price_increase_percentage');
            $table->date('price_increase_start_date')->nullable()->after('price_increase_interval_months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_article', function (Blueprint $table) {
            $table->dropColumn(['price_increase_percentage', 'price_increase_interval_months', 'price_increase_start_date']);
        });
    }
};
