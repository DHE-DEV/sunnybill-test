<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
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
            $disk = 'local'; // Standard-Disk
            
            // Prüfe ob die Datei existiert
            if (Storage::disk($disk)->exists($filePath)) {
                // Extrahiere den ursprünglichen Dateinamen aus dem Pfad
                $originalName = basename($filePath);
                
                // Hole die Dateigröße
                $size = Storage::disk($disk)->size($filePath);
                
                // Bestimme den MIME-Type basierend auf der Dateierweiterung
                $mimeType = Storage::disk($disk)->mimeType($filePath);
                
                // Füge die fehlenden Felder hinzu
                $data['original_name'] = $originalName;
                $data['disk'] = $disk;
                $data['size'] = $size;
                $data['mime_type'] = $mimeType;
                $data['uploaded_by'] = auth()->id();
            }
        }

        return $data;
    }
}