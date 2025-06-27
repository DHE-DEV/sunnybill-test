<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FusionSolarService;
use App\Models\SolarPlant;
use Carbon\Carbon;

class SyncFusionSolar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fusionsolar:sync {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronisiert Solaranlagen-Daten von FusionSolar';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌞 Starte FusionSolar Synchronisation...');

        $fusionSolarService = new FusionSolarService();
        
        // Prüfe Konfiguration
        if (!config('services.fusionsolar.username') || !config('services.fusionsolar.password')) {
            $this->error('❌ FusionSolar Zugangsdaten nicht konfiguriert!');
            $this->line('Bitte setzen Sie FUSIONSOLAR_USERNAME und FUSIONSOLAR_PASSWORD in der .env Datei.');
            return 1;
        }

        $this->info('📡 Rufe Anlagendaten von FusionSolar ab...');
        
        $result = $fusionSolarService->syncAllPlants();

        if (!$result['success']) {
            $this->error('❌ Synchronisation fehlgeschlagen!');
            foreach ($result['errors'] as $error) {
                $this->error("   • $error");
            }
            return 1;
        }

        $this->info("✅ {$result['synced']} Anlagen von FusionSolar abgerufen");

        // Zeige Komponenten-Statistiken
        if (isset($result['components'])) {
            $this->line("   🔧 {$result['components']['inverters']} Wechselrichter synchronisiert");
            $this->line("   📱 {$result['components']['modules']} Module synchronisiert");
            $this->line("   🔋 {$result['components']['batteries']} Batterien synchronisiert");
        }

        // Zusammenfassung
        $this->newLine();
        $this->info('📊 Synchronisation abgeschlossen:');
        $this->line("   • {$result['synced']} Anlagen synchronisiert");
        if (isset($result['components'])) {
            $this->line("   • {$result['components']['inverters']} Wechselrichter");
            $this->line("   • {$result['components']['modules']} Module");
            $this->line("   • {$result['components']['batteries']} Batterien");
        }

        if (!empty($result['errors'])) {
            $this->newLine();
            $this->warn('⚠️  Warnungen:');
            foreach ($result['errors'] as $error) {
                $this->line("   • $error");
            }
        }

        return 0;
    }
}
