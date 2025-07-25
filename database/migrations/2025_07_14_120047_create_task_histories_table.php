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
        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // 'created', 'updated', 'status_changed', etc.
            $table->string('field_name')->nullable(); // Name des geänderten Feldes
            $table->text('old_value')->nullable(); // Alter Wert
            $table->text('new_value')->nullable(); // Neuer Wert
            $table->text('description'); // Beschreibung der Änderung
            $table->timestamps();
            
            $table->index(['task_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_histories');
    }
};
