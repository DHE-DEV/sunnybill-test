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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            
            // ToDoist-ähnliche Funktionen
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->json('labels')->nullable(); // Array von Labels
            $table->integer('order_index')->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_pattern')->nullable(); // daily, weekly, monthly, etc.
            $table->integer('estimated_minutes')->nullable();
            $table->integer('actual_minutes')->nullable();
            
            // Zuordnungen zu anderen Entitäten
            $table->foreignId('task_type_id')->constrained('task_types')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('solar_plant_id')->nullable();
            $table->unsignedBigInteger('billing_id')->nullable();
            $table->unsignedBigInteger('milestone_id')->nullable();
            
            // Benutzer-Zuordnung
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by');
            
            // Foreign Key Constraints (nur für task_types, da diese garantiert existiert)
            // Andere Constraints werden später über separate Migrationen hinzugefügt
            
            // Hierarchie für Subtasks
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            
            // Zeitstempel
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indizes für Performance
            $table->index(['status', 'due_date']);
            $table->index(['assigned_to', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['solar_plant_id', 'status']);
            $table->index(['parent_task_id']);
            $table->index(['task_type_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
