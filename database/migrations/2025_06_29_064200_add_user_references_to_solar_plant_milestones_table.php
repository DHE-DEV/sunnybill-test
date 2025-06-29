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
        Schema::table('solar_plant_milestones', function (Blueprint $table) {
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('last_responsible_user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->index('project_manager_id');
            $table->index('last_responsible_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solar_plant_milestones', function (Blueprint $table) {
            $table->dropForeign(['project_manager_id']);
            $table->dropForeign(['last_responsible_user_id']);
            $table->dropColumn(['project_manager_id', 'last_responsible_user_id']);
        });
    }
};