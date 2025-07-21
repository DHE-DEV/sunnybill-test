<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->constrained('projects')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['meeting', 'deadline', 'review', 'milestone_check', 'inspection', 'training'])->default('meeting');
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime')->nullable();
            $table->string('location')->nullable();
            $table->json('attendees')->nullable();
            $table->integer('reminder_minutes')->default(60);
            $table->boolean('is_recurring')->default(false);
            $table->json('recurring_pattern')->nullable();
            $table->enum('status', ['scheduled', 'confirmed', 'cancelled', 'completed'])->default('scheduled');
            $table->uuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'start_datetime']);
            $table->index(['project_id', 'status']);
            $table->index(['start_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_appointments');
    }
};
