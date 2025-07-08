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
        Schema::create('gmail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('gmail_id')->index();
            $table->string('subject')->nullable();
            $table->string('from_email')->nullable();
            $table->integer('total_labels')->default(0);
            $table->json('all_labels')->nullable();
            $table->json('system_labels')->nullable();
            $table->json('category_labels')->nullable();
            $table->json('user_labels')->nullable();
            $table->boolean('has_inbox')->default(false);
            $table->boolean('is_unread')->default(false);
            $table->boolean('is_important')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('filter_active')->default(false);
            $table->string('action')->default('sync'); // sync, created, updated
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['gmail_id', 'created_at']);
            $table->index(['has_inbox', 'filter_active']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmail_logs');
    }
};
