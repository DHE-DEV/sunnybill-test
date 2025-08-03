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
        Schema::table('user_table_preferences', function (Blueprint $table) {
            $table->json('infolist_state')->nullable()->after('column_searches'); // Zustand der Infolist-Sections (auf-/zugeklappt)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_table_preferences', function (Blueprint $table) {
            $table->dropColumn('infolist_state');
        });
    }
};
