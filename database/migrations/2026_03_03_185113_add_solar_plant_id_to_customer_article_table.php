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
            $table->uuid('solar_plant_id')->nullable()->after('article_id');
            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_article', function (Blueprint $table) {
            $table->dropForeign(['solar_plant_id']);
            $table->dropColumn('solar_plant_id');
        });
    }
};
