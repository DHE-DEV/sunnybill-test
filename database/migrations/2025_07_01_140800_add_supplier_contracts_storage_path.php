<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Füge die supplier_contracts Pfadkonfiguration zu bestehenden Storage-Einstellungen hinzu
        $this->addSupplierContractsStoragePath();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Entferne die supplier_contracts Pfadkonfiguration
        $this->removeSupplierContractsStoragePath();
    }

    /**
     * Fügt die supplier_contracts Pfadkonfiguration hinzu
     */
    private function addSupplierContractsStoragePath(): void
    {
        $storageSettings = DB::table('storage_settings')->get();
        
        foreach ($storageSettings as $setting) {
            // Sichere Behandlung der storage_paths
            $storagePaths = [];
            
            if (is_string($setting->storage_paths)) {
                $decoded = json_decode($setting->storage_paths, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $storagePaths = $decoded;
                }
            } elseif (is_array($setting->storage_paths)) {
                $storagePaths = $setting->storage_paths;
            }

            // Prüfe ob supplier_contracts bereits existiert
            if (!isset($storagePaths['supplier_contracts'])) {
                // Füge die neue supplier_contracts Konfiguration hinzu
                $storagePaths['supplier_contracts'] = [
                'name' => 'Lieferantenvertrags-Dokumente',
                'description' => 'Strukturierte Ablage für Lieferantenvertragsdokumente mit der Struktur suppliers/{supplier_id}/contracts/{contract_internal_number}/',
                'pattern' => 'suppliers/{supplier_id}/contracts/{contract_internal_number}',
                'path' => 'suppliers/{supplier_id}/contracts/{contract_internal_number}',
                'placeholders' => [
                    'supplier_id' => 'Lieferanten-ID',
                    'supplier_number' => 'Lieferantennummer',
                    'supplier_name' => 'Lieferantenname (bereinigt)',
                    'contract_internal_number' => 'Interne Vertragsnummer',
                    'contract_number' => 'Vertragsnummer',
                    'contract_name' => 'Vertragsname (bereinigt)',
                    'document_number' => 'Dokumentnummer',
                    'document_name' => 'Dokumentname (bereinigt)',
                    'category' => 'Dokumentkategorie',
                    'date' => 'Aktuelles Datum (YYYY-MM-DD)',
                    'timestamp' => 'Zeitstempel (YYYY-MM-DD_HH-MM-SS)'
                ],
                'example' => 'suppliers/01234567-8901-2345-6789-012345678901/contracts/EV-2024-001',
                'active' => true
            ];
            
                // Aktualisiere die Storage-Einstellung
                DB::table('storage_settings')
                    ->where('id', $setting->id)
                    ->update([
                        'storage_paths' => json_encode($storagePaths)
                    ]);
            }
        }
    }

    /**
     * Entfernt die supplier_contracts Pfadkonfiguration
     */
    private function removeSupplierContractsStoragePath(): void
    {
        $storageSettings = DB::table('storage_settings')->get();
        
        foreach ($storageSettings as $setting) {
            $storagePaths = json_decode($setting->storage_paths, true) ?? [];
            
            // Entferne die supplier_contracts Konfiguration
            unset($storagePaths['supplier_contracts']);
            
            // Aktualisiere die Storage-Einstellung
            DB::table('storage_settings')
                ->where('id', $setting->id)
                ->update([
                    'storage_paths' => json_encode($storagePaths)
                ]);
        }
    }
};