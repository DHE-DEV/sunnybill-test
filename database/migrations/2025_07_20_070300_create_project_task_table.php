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
        if (!Schema::hasTable('project_task')) {
            Schema::create('project_task', function (Blueprint $table) {
                $table->uuid('project_id');
                $table->unsignedBigInteger('task_id');
                $table->primary(['project_id', 'task_id']);
                
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('cascade');
                    
                $table->foreign('task_id')
                    ->references('id')
                    ->on('tasks')
                    ->onDelete('cascade');
                    
                $table->index(['project_id', 'task_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_task');
    }
};
