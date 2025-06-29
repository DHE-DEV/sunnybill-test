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
                
                // Debug-Log für Storage-Verwendung mit Verzeichnisstruktur
                \Log::info('Document Upload Storage Info', [
                    'disk' => $disk,
                    'file_path' => $filePath,
                    'original_name' => $data['original_name'],
                    'size' => $size,
                    'documentable_type' => $data['documentable_type'] ?? 'unknown',
                    'storage_driver' => StorageSetting::current()?->storage_driver ?? 'none',
                    'directory_structure' => dirname($filePath)
                ]);
            }
        }

        return $data;
    }
}