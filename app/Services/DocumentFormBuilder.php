<?php

namespace App\Services;

use Filament\Forms;
use Filament\Forms\Form;

/**
 * Service zum dynamischen Erstellen von Dokumenten-Upload-Formularen
 */
class DocumentFormBuilder
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public static function make(array $config = []): self
    {
        return new self($config);
    }

    /**
     * Erstellt das Formular basierend auf der Konfiguration
     */
    public function build(Form $form): Form
    {
        return $form->schema($this->getFormSchema());
    }

    /**
     * Erstellt das Formular-Schema
     */
    public function getFormSchema(): array
    {
        $schema = [];

        // Hauptsektion für Upload
        if ($this->config('showSection', true)) {
            $schema[] = Forms\Components\Section::make($this->config('sectionTitle', 'Dokument-Upload'))
                ->schema($this->getUploadFields())
                ->columns($this->config('formColumns', 2));
        } else {
            $schema = $this->getUploadFields();
        }

        return $schema;
    }

    /**
     * Erstellt die Upload-Felder
     */
    protected function getUploadFields(): array
    {
        $fields = [];

        // FileUpload Feld
        $fields[] = $this->createFileUploadField();

        // Name Feld
        $fields[] = $this->createNameField();

        // Kategorie Feld
        if ($this->config('categories')) {
            $fields[] = $this->createCategoryField();
        }

        // Beschreibung Feld
        if ($this->config('showDescription', true)) {
            $fields[] = $this->createDescriptionField();
        }

        // Versteckte Metadaten-Felder
        $fields = array_merge($fields, $this->createHiddenFields());

        return $fields;
    }

    /**
     * Erstellt das FileUpload-Feld
     */
    protected function createFileUploadField(): Forms\Components\FileUpload
    {
        // Bestimme Disk und Directory basierend auf Konfiguration
        $diskName = $this->config('diskName') ?? DocumentStorageService::getDiskName();
        $directory = $this->getUploadDirectory();
        
        $field = Forms\Components\FileUpload::make('path')
            ->label($this->config('fileLabel', 'Datei'))
            ->required($this->config('required', true))
            ->disk($diskName)
            ->directory($directory)
            ->maxSize($this->config('maxSize', 10240))
            ->acceptedFileTypes($this->config('acceptedFileTypes', ['application/pdf']))
            ->afterStateUpdated(function (Forms\Set $set, $state) {
                $this->handleFileUpload($set, $state);
            });

        // Optionale Konfigurationen
        if ($this->config('preserveFilenames', true)) {
            $field->preserveFilenames();
        } else {
            // Wenn preserveFilenames false ist, verwende Zeitstempel-Naming
            if ($this->config('timestampFilenames', true)) {
                $field->getUploadedFileNameForStorageUsing(function ($file) {
                    return $this->generateTimestampedFilename($file->getClientOriginalName());
                });
            }
        }

        if ($this->config('multiple', false)) {
            $field->multiple();
        }

        if ($this->config('image', false)) {
            $field->image()
                ->imageEditor()
                ->imageEditorAspectRatios([
                    '16:9',
                    '4:3',
                    '1:1',
                ]);
        }

        if ($this->config('columnSpanFull', false)) {
            $field->columnSpanFull();
        }

        return $field;
    }

    /**
     * Erstellt das Name-Feld
     */
    protected function createNameField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('name')
            ->label($this->config('nameLabel', 'Dokumentname'))
            ->required($this->config('nameRequired', true))
            ->maxLength($this->config('nameMaxLength', 255))
            ->default(fn ($get) => $get('original_name'))
            ->placeholder($this->config('namePlaceholder', 'Name des Dokuments'));
    }

    /**
     * Erstellt das Kategorie-Feld
     */
    protected function createCategoryField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('category')
            ->label($this->config('categoryLabel', 'Kategorie'))
            ->options($this->config('categories', []))
            ->searchable($this->config('categorySearchable', true))
            ->required($this->config('categoryRequired', false))
            ->default($this->config('defaultCategory'));
    }

    /**
     * Erstellt das Beschreibung-Feld
     */
    protected function createDescriptionField(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('description')
            ->label($this->config('descriptionLabel', 'Beschreibung'))
            ->rows($this->config('descriptionRows', 3))
            ->maxLength($this->config('descriptionMaxLength', 1000))
            ->placeholder($this->config('descriptionPlaceholder', 'Optionale Beschreibung des Dokuments'))
            ->columnSpanFull();
    }

    /**
     * Erstellt versteckte Felder für Metadaten
     */
    protected function createHiddenFields(): array
    {
        return [
            Forms\Components\Hidden::make('original_name'),
            Forms\Components\Hidden::make('disk'),
            Forms\Components\Hidden::make('size'),
            Forms\Components\Hidden::make('mime_type'),
            Forms\Components\Hidden::make('uploaded_by'),
        ];
    }

    /**
     * Behandelt den Datei-Upload und extrahiert Metadaten
     */
    protected function handleFileUpload(Forms\Set $set, $state): void
    {
        if ($state && is_array($state) && !empty($state)) {
            // Sichere Prüfung auf Index 0
            $filePath = $state[0] ?? null;
            
            if ($filePath) {
                try {
                    $metadata = DocumentStorageService::extractFileMetadata($filePath);
                    
                    // Setze die Metadaten in das Formular (außer 'path')
                    foreach ($metadata as $key => $value) {
                        if ($key !== 'path') {
                            $set($key, $value);
                        }
                    }

                    // Auto-fill Name wenn leer
                    if ($this->config('autoFillName', true)) {
                        $set('name', $metadata['original_name'] ?? '');
                    }
                } catch (\Exception $e) {
                    \Log::error('Fehler beim Extrahieren der Metadaten in DocumentFormBuilder', [
                        'file_path' => $filePath,
                        'error' => $e->getMessage(),
                        'state' => $state
                    ]);
                }
            }
        } elseif ($state) {
            // Fallback für String-Werte (falls Filament manchmal Strings liefert)
            if (is_string($state)) {
                try {
                    $metadata = DocumentStorageService::extractFileMetadata($state);
                    
                    // Setze die Metadaten in das Formular (außer 'path')
                    foreach ($metadata as $key => $value) {
                        if ($key !== 'path') {
                            $set($key, $value);
                        }
                    }

                    // Auto-fill Name wenn leer
                    if ($this->config('autoFillName', true)) {
                        $set('name', $metadata['original_name'] ?? '');
                    }
                } catch (\Exception $e) {
                    \Log::error('Fehler beim Extrahieren der Metadaten in DocumentFormBuilder (String)', [
                        'file_path' => $state,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                \Log::warning('Unerwarteter $state Typ in DocumentFormBuilder::handleFileUpload', [
                    'state' => $state,
                    'type' => gettype($state)
                ]);
            }
        }
    }

    /**
     * Bestimmt das Upload-Verzeichnis basierend auf der Konfiguration
     */
    protected function getUploadDirectory(): string
    {
        // Prüfe ob storageDirectory direkt gesetzt ist (von DocumentUploadConfig)
        if ($storageDirectory = $this->config('storageDirectory')) {
            return $storageDirectory;
        }
        
        // Fallback auf alte directory-Konfiguration
        $directory = $this->config('directory', 'documents');
        return DocumentStorageService::getUploadDirectory($directory);
    }

    /**
     * Hilfsmethode zum Abrufen von Konfigurationswerten
     */
    protected function config(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Erstellt ein einfaches Upload-Feld für schnelle Verwendung
     */
    public static function quickUpload(array $options = []): Forms\Components\FileUpload
    {
        $builder = new self($options);
        return $builder->createFileUploadField();
    }

    /**
     * Erstellt ein vollständiges Upload-Schema für schnelle Verwendung
     */
    public static function quickSchema(array $options = []): array
    {
        $builder = new self($options);
        return $builder->getUploadFields();
    }

    /**
     * Generiert einen Dateinamen mit Zeitstempel zur Vermeidung von Überschreibungen
     * Format: originalname_YYYY-MM-DD_HH-MM-SS.extension
     */
    protected function generateTimestampedFilename(string $originalFilename): string
    {
        $pathInfo = pathinfo($originalFilename);
        $name = $pathInfo['filename'] ?? 'document';
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        
        // Bereinige den ursprünglichen Namen
        $cleanName = preg_replace('/[^\w\-_.]/', '-', $name);
        $cleanName = preg_replace('/-+/', '-', $cleanName);
        $cleanName = trim($cleanName, '-');
        
        if (empty($cleanName)) {
            $cleanName = 'document';
        }
        
        // Generiere Zeitstempel im Format YYYY-MM-DD_HH-MM-SS
        // Laravel's now() berücksichtigt automatisch die in config/app.php gesetzte Timezone
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return $cleanName . '_' . $timestamp . $extension;
    }
}