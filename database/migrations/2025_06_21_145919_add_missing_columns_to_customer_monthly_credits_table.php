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
            $table->decimal('share_percentage', 5, 2)->nullable()->after('plant_monthly_result_id');
            $table->decimal('credited_amount', 15, 6)->nullable()->after('share_percentage');
            $table->decimal('full_plant_revenue', 15, 6)->nullable()->after('credited_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_monthly_credits', function (Blueprint $table) {
            $table->dropColumn(['share_percentage', 'credited_amount', 'full_plant_revenue']);
        });
    }
};
