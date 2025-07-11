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
        Schema::table('plant_monthly_results', function (Blueprint $table) {
            $table->enum('billing_type', ['invoice', 'credit_note'])
                  ->default('invoice')
                  ->after('total_revenue')
                  ->comment('Abrechnungstyp: Rechnung oder Gutschrift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_monthly_results', function (Blueprint $table) {
            $table->dropColumn('billing_type');
        });
    }
};
