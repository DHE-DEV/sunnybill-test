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
        Schema::table('article_versions', function (Blueprint $table) {
            $table->integer('decimal_places')->default(2)->after('unit');
            $table->integer('total_decimal_places')->default(2)->after('decimal_places');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_versions', function (Blueprint $table) {
            $table->dropColumn(['decimal_places', 'total_decimal_places']);
        });
    }
};