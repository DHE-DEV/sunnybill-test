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
        Schema::create('task_read_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('notes_read_at')->nullable();
            $table->timestamp('history_read_at')->nullable();
            $table->timestamps();

            // Unique constraint to ensure one record per user per task
            $table->unique(['task_id', 'user_id']);
            
            // Indexes for performance
            $table->index(['task_id', 'user_id']);
            $table->index('notes_read_at');
            $table->index('history_read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_read_statuses');
    }
};
