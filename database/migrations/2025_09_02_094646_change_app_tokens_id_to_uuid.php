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
        Schema::table('app_tokens', function (Blueprint $table) {
            // First, drop the primary key constraint
            $table->dropPrimary(['id']);
            
            // Change the id column from integer to UUID
            $table->uuid('id')->change();
            
            // Re-add the primary key constraint
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_tokens', function (Blueprint $table) {
            // Drop the primary key constraint
            $table->dropPrimary(['id']);
            
            // Change the id column back to integer
            $table->id()->change();
        });
    }
};
