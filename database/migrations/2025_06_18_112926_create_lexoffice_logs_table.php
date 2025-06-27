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
        Schema::create('lexoffice_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'customer', 'article', 'invoice'
            $table->string('action'); // 'import', 'export', 'sync'
            $table->uuid('entity_id')->nullable(); // ID des betroffenen Datensatzes
            $table->string('lexoffice_id')->nullable(); // Lexoffice ID
            $table->json('request_data')->nullable(); // Gesendete Daten
            $table->json('response_data')->nullable(); // Antwort von Lexoffice
            $table->string('status'); // 'success', 'error'
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['type', 'action']);
            $table->index('status');
            $table->index('entity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lexoffice_logs');
    }
};
