<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['planning', 'approval', 'implementation', 'testing', 'delivery', 'payment', 'review'])->default('planning');
            $table->date('planned_date');
            $table->date('actual_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'delayed', 'cancelled'])->default('pending');
            $table->uuid('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('dependencies')->nullable();
            $table->integer('completion_percentage')->default(0);
            $table->boolean('is_critical_path')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'planned_date']);
            $table->index(['responsible_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};
