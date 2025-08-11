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
        if (!Schema::hasTable('app_tokens')) {
            Schema::create('app_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Name des Tokens (z.B. "iPhone App", "Desktop Client")
            $table->string('token', 255)->unique(); // Hash des Tokens
            $table->json('abilities')->nullable(); // Array der Berechtigungen
            $table->timestamp('expires_at'); // Ablaufdatum
            $table->boolean('is_active')->default(true); // Aktiv/Deaktiv
            $table->timestamp('last_used_at')->nullable(); // Letzte Verwendung
            $table->string('created_by_ip', 45)->nullable(); // IP-Adresse bei Erstellung
            $table->string('app_type', 50)->default('mobile_app'); // App-Typ
            $table->string('app_version', 20)->nullable(); // App-Version
            $table->text('device_info')->nullable(); // Geräteinformationen
            $table->text('notes')->nullable(); // Notizen
            $table->timestamps();
            
            // Indizes
            $table->index(['user_id', 'is_active']);
            $table->index(['expires_at']);
            $table->index(['last_used_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_tokens');
    }
};
