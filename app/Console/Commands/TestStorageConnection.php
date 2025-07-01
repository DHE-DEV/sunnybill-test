<?php

namespace App\Console\Commands;

use App\Models\StorageSetting;
use Illuminate\Console\Command;

class TestStorageConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:test {--driver= : Specific driver to test (local, s3, digitalocean)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test storage connection based on current settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing Storage Connection...');
        $this->newLine();

        $driver = $this->option('driver');
        
        if ($driver) {
            $this->testSpecificDriver($driver);
        } else {
            $this->testCurrentSettings();
        }
    }

    private function testCurrentSettings()
    {
        $setting = StorageSetting::current();
        
        if (!$setting) {
            $this->warn('⚠️  Keine aktive Storage-Einstellung gefunden.');
            $this->info('💡 Verwende Fallback auf lokalen Speicher.');
            $this->testLocalStorage();
            return;
        }

        $this->info("📋 Aktuelle Einstellung: {$setting->storage_driver}");
        
        switch ($setting->storage_driver) {
            case 'local':
                $this->testLocalStorage();
                break;
            case 's3':
            case 'digitalocean':
                $this->testCloudStorage($setting);
                break;
            default:
                $this->error("❌ Unbekannter Storage-Driver: {$setting->storage_driver}");
        }
    }

    private function testSpecificDriver(string $driver)
    {
        $this->info("🎯 Teste spezifischen Driver: {$driver}");
        
        switch ($driver) {
            case 'local':
                $this->testLocalStorage();
                break;
            case 's3':
            case 'digitalocean':
                $setting = StorageSetting::current();
                if (!$setting || $setting->storage_driver !== $driver) {
                    $this->error("❌ Keine aktive Konfiguration für {$driver} gefunden.");
                    return;
                }
                $this->testCloudStorage($setting);
                break;
            default:
                $this->error("❌ Unbekannter Driver: {$driver}");
        }
    }

    private function testLocalStorage()
    {
        $this->info('🏠 Teste lokalen Speicher...');
        
        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('local');
            $testPath = 'storage-test-' . time() . '.txt';
            $testContent = 'Storage-Test: ' . now();

            // Upload
            $disk->put($testPath, $testContent);
            $this->info('✅ Upload erfolgreich');

            // Existenz prüfen
            if ($disk->exists($testPath)) {
                $this->info('✅ Datei existiert');
            } else {
                $this->error('❌ Datei nicht gefunden');
                return;
            }

            // Download
            $downloadedContent = $disk->get($testPath);
            if ($downloadedContent === $testContent) {
                $this->info('✅ Download erfolgreich');
            } else {
                $this->error('❌ Download-Inhalt stimmt nicht überein');
            }

            // Größe
            $size = $disk->size($testPath);
            $this->info("📏 Dateigröße: {$size} Bytes");

            // Aufräumen
            $disk->delete($testPath);
            $this->info('✅ Aufräumen erfolgreich');

            $this->newLine();
            $this->info('🎉 Lokaler Speicher funktioniert einwandfrei!');

        } catch (\Exception $e) {
            $this->error('❌ Lokaler Speicher-Test fehlgeschlagen:');
            $this->error($e->getMessage());
        }
    }

    private function testCloudStorage(StorageSetting $setting)
    {
        $this->info("☁️  Teste Cloud-Speicher ({$setting->storage_driver})...");
        
        try {
            $result = $setting->testConnection();
            
            if ($result['success']) {
                $this->info('✅ ' . $result['message']);
                $this->newLine();
                $this->info('🎉 Cloud-Speicher funktioniert einwandfrei!');
            } else {
                $this->error('❌ Cloud-Speicher-Test fehlgeschlagen:');
                $this->error($result['message']);
                
                $this->newLine();
                $this->warn('💡 Troubleshooting-Tipps:');
                $this->line('• Überprüfen Sie Ihre Credentials in den Storage-Einstellungen');
                $this->line('• Stellen Sie sicher, dass der Space/Bucket existiert');
                $this->line('• Prüfen Sie die Netzwerk-Konnektivität');
                $this->line('• Kontrollieren Sie eventuelle IP-Beschränkungen');
            }

        } catch (\Exception $e) {
            $this->error('❌ Cloud-Speicher-Test Ausnahme:');
            $this->error($e->getMessage());
        }
    }
}