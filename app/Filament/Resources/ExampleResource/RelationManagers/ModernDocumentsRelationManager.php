<?php

namespace App\Filament\Resources\ExampleResource\RelationManagers;

use App\Traits\DocumentUploadTrait;
use App\Services\DocumentUploadConfig;
use Filament\Resources\RelationManagers\RelationManager;

/**
 * Beispiel für einen modernisierten DocumentsRelationManager
 * mit dem wiederverwendbaren Upload-Modul
 */
class ModernDocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;

    protected static string $relationship = 'documents';
    protected static ?string $title = 'Dokumente';
    protected static ?string $modelLabel = 'Dokument';
    protected static ?string $pluralModelLabel = 'Dokumente';
    protected static ?string $icon = 'heroicon-o-document';

    /**
     * Konfiguration für das Upload-Modul
     * 
     * Diese Methode überschreibt die Standard-Konfiguration
     * und passt sie an die spezifischen Anforderungen an
     */
    protected function getDocumentUploadConfig(): array
    {
        // Beispiel 1: Einfache Konfiguration mit Array
        return [
            'directory' => 'example-documents',
            'categories' => [
                'contract' => 'Vertrag',
                'invoice' => 'Rechnung',
                'certificate' => 'Zertifikat',
                'photo' => 'Foto',
                'other' => 'Sonstiges',
            ],
            'maxSize' => 51200, // 50MB
            'acceptedFileTypes' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'defaultCategory' => 'other',
            'showStats' => true,
            'enableDragDrop' => true,
            'createButtonLabel' => 'Neues Dokument',
            'emptyStateDescription' => 'Laden Sie Ihr erstes Dokument hoch.',
        ];

        // Beispiel 2: Verwendung der DocumentUploadConfig-Klasse
        // return DocumentUploadConfig::forDocuments()
        //     ->set('directory', 'example-documents')
        //     ->set('maxSize', 20480)
        //     ->set('showStats', true)
        //     ->set('enableDragDrop', true)
        //     ->toArray();

        // Beispiel 3: Vordefinierte Konfiguration für Bilder
        // return DocumentUploadConfig::forImages()
        //     ->set('directory', 'example-images')
        //     ->toArray();

        // Beispiel 4: Minimale Konfiguration
        // return DocumentUploadConfig::minimal()
        //     ->set('directory', 'example-minimal')
        //     ->toArray();

        // Beispiel 5: Vollständige Konfiguration mit allen Features
        // return DocumentUploadConfig::full()
        //     ->set('directory', 'example-full')
        //     ->toArray();
    }

    /**
     * Optional: Custom Hooks für spezielle Logik
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Rufe die Standard-Verarbeitung auf
        $data = $this->processDocumentUploadData($data);

        // Füge custom Logik hinzu
        $data['custom_field'] = 'example_value';
        
        // Beispiel: Automatische Kategorisierung basierend auf Dateiname
        if (!isset($data['category']) || !$data['category']) {
            $filename = strtolower($data['original_name'] ?? '');
            
            if (str_contains($filename, 'vertrag') || str_contains($filename, 'contract')) {
                $data['category'] = 'contract';
            } elseif (str_contains($filename, 'rechnung') || str_contains($filename, 'invoice')) {
                $data['category'] = 'invoice';
            } elseif (str_contains($filename, 'foto') || str_contains($filename, 'bild')) {
                $data['category'] = 'photo';
            }
        }

        return $data;
    }

    /**
     * Optional: Custom Validierung
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->processDocumentUploadData($data);

        // Custom Validierung
        if (isset($data['category']) && $data['category'] === 'contract') {
            // Spezielle Validierung für Verträge
            if (!str_contains(strtolower($data['name']), 'vertrag')) {
                $data['name'] = 'Vertrag - ' . $data['name'];
            }
        }

        return $data;
    }
}
