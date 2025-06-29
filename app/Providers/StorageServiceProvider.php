<?php

namespace App\Providers;

use App\Models\StorageSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

class StorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Dynamische Konfiguration der 'documents' Disk basierend auf StorageSetting
        $this->configureDynamicDocumentsDisk();
    }

    /**
     * Konfiguriere die 'documents' Disk dynamisch basierend auf den Storage-Einstellungen
     */
    private function configureDynamicDocumentsDisk(): void
    {
        try {
            $storageSetting = StorageSetting::current();
            
            if ($storageSetting && $storageSetting->storage_driver !== 'local') {
                // Überschreibe die 'documents' Disk-Konfiguration mit den aktuellen Einstellungen
                $config = $storageSetting->buildFilesystemConfig();
                config(['filesystems.disks.documents' => $config]);
                
                \Log::info('Dynamic documents disk configured', [
                    'driver' => $storageSetting->storage_driver,
                    'config_keys' => array_keys($config)
                ]);
            } else {
                \Log::info('Using default local documents disk configuration');
            }
        } catch (\Exception $e) {
            // Fallback zu lokaler Konfiguration bei Fehlern
            \Log::warning('Failed to configure dynamic documents disk, using local fallback', [
                'error' => $e->getMessage()
            ]);
        }
    }
}