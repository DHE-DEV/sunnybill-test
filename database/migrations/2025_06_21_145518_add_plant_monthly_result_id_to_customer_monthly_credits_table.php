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
        Schema::table('customer_monthly_credits', function (Blueprint $table) {
            $table->uuid('plant_monthly_result_id')->nullable()->after('customer_id');
            $table->foreign('plant_monthly_result_id')->references('id')->on('plant_monthly_results')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_monthly_credits', function (Blueprint $table) {
            $table->dropForeign(['plant_monthly_result_id']);
            $table->dropColumn('plant_monthly_result_id');
        });
    }
};
