<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FusionSolarService;

class TestFusionSolarConnection extends Command
{
    protected $signature = 'fusionsolar:test';
    protected $description = 'Testet die FusionSolar-Verbindung ohne Synchronisation';

    public function handle()
    {
        $this->info('🔍 Teste FusionSolar-Verbindung...');

        $service = new FusionSolarService();

        // Test Authentifizierung
        $this->line('1. Authentifizierung testen...');
        
        $reflection = new \ReflectionClass($service);
        $authenticateMethod = $reflection->getMethod('authenticate');
        $authenticateMethod->setAccessible(true);

        if ($authenticateMethod->invoke($service)) {
            $this->info('   ✅ Authentifizierung erfolgreich');
            
            $tokenProperty = $reflection->getProperty('token');
            $tokenProperty->setAccessible(true);
            $token = $tokenProperty->getValue($service);
            $this->line('   🔑 Token erhalten: ' . substr($token, 0, 20) . '...');
        } else {
            $this->error('   ❌ Authentifizierung fehlgeschlagen');
            return 1;
        }

        // Test API-Zugriff
        $this->line('2. API-Zugriff testen...');
        
        $apiMethod = $reflection->getMethod('apiRequest');
        $apiMethod->setAccessible(true);

        try {
            $result = $apiMethod->invoke($service, '/getStationList', []);
            
            if ($result === null) {
                // Prüfe die letzten Logs für spezifische Fehlercodes
                $logFile = storage_path('logs/laravel.log');
                $authError = false;
                
                if (file_exists($logFile)) {
                    $logs = file_get_contents($logFile);
                    if (strpos($logs, '"failCode":20056') !== false) {
                        $this->error('   ❌ BERECHTIGUNGSFEHLER (Code 20056)');
                        $this->line('   📋 Ihr Northbound-Benutzer ist nicht autorisiert!');
                        $this->line('');
                        $this->line('🔧 LÖSUNG:');
                        $this->line('   1. Gehen Sie zu Ihrem FusionSolar-Portal');
                        $this->line('   2. System Management → User Management');
                        $this->line('   3. Suchen Sie Ihren Northbound-Benutzer');
                        $this->line('   4. Klicken Sie auf "Authorize" oder "Berechtigen"');
                        $this->line('   5. Weisen Sie dem Benutzer Zugriff auf ALLE Anlagen zu');
                        $this->line('   6. Aktivieren Sie alle API-Berechtigungen');
                        $this->line('   7. Speichern und 5-10 Minuten warten');
                        $authError = true;
                    }
                }
                
                if (!$authError) {
                    $this->warn('   ⚠️  API-Aufruf fehlgeschlagen (siehe Logs für Details)');
                }
            } elseif (empty($result)) {
                $this->info('   ✅ API-Zugriff erfolgreich');
                $this->warn('   ℹ️  Keine Anlagen gefunden - das ist normal für neue Accounts');
                $this->line('');
                $this->line('📋 Nächste Schritte:');
                $this->line('   1. Fügen Sie Anlagen zu Ihrem FusionSolar-Account hinzu');
                $this->line('   2. Stellen Sie sicher, dass Ihr Northbound-Benutzer Zugriff auf die Anlagen hat');
                $this->line('   3. Überprüfen Sie die Berechtigungen in der FusionSolar-Konfiguration');
            } else {
                $this->info('   ✅ API-Zugriff erfolgreich');
                $this->info('   🏭 ' . count($result) . ' Anlage(n) gefunden');
                
                foreach ($result as $i => $plant) {
                    $name = $plant['stationName'] ?? 'Unbekannt';
                    $capacity = isset($plant['capacity']) ? ($plant['capacity'] / 1000) . ' kW' : 'Unbekannt';
                    $this->line("      " . ($i + 1) . ". $name ($capacity)");
                }
            }
        } catch (\Exception $e) {
            $this->error('   ❌ API-Fehler: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🎉 FusionSolar-Verbindung erfolgreich getestet!');
        
        if (empty($result)) {
            $this->line('💡 Tipp: Sobald Sie Anlagen hinzufügen, können Sie `php artisan fusionsolar:sync` verwenden');
        } else {
            $this->line('💡 Sie können jetzt `php artisan fusionsolar:sync` verwenden');
        }

        return 0;
    }
}