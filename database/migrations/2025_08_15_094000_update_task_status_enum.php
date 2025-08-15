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
            // Update the status enum to include all valid status values
            $table->enum('status', [
                'open', 
                'in_progress', 
                'waiting_external', 
                'waiting_internal', 
                'completed', 
                'cancelled'
            ])->default('open')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Revert to original enum values
            $table->enum('status', [
                'open', 
                'in_progress', 
                'completed', 
                'cancelled'
            ])->default('open')->change();
        });
    }
};
