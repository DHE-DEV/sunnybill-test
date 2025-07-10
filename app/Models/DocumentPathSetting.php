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
            'App\Models\SupplierContract' => 'Lieferantenvertrag',
            'App\Models\SupplierContractBilling' => 'Lieferanten-Abrechnung',
            'App\Models\UploadedPdf' => 'PDF-Upload',
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

        // SupplierContract-spezifische Platzhalter
        if ($model instanceof \App\Models\SupplierContract) {
            $replacements['{contract_number}'] = $this->sanitizeValue($model->contract_number ?? 'unknown');
            $replacements['{contract_title}'] = $this->sanitizeValue($model->title ?? 'unknown');
            $replacements['{contract_id}'] = $model->id ?? 'unknown';
            $replacements['{contract_status}'] = $this->sanitizeValue($model->status ?? 'unknown');
            
            // Lieferanten-Informationen vom zugehörigen Lieferanten
            if ($model->supplier) {
                $replacements['{supplier_number}'] = $this->sanitizeValue($model->supplier->supplier_number ?? 'unknown');
                $replacements['{supplier_name}'] = $this->sanitizeValue($model->supplier->company_name ?? 'unknown');
                $replacements['{supplier_id}'] = $model->supplier->id ?? 'unknown';
            }
            
            // Datum-basierte Platzhalter
            if ($model->start_date) {
                $replacements['{contract_start_year}'] = $model->start_date->format('Y');
                $replacements['{contract_start_month}'] = $model->start_date->format('m');
            }
        }

        // SupplierContractBilling-spezifische Platzhalter
        if ($model instanceof \App\Models\SupplierContractBilling) {
            $replacements['{billing_number}'] = $this->sanitizeValue($model->billing_number ?? 'unknown');
            $replacements['{supplier_invoice_number}'] = $this->sanitizeValue($model->supplier_invoice_number ?? 'unknown');
            $replacements['{billing_type}'] = $this->sanitizeValue($model->billing_type ?? 'unknown');
            $replacements['{billing_year}'] = $this->sanitizeValue($model->billing_year ?? 'unknown');
            $replacements['{billing_month}'] = $this->sanitizeValue($model->billing_month ?? 'unknown');
            $replacements['{billing_status}'] = $this->sanitizeValue($model->status ?? 'unknown');
            $replacements['{billing_id}'] = $model->id ?? 'unknown';
            $replacements['{billing_title}'] = $this->sanitizeValue($model->title ?? 'unknown');
            
            // Vertrags- und Lieferanten-Informationen vom zugehörigen Vertrag
            if ($model->supplierContract) {
                $contract = $model->supplierContract;
                $replacements['{contract_number}'] = $this->sanitizeValue($contract->contract_number ?? 'unknown');
                $replacements['{contract_title}'] = $this->sanitizeValue($contract->title ?? 'unknown');
                $replacements['{contract_id}'] = $contract->id ?? 'unknown';
                
                // Lieferanten-Informationen
                if ($contract->supplier) {
                    $replacements['{supplier_number}'] = $this->sanitizeValue($contract->supplier->supplier_number ?? 'unknown');
                    $replacements['{supplier_name}'] = $this->sanitizeValue($contract->supplier->company_name ?? 'unknown');
                    $replacements['{supplier_id}'] = $contract->supplier->id ?? 'unknown';
                }
            }
            
            // Datum-basierte Platzhalter
            if ($model->billing_date) {
                $replacements['{billing_date_year}'] = $model->billing_date->format('Y');
                $replacements['{billing_date_month}'] = $model->billing_date->format('m');
                $replacements['{billing_date_day}'] = $model->billing_date->format('d');
            }
            
            // Formatierte Abrechnungsperiode
            if ($model->billing_year && $model->billing_month) {
                $replacements['{billing_period}'] = $this->sanitizeValue($model->billing_year . '-' . str_pad($model->billing_month, 2, '0', STR_PAD_LEFT));
            }
        }

        // UploadedPdf-spezifische Platzhalter
        if ($model instanceof \App\Models\UploadedPdf) {
            $replacements['{pdf_name}'] = $this->sanitizeValue($model->name ?? 'unknown');
            $replacements['{pdf_id}'] = $model->id ?? 'unknown';
            $replacements['{original_filename}'] = $this->sanitizeValue(pathinfo($model->original_filename ?? 'unknown', PATHINFO_FILENAME));
            $replacements['{analysis_status}'] = $this->sanitizeValue($model->analysis_status ?? 'unknown');
            
            // UUID-Platzhalter
            $uuid = \Illuminate\Support\Str::uuid()->toString();
            $replacements['{file_uuid}'] = $uuid;
            $replacements['{file_uuid_short}'] = substr($uuid, 0, 8);
            
            // Benutzer-Informationen
            if ($model->uploadedBy) {
                $replacements['{uploaded_by_name}'] = $this->sanitizeValue($model->uploadedBy->name ?? 'unknown');
                $replacements['{uploaded_by_id}'] = $model->uploadedBy->id ?? 'unknown';
            } else {
                $replacements['{uploaded_by_name}'] = 'unknown';
                $replacements['{uploaded_by_id}'] = 'unknown';
            }
            
            // Dateigröße in MB
            if ($model->file_size) {
                $replacements['{file_size_mb}'] = round($model->file_size / 1024 / 1024, 2);
            } else {
                $replacements['{file_size_mb}'] = 'unknown';
            }
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

            case 'App\Models\SupplierContract':
                $placeholders = array_merge($placeholders, [
                    'contract_number' => 'Vertragsnummer',
                    'contract_title' => 'Vertragstitel',
                    'contract_id' => 'Vertrags-ID',
                    'contract_status' => 'Vertragsstatus',
                    'supplier_number' => 'Lieferantennummer',
                    'supplier_name' => 'Lieferantenname',
                    'supplier_id' => 'Lieferanten-ID',
                    'contract_start_year' => 'Vertragsbeginn Jahr',
                    'contract_start_month' => 'Vertragsbeginn Monat',
                ]);
                break;

            case 'App\Models\SupplierContractBilling':
                $placeholders = array_merge($placeholders, [
                    'billing_number' => 'Abrechnungsnummer',
                    'supplier_invoice_number' => 'Lieferanten-Rechnungsnummer',
                    'billing_type' => 'Abrechnungstyp',
                    'billing_year' => 'Abrechnungsjahr',
                    'billing_month' => 'Abrechnungsmonat',
                    'billing_period' => 'Abrechnungsperiode (YYYY-MM)',
                    'billing_status' => 'Abrechnungsstatus',
                    'billing_id' => 'Abrechnungs-ID',
                    'billing_title' => 'Abrechnungstitel',
                    'billing_date_year' => 'Abrechnungsdatum Jahr',
                    'billing_date_month' => 'Abrechnungsdatum Monat',
                    'billing_date_day' => 'Abrechnungsdatum Tag',
                    'contract_number' => 'Vertragsnummer (vom zugehörigen Vertrag)',
                    'contract_title' => 'Vertragstitel (vom zugehörigen Vertrag)',
                    'contract_id' => 'Vertrags-ID (vom zugehörigen Vertrag)',
                    'supplier_number' => 'Lieferantennummer (vom zugehörigen Lieferanten)',
                    'supplier_name' => 'Lieferantenname (vom zugehörigen Lieferanten)',
                    'supplier_id' => 'Lieferanten-ID (vom zugehörigen Lieferanten)',
                ]);
                break;

            case 'App\Models\UploadedPdf':
                $placeholders = array_merge($placeholders, [
                    'pdf_name' => 'PDF-Name',
                    'pdf_id' => 'PDF-ID',
                    'original_filename' => 'Original-Dateiname',
                    'uploaded_by_name' => 'Hochgeladen von (Name)',
                    'uploaded_by_id' => 'Hochgeladen von (ID)',
                    'analysis_status' => 'Analyse-Status',
                    'file_size_mb' => 'Dateigröße (MB)',
                    'file_uuid' => 'Datei-UUID (vollständig)',
                    'file_uuid_short' => 'Datei-UUID (kurz, 8 Zeichen)',
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
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => null,
                'path_template' => 'vertraege/{supplier_number}/{contract_number}',
                'description' => 'Standard-Pfad für Dokumente zu Vertragsdaten',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'contracts',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/vertragsdokumente',
                'description' => 'Pfad für Vertragsdokumente und Anhänge',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'correspondence',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/korrespondenz',
                'description' => 'Pfad für Korrespondenz zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContract',
                'category' => 'invoices',
                'path_template' => 'vertraege/{supplier_number}/{contract_number}/abrechnungen/{year}',
                'description' => 'Pfad für Abrechnungen zu Verträgen',
                'placeholders' => ['contract_number', 'contract_title', 'contract_id', 'supplier_number', 'supplier_name', 'year'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => null,
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}',
                'description' => 'Standard-Pfad für Lieferanten-Abrechnungsdokumente',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number', 'billing_year', 'billing_month'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'invoices',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/rechnungen',
                'description' => 'Pfad für Abrechnungs-Rechnungsdokumente',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number', 'supplier_invoice_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'correspondence',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/korrespondenz',
                'description' => 'Pfad für Korrespondenz zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'technical',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/unterlagen',
                'description' => 'Pfad für technische Unterlagen zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'certificates',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/nachweise',
                'description' => 'Pfad für Nachweise und Zertifikate zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'credit_note',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/gutschriften',
                'description' => 'Pfad für Gutschriften zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'statement',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/abrechnungen',
                'description' => 'Pfad für Abrechnungsunterlagen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'supporting_documents',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/belege',
                'description' => 'Pfad für Belege zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\SupplierContractBilling',
                'category' => 'other',
                'path_template' => 'abrechnungen/{supplier_number}/{contract_number}/{billing_period}/sonstiges',
                'description' => 'Pfad für sonstige Dokumente zu Abrechnungen',
                'placeholders' => ['billing_number', 'billing_period', 'supplier_number', 'contract_number'],
            ],
            [
                'documentable_type' => 'App\Models\UploadedPdf',
                'category' => null,
                'path_template' => 'pdf-uploads/{year}/{month}',
                'description' => 'Standard-Pfad für PDF-Uploads (nach Jahr/Monat)',
                'placeholders' => ['pdf_name', 'pdf_id', 'original_filename', 'uploaded_by_name', 'year', 'month', 'file_uuid', 'file_uuid_short'],
            ],
            [
                'documentable_type' => 'App\Models\UploadedPdf',
                'category' => 'analysis',
                'path_template' => 'pdf-uploads/analysiert/{year}/{month}',
                'description' => 'Pfad für analysierte PDF-Uploads',
                'placeholders' => ['pdf_name', 'pdf_id', 'original_filename', 'analysis_status', 'year', 'month', 'file_uuid', 'file_uuid_short'],
            ],
            [
                'documentable_type' => 'App\Models\UploadedPdf',
                'category' => 'user_organized',
                'path_template' => 'pdf-uploads/benutzer/{uploaded_by_name}/{year}',
                'description' => 'Pfad für PDF-Uploads organisiert nach Benutzer',
                'placeholders' => ['uploaded_by_name', 'uploaded_by_id', 'pdf_name', 'year', 'month', 'file_uuid', 'file_uuid_short'],
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
