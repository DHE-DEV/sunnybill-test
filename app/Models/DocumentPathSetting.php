<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentPathSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'documentable_type',
        'category',
        'path_template',
        'placeholders',
        'description',
        'is_active',
        'filename_strategy',
        'filename_template',
        'filename_prefix',
        'filename_suffix',
        'preserve_extension',
        'sanitize_filename',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Verfügbare Dokumenttypen
     */
    public static function getDocumentableTypes(): array
    {
        return [
            'App\Models\SolarPlant' => 'Solaranlage',
            'App\Models\Customer' => 'Kunde',
            'App\Models\Task' => 'Aufgabe',
            'App\Models\Invoice' => 'Rechnung',
            'App\Models\Supplier' => 'Lieferant',
        ];
    }

    /**
     * Verfügbare Kategorien
     */
    public static function getCategories(): array
    {
        return [
            'planning' => 'Planung',
            'permits' => 'Genehmigungen',
            'installation' => 'Installation',
            'maintenance' => 'Wartung',
            'invoices' => 'Rechnungen',
            'certificates' => 'Zertifikate',
            'contracts' => 'Verträge',
            'correspondence' => 'Korrespondenz',
            'technical' => 'Technische Unterlagen',
            'photos' => 'Fotos',
        ];
    }

    /**
     * Pfadkonfiguration für einen bestimmten Typ und Kategorie abrufen
     */
    public static function getPathConfig(string $documentableType, ?string $category = null): ?self
    {
        $query = static::where('documentable_type', $documentableType)
            ->where('is_active', true);

        if ($category) {
            $query->where('category', $category);
        }

        return $query->first();
    }

    /**
     * Pfad basierend auf Template und Model generieren
     */
    public function generatePath($model = null, array $additionalData = []): string
    {
        $template = $this->path_template;
        
        // Platzhalter ersetzen
        $replacements = $this->buildPlaceholderReplacements($model, $additionalData);
        
        $resolvedPath = $template;
        foreach ($replacements as $placeholder => $value) {
            $resolvedPath = str_replace($placeholder, $value, $resolvedPath);
        }

        // Pfad bereinigen
        return $this->sanitizePath($resolvedPath);
    }

    /**
     * Platzhalter-Ersetzungen erstellen
     */
    private function buildPlaceholderReplacements($model = null, array $additionalData = []): array
    {
        $replacements = [];

        // Standard-Platzhalter
        $replacements['{timestamp}'] = now()->format('Y-m-d_H-i-s');
        $replacements['{date}'] = now()->format('Y-m-d');
        $replacements['{year}'] = now()->format('Y');
        $replacements['{month}'] = now()->format('m');
        $replacements['{day}'] = now()->format('d');

        // Model-spezifische Platzhalter
        if ($model) {
            $this->addModelPlaceholders($replacements, $model);
        }

        // Zusätzliche Daten
        foreach ($additionalData as $key => $value) {
            $replacements['{' . $key . '}'] = $this->sanitizeValue($value);
        }

        return $replacements;
    }

    /**
     * Model-spezifische Platzhalter hinzufügen
     */
    private function addModelPlaceholders(array &$replacements, $model): void
    {
        // Supplier-spezifische Platzhalter
        if ($model instanceof \App\Models\Supplier) {
            $replacements['{supplier_number}'] = $this->sanitizeValue($model->supplier_number ?? 'unknown');
            $replacements['{supplier_name}'] = $this->sanitizeValue($model->company_name ?? 'unknown');
            $replacements['{supplier_id}'] = $model->id ?? 'unknown';
        }

        // Customer-spezifische Platzhalter
        if ($model instanceof \App\Models\Customer) {
            $replacements['{customer_number}'] = $this->sanitizeValue($model->customer_number ?? 'unknown');
            $replacements['{customer_name}'] = $this->sanitizeValue($model->company_name ?? 'unknown');
            $replacements['{customer_id}'] = $model->id ?? 'unknown';
        }

        // SolarPlant-spezifische Platzhalter
        if ($model instanceof \App\Models\SolarPlant) {
            $replacements['{plant_number}'] = $this->sanitizeValue($model->plant_number ?? 'unknown');
            $replacements['{plant_name}'] = $this->sanitizeValue($model->name ?? 'unknown');
            $replacements['{plant_id}'] = $model->id ?? 'unknown';
        }

        // Task-spezifische Platzhalter
        if ($model instanceof \App\Models\Task) {
            $replacements['{task_number}'] = $this->sanitizeValue($model->task_number ?? 'unknown');
            $replacements['{task_title}'] = $this->sanitizeValue($model->title ?? 'unknown');
            $replacements['{task_id}'] = $model->id ?? 'unknown';
        }

        // Invoice-spezifische Platzhalter
        if ($model instanceof \App\Models\Invoice) {
            $replacements['{invoice_number}'] = $this->sanitizeValue($model->invoice_number ?? 'unknown');
            $replacements['{invoice_id}'] = $model->id ?? 'unknown';
        }

        // Allgemeine Model-Platzhalter
        $replacements['{model_id}'] = $model->id ?? 'unknown';
        $replacements['{model_type}'] = class_basename($model);
    }

    /**
     * Wert für Pfad-Verwendung bereinigen
     */
    private function sanitizeValue($value): string
    {
        if ($value === null) {
            return 'unknown';
        }

        $value = (string) $value;
        
        // Gefährliche Zeichen entfernen/ersetzen
        $value = preg_replace('/[^\w\-_.]/', '-', $value);
        
        // Mehrfache Bindestriche reduzieren
        $value = preg_replace('/-+/', '-', $value);
        
        // Führende/nachfolgende Bindestriche entfernen
        $value = trim($value, '-');
        
        // Leer-String vermeiden
        if (empty($value)) {
            return 'unknown';
        }

        return $value;
    }

    /**
     * Pfad bereinigen und normalisieren
     */
    private function sanitizePath(string $path): string
    {
        // Backslashes zu Forward-Slashes
        $path = str_replace('\\', '/', $path);
        
        // Doppelte Slashes entfernen
        $path = preg_replace('/\/+/', '/', $path);
        
        // Führende/nachfolgende Slashes entfernen
        $path = trim($path, '/');
        
        return $path;
    }

    /**
     * Verfügbare Platzhalter für einen Dokumenttyp abrufen
     */
    public static function getAvailablePlaceholders(string $documentableType): array
    {
        $placeholders = [
            // Standard-Platzhalter
            'timestamp' => 'Aktueller Zeitstempel (Y-m-d_H-i-s)',
            'date' => 'Aktuelles Datum (Y-m-d)',
            'year' => 'Aktuelles Jahr',
            'month' => 'Aktueller Monat',
            'day' => 'Aktueller Tag',
        ];

        // Typ-spezifische Platzhalter
        switch ($documentableType) {
            case 'App\Models\Supplier':
                $placeholders = array_merge($placeholders, [
                    'supplier_number' => 'Lieferantennummer',
                    'supplier_name' => 'Lieferantenname',
                    'supplier_id' => 'Lieferanten-ID',
                ]);
                break;

            case 'App\Models\Customer':
                $placeholders = array_merge($placeholders, [
                    'customer_number' => 'Kundennummer',
                    'customer_name' => 'Kundenname',
                    'customer_id' => 'Kunden-ID',
                ]);
                break;

            case 'App\Models\SolarPlant':
                $placeholders = array_merge($placeholders, [
                    'plant_number' => 'Anlagennummer',
                    'plant_name' => 'Anlagenname',
                    'plant_id' => 'Anlagen-ID',
                ]);
                break;

            case 'App\Models\Task':
                $placeholders = array_merge($placeholders, [
                    'task_number' => 'Aufgabennummer',
                    'task_title' => 'Aufgabentitel',
                    'task_id' => 'Aufgaben-ID',
                ]);
                break;

            case 'App\Models\Invoice':
                $placeholders = array_merge($placeholders, [
                    'invoice_number' => 'Rechnungsnummer',
                    'invoice_id' => 'Rechnungs-ID',
                ]);
                break;
        }

        return $placeholders;
    }

    /**
     * Standard-Pfadkonfigurationen erstellen
     */
    public static function createDefaults(): void
    {
        $defaults = [
            [
                'documentable_type' => 'App\Models\SolarPlant',
                'category' => null,
                'path_template' => 'solaranlagen/{plant_number}',
                'description' => 'Standard-Pfad für Solaranlagen-Dokumente',
                'placeholders' => ['plant_number', 'plant_name', 'plant_id'],
            ],
            [
                'documentable_type' => 'App\Models\Customer',
                'category' => null,
                'path_template' => 'kunden/{customer_number}',
                'description' => 'Standard-Pfad für Kunden-Dokumente',
                'placeholders' => ['customer_number', 'customer_name', 'customer_id'],
            ],
            [
                'documentable_type' => 'App\Models\Task',
                'category' => null,
                'path_template' => 'aufgaben/{task_number}',
                'description' => 'Standard-Pfad für Aufgaben-Dokumente',
                'placeholders' => ['task_number', 'task_title', 'task_id'],
            ],
            [
                'documentable_type' => 'App\Models\Invoice',
                'category' => null,
                'path_template' => 'rechnungen/{invoice_number}',
                'description' => 'Standard-Pfad für Rechnungs-Dokumente',
                'placeholders' => ['invoice_number', 'invoice_id'],
            ],
            [
                'documentable_type' => 'App\Models\Supplier',
                'category' => null,
                'path_template' => 'lieferanten/{supplier_number}',
                'description' => 'Standard-Pfad für Lieferanten-Dokumente',
                'placeholders' => ['supplier_number', 'supplier_name', 'supplier_id'],
            ],
            [
                'documentable_type' => 'App\Models\Supplier',
                'category' => 'contracts',
                'path_template' => 'lieferanten/{supplier_number}/vertraege/{contract_internal_number}',
                'description' => 'Pfad für Lieferanten-Verträge',
                'placeholders' => ['supplier_number', 'supplier_name', 'supplier_id', 'contract_internal_number'],
            ],
        ];

        foreach ($defaults as $default) {
            static::updateOrCreate(
                [
                    'documentable_type' => $default['documentable_type'],
                    'category' => $default['category'],
                ],
                array_merge($default, ['is_active' => true])
            );
        }
    }

    /**
     * Generiert einen Dateinamen basierend auf der Konfiguration
     */
    public function generateFilename(string $originalFilename, $model = null): string
    {
        $pathInfo = pathinfo($originalFilename);
        $extension = $pathInfo['extension'] ?? '';
        $basename = $pathInfo['filename'] ?? $originalFilename;
        
        $filename = '';
        
        switch ($this->filename_strategy) {
            case 'random':
                $filename = $this->generateRandomFilename();
                break;
                
            case 'template':
                $filename = $this->generateTemplateFilename($model);
                break;
                
            case 'original':
            default:
                $filename = $basename;
                break;
        }
        
        // Präfix hinzufügen (mit Platzhalter-Ersetzung)
        if ($this->filename_prefix) {
            $prefix = $this->filename_prefix;
            $replacements = $this->buildPlaceholderReplacements($model);
            
            foreach ($replacements as $placeholder => $value) {
                $prefix = str_replace($placeholder, $value, $prefix);
            }
            
            $filename = $prefix . $filename;
        }
        
        // Suffix hinzufügen (mit Platzhalter-Ersetzung)
        if ($this->filename_suffix) {
            $suffix = $this->filename_suffix;
            $replacements = $this->buildPlaceholderReplacements($model);
            
            foreach ($replacements as $placeholder => $value) {
                $suffix = str_replace($placeholder, $value, $suffix);
            }
            
            $filename = $filename . $suffix;
        }
        
        // Dateinamen bereinigen
        if ($this->sanitize_filename) {
            $filename = $this->sanitizeFilename($filename);
        }
        
        // Dateierweiterung hinzufügen
        if ($this->preserve_extension && $extension) {
            $filename .= '.' . $extension;
        }
        
        return $filename;
    }

    /**
     * Generiert einen zufälligen Dateinamen
     */
    private function generateRandomFilename(): string
    {
        return uniqid() . '_' . now()->format('YmdHis');
    }

    /**
     * Generiert einen Dateinamen basierend auf einem Template
     */
    private function generateTemplateFilename($model): string
    {
        if (!$this->filename_template || !$model) {
            return $this->generateRandomFilename();
        }
        
        $filename = $this->filename_template;
        $replacements = $this->buildPlaceholderReplacements($model);
        
        foreach ($replacements as $placeholder => $value) {
            $filename = str_replace($placeholder, $value, $filename);
        }
        
        return $filename;
    }

    /**
     * Bereinigt einen Dateinamen von problematischen Zeichen
     */
    private function sanitizeFilename(string $filename): string
    {
        // Entferne oder ersetze problematische Zeichen
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Entferne mehrfache Unterstriche
        $filename = preg_replace('/_+/', '_', $filename);
        
        // Entferne führende und nachfolgende Unterstriche
        $filename = trim($filename, '_');
        
        return $filename;
    }

    /**
     * Verfügbare Dateinamen-Strategien
     */
    public static function getFilenameStrategies(): array
    {
        return [
            'original' => 'Original-Dateiname verwenden',
            'random' => 'Zufälligen Dateinamen generieren',
            'template' => 'Template-basierten Dateinamen generieren',
        ];
    }
}