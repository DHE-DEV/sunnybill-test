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
            $table->boolean('show_hints')->default(true)->after('notes')->comment('Ob der Hinweistext auf der PDF angezeigt werden soll');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plant_billings', function (Blueprint $table) {
            $table->dropColumn('show_hints');
        });
    }
};
