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
        Schema::table('task_notes', function (Blueprint $table) {
            $table->json('mentioned_users')->nullable()->after('content');
        });

        // Create pivot table for task note mentions
        Schema::create('task_note_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_note_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['task_note_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_note_mentions');
        
        Schema::table('task_notes', function (Blueprint $table) {
            $table->dropColumn('mentioned_users');
        });
    }
};