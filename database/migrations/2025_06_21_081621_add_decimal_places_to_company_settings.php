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
            $table->integer('article_price_decimal_places')->default(2)->after('pdf_margin_left');
            $table->integer('total_price_decimal_places')->default(2)->after('article_price_decimal_places');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['article_price_decimal_places', 'total_price_decimal_places']);
        });
    }
};