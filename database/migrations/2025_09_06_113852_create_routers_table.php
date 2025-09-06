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
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            
            // Grunddaten
            $table->string('name')->comment('Router Name (z.B. Router Standort A)');
            $table->string('model')->default('RUTX50')->comment('Router-Modell (z.B. Teltonika RUTX50)');
            $table->string('serial_number')->nullable()->comment('Seriennummer des Routers');
            $table->string('location')->comment('Standort/Adresse des Routers');
            $table->text('description')->nullable()->comment('Beschreibung/Notizen');
            
            // Status und Verbindung
            $table->boolean('is_active')->default(true)->comment('Router aktiv/inaktiv');
            $table->enum('connection_status', ['online', 'delayed', 'offline'])->default('offline')->comment('Aktueller Verbindungsstatus');
            $table->timestamp('last_seen_at')->nullable()->comment('Letzter Webhook-Aufruf');
            
            // Netzwerk-Informationen (vom Webhook)
            $table->string('operator')->nullable()->comment('Mobilfunkanbieter (z.B. Telekom.de)');
            $table->integer('signal_strength')->nullable()->comment('Signalstärke in dBm');
            $table->string('network_type')->nullable()->comment('Netzwerktyp (4G, 5G)');
            $table->integer('signal_bars')->nullable()->comment('Berechnete Signalbalken (1-5)');
            
            // Webhook-Konfiguration
            $table->string('webhook_token')->nullable()->comment('Eindeutiger Token für Webhook-Authentifizierung');
            $table->string('ip_address')->nullable()->comment('IP-Adresse des Routers');
            $table->integer('webhook_port')->default(3000)->comment('Port für Webhook-Endpunkt');
            
            // Koordinaten für Kartenanzeige
            $table->decimal('latitude', 10, 8)->nullable()->comment('Breitengrad');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Längengrad');
            
            // Zusätzliche Informationen
            $table->json('last_data')->nullable()->comment('Letzte empfangene Webhook-Daten als JSON');
            $table->integer('total_webhooks')->default(0)->comment('Anzahl empfangener Webhooks');
            $table->timestamp('installed_at')->nullable()->comment('Installationsdatum');
            
            $table->timestamps();
            
            // Indizes
            $table->index('connection_status');
            $table->index('is_active');
            $table->index('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
