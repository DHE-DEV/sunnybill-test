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
        Schema::table('tasks', function (Blueprint $table) {
            // Füge eine automatische Aufgabennummer hinzu
            $table->string('task_number')->unique()->after('id');
            
            // Index für bessere Performance bei Suchen
            $table->index('task_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['task_number']);
            $table->dropColumn('task_number');
        });
    }
};