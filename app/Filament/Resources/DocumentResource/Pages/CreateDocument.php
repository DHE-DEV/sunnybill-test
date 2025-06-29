<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\StorageSetting;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Wenn eine Datei hochgeladen wurde, extrahiere die Metadaten
        if (isset($data['path']) && $data['path']) {
            $filePath = $data['path'];
            $disk = 'documents'; // Verwende immer die 'documents' Disk (wird dynamisch konfiguriert)
            
            // Prüfe ob die Datei existiert
            $diskInstance = Storage::disk($disk);
            if ($diskInstance->exists($filePath)) {
                // Der ursprüngliche Dateiname wird bereits durch storeFileNamesIn('original_name') gesetzt
                // Falls nicht gesetzt, extrahiere aus dem Pfad
                if (empty($data['original_name'])) {
                    $data['original_name'] = basename($filePath);
                }
                
                // Hole die Dateigröße
                $size = $diskInstance->size($filePath);
                
                // Bestimme den MIME-Type basierend auf der Dateierweiterung
                $mimeType = $diskInstance->mimeType($filePath);
                
                // Füge die fehlenden Felder hinzu
                $data['disk'] = $disk;
                $data['size'] = $size;
                $data['mime_type'] = $mimeType;
                $data['uploaded_by'] = auth()->id();
                
                // Debug-Log für Storage-Verwendung mit spezifischer Verzeichnisstruktur
                \Log::info('Document Upload Storage Info', [
                    'disk' => $disk,
                    'file_path' => $filePath,
                    'original_name' => $data['original_name'],
                    'size' => $size,
                    'documentable_type' => $data['documentable_type'] ?? 'unknown',
                    'documentable_id' => $data['documentable_id'] ?? 'unknown',
                    'storage_driver' => StorageSetting::current()?->storage_driver ?? 'none',
                    'directory_structure' => dirname($filePath),
                    'specific_directory' => $this->getSpecificDirectoryInfo($data)
                ]);
            }
        }

        return $data;
    }

    /**
     * Erstellt Debug-Informationen über die spezifische Verzeichnisstruktur
     */
    private function getSpecificDirectoryInfo(array $data): array
    {
        $type = $data['documentable_type'] ?? null;
        $id = $data['documentable_id'] ?? null;
        
        if (!$type || !$id) {
            return ['info' => 'Keine spezifische Zuordnung'];
        }
        
        return match ($type) {
            'App\Models\Customer' => [
                'type' => 'Kunde',
                'identifier' => \App\Models\Customer::find($id)?->customer_number ?? 'Unbekannt',
                'name' => \App\Models\Customer::find($id)?->company_name ?? 'Unbekannt'
            ],
            'App\Models\SolarPlant' => [
                'type' => 'Solaranlage',
                'identifier' => \App\Models\SolarPlant::find($id)?->plant_number ?? \App\Models\SolarPlant::find($id)?->name ?? 'Unbekannt',
                'name' => \App\Models\SolarPlant::find($id)?->name ?? 'Unbekannt',
                'location' => \App\Models\SolarPlant::find($id)?->location ?? 'Unbekannt'
            ],
            'App\Models\Task' => [
                'type' => 'Aufgabe',
                'identifier' => \App\Models\Task::find($id)?->task_number ?? 'Unbekannt',
                'title' => \App\Models\Task::find($id)?->title ?? 'Unbekannt'
            ],
            'App\Models\Invoice' => [
                'type' => 'Rechnung',
                'identifier' => \App\Models\Invoice::find($id)?->invoice_number ?? 'Unbekannt'
            ],
            'App\Models\Supplier' => [
                'type' => 'Lieferant',
                'identifier' => \App\Models\Supplier::find($id)?->supplier_number ?? 'Unbekannt',
                'name' => \App\Models\Supplier::find($id)?->company_name ?? 'Unbekannt'
            ],
            default => ['info' => 'Unbekannter Typ: ' . $type]
        };
    }
}