<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('project_number', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['solar_plant', 'internal', 'customer', 'development', 'maintenance'])->default('solar_plant');
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed', 'cancelled'])->default('planning');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('actual_costs', 15, 2)->nullable();
            $table->integer('progress_percentage')->default(0);
            $table->uuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->uuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->uuid('solar_plant_id')->nullable()->constrained('solar_plants')->nullOnDelete();
            $table->uuid('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
            $table->index('start_date');
            $table->index('planned_end_date');
            $table->index('project_manager_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
