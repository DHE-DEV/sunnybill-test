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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // gmail_email, system, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Zusätzliche Daten (email_id, url, etc.)
            $table->string('icon')->nullable(); // Icon-Name oder URL
            $table->string('color')->default('primary'); // primary, success, warning, danger
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('action_url')->nullable(); // URL für Aktion
            $table->string('action_text')->nullable(); // Text für Aktions-Button
            $table->timestamp('expires_at')->nullable(); // Ablaufzeit für temporäre Benachrichtigungen
            $table->timestamps();

            // Indizes für bessere Performance
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
