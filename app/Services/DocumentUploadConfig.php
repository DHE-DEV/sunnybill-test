<?php

namespace App\Services;

/**
 * Konfigurationsklasse für Dokumenten-Upload-Module
 * 
 * Bietet typisierte Konfiguration und Validierung für alle Upload-Komponenten
 */
class DocumentUploadConfig
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaults(), $config);
        $this->validate();
    }

    /**
     * Standard-Konfiguration
     */
    protected function getDefaults(): array
    {
        return [
            // Upload-Einstellungen
            'directory' => 'documents',
            'pathType' => null, // Für dynamische Pfad-Generierung
            'model' => null, // Model-Instanz für Platzhalter
            'additionalData' => [], // Zusätzliche Daten für Platzhalter
            'maxSize' => 10240, // 10MB in KB
            'preserveFilenames' => true,
            'timestampFilenames' => false,
            'multiple' => false,
            'required' => true,
            'acceptedFileTypes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'application/zip',
                'application/x-rar-compressed',
            ],

            // Kategorien
            'categories' => [
                'contract' => 'Vertrag',
                'invoice' => 'Rechnung',
                'certificate' => 'Zertifikat',
                'manual' => 'Handbuch',
                'photo' => 'Foto',
                'plan' => 'Plan/Zeichnung',
                'report' => 'Bericht',
                'correspondence' => 'Korrespondenz',
                'other' => 'Sonstiges',
            ],
            'categoryColors' => [
                'contract' => 'success',
                'invoice' => 'warning',
                'certificate' => 'info',
                'manual' => 'gray',
                'photo' => 'purple',
                'plan' => 'blue',
                'report' => 'orange',
                'correspondence' => 'green',
                'other' => 'gray',
            ],
            'defaultCategory' => null,
            'categoryRequired' => false,
            'categorySearchable' => true,

            // Formular-Einstellungen
            'showSection' => true,
            'sectionTitle' => 'Dokument-Upload',
            'formColumns' => 2,
            'autoFillName' => true,
            'nameRequired' => true,
            'nameMaxLength' => 255,
            'showDescription' => true,
            'descriptionRows' => 3,
            'descriptionMaxLength' => 1000,

            // Tabellen-Einstellungen
            'showIcon' => true,
            'showCategory' => true,
            'showSize' => true,
            'showMimeType' => false,
            'showUploadedBy' => true,
            'showCreatedAt' => true,
            'categoryBadge' => true,
            'nameSearchable' => true,
            'nameSortable' => true,
            'nameWeight' => 'bold',
            'defaultSort' => ['created_at', 'desc'],

            // Aktionen
            'enableCreate' => true,
            'showPreview' => true,
            'showDownload' => true,
            'showView' => true,
            'showEdit' => true,
            'showDelete' => true,
            'groupActions' => true,
            'enableBulkActions' => true,
            'enableBulkDelete' => true,

            // Filter
            'enableCategoryFilter' => true,
            'enableDateFilters' => true,

            // UI-Einstellungen
            'modalWidth' => '4xl',
            'title' => 'Dokumente',
            'createButtonLabel' => 'Dokument hinzufügen',
            'createButtonIcon' => 'heroicon-o-plus',
            'emptyStateHeading' => 'Keine Dokumente vorhanden',
            'emptyStateDescription' => 'Fügen Sie das erste Dokument hinzu.',
            'emptyStateIcon' => 'heroicon-o-document',

            // Labels
            'fileLabel' => 'Datei',
            'nameLabel' => 'Dokumentname',
            'categoryLabel' => 'Kategorie',
            'descriptionLabel' => 'Beschreibung',
            'sizeLabel' => 'Größe',
            'mimeTypeLabel' => 'Typ',
            'uploadedByLabel' => 'Hochgeladen von',
            'createdAtLabel' => 'Erstellt',
            'previewLabel' => 'Vorschau',
            'downloadLabel' => 'Download',
            'actionsLabel' => 'Aktionen',

            // Erweiterte Features
            'enableDragDrop' => false,
            'showStats' => false,
            'enableVersioning' => false,
            'enableTags' => false,
            'enableComments' => false,

            // Validierung
            'validateFileContent' => false,
            'scanForViruses' => false,
            'extractText' => false,
            'generateThumbnails' => false,

            // Hooks
            'beforeUpload' => null,
            'afterUpload' => null,
            'beforeDelete' => null,
            'afterDelete' => null,
        ];
    }

    /**
     * Validiert die Konfiguration
     */
    protected function validate(): void
    {
        // Validiere maxSize
        if (!is_numeric($this->config['maxSize']) || $this->config['maxSize'] <= 0) {
            throw new \InvalidArgumentException('maxSize muss eine positive Zahl sein');
        }

        // Validiere acceptedFileTypes
        if (!is_array($this->config['acceptedFileTypes']) || empty($this->config['acceptedFileTypes'])) {
            throw new \InvalidArgumentException('acceptedFileTypes muss ein nicht-leeres Array sein');
        }

        // Validiere categories
        if (!is_array($this->config['categories'])) {
            throw new \InvalidArgumentException('categories muss ein Array sein');
        }

        // Validiere directory
        if (!is_string($this->config['directory']) || empty($this->config['directory'])) {
            throw new \InvalidArgumentException('directory muss ein nicht-leerer String sein');
        }

        // Validiere defaultSort
        if (!is_array($this->config['defaultSort']) || count($this->config['defaultSort']) !== 2) {
            throw new \InvalidArgumentException('defaultSort muss ein Array mit [column, direction] sein');
        }
    }

    /**
     * Gibt einen Konfigurationswert zurück
     */
    public function get(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Setzt einen Konfigurationswert
     */
    public function set(string $key, $value): self
    {
        data_set($this->config, $key, $value);
        return $this;
    }

    /**
     * Merged zusätzliche Konfiguration
     */
    public function merge(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        $this->validate();
        return $this;
    }

    /**
     * Gibt die komplette Konfiguration zurück
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * Erstellt eine Konfiguration für spezifische Anwendungsfälle
     */
    public static function forImages(): self
    {
        return new self([
            'acceptedFileTypes' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
            ],
            'categories' => [
                'photo' => 'Foto',
                'screenshot' => 'Screenshot',
                'diagram' => 'Diagramm',
                'logo' => 'Logo',
                'other' => 'Sonstiges',
            ],
            'generateThumbnails' => true,
            'showPreview' => true,
            'enableDragDrop' => true,
        ]);
    }

    /**
     * Erstellt eine Konfiguration für Dokumente
     */
    public static function forDocuments(): self
    {
        return new self([
            'acceptedFileTypes' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ],
            'extractText' => true,
            'validateFileContent' => true,
        ]);
    }

    /**
     * Erstellt eine Konfiguration für Archive
     */
    public static function forArchives(): self
    {
        return new self([
            'acceptedFileTypes' => [
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/gzip',
            ],
            'maxSize' => 102400, // 100MB
            'categories' => [
                'backup' => 'Backup',
                'export' => 'Export',
                'import' => 'Import',
                'archive' => 'Archiv',
                'other' => 'Sonstiges',
            ],
        ]);
    }

    /**
     * Erstellt eine minimale Konfiguration
     */
    public static function minimal(): self
    {
        return new self([
            'showSection' => false,
            'showDescription' => false,
            'showIcon' => false,
            'showUploadedBy' => false,
            'showCreatedAt' => false,
            'enableDateFilters' => false,
            'enableBulkActions' => false,
            'groupActions' => false,
            'categories' => [],
        ]);
    }

    /**
     * Erstellt eine vollständige Konfiguration mit allen Features
     */
    public static function full(): self
    {
        return new self([
            'enableDragDrop' => true,
            'showStats' => true,
            'enableVersioning' => true,
            'enableTags' => true,
            'enableComments' => true,
            'validateFileContent' => true,
            'extractText' => true,
            'generateThumbnails' => true,
        ]);
    }

    /**
     * Magic setter für einfachen Zugriff
     */
    public function __set(string $key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Magic isset für einfachen Zugriff
     */
    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Dynamisches Upload-Verzeichnis basierend auf Konfiguration abrufen
     */
    public function getStorageDirectory(): string
    {
        // Wenn pathType gesetzt ist, verwende dynamische Pfad-Generierung
        if ($this->config['pathType']) {
            return DocumentStorageService::getUploadDirectoryForModel(
                $this->config['pathType'],
                $this->config['model'],
                $this->config['additionalData']
            );
        }

        // Fallback auf statisches directory
        return $this->config['directory'];
    }

    /**
     * Disk-Name für Storage abrufen
     */
    public function getDiskName(): string
    {
        return DocumentStorageService::getDiskName();
    }

    /**
     * Pfad-Typ für dynamische Generierung setzen
     */
    public function setPathType(string $pathType, $model = null, array $additionalData = []): self
    {
        $this->config['pathType'] = $pathType;
        $this->config['model'] = $model;
        $this->config['additionalData'] = $additionalData;
        return $this;
    }

    /**
     * Model für Platzhalter-Ersetzung setzen
     */
    public function setModel($model): self
    {
        $this->config['model'] = $model;
        return $this;
    }

    /**
     * Zusätzliche Daten für Platzhalter setzen
     */
    public function setAdditionalData(array $data): self
    {
        $this->config['additionalData'] = array_merge($this->config['additionalData'], $data);
        return $this;
    }

    /**
     * Pfad-Vorschau für aktuell konfigurierte Einstellungen
     */
    public function previewPath(): array
    {
        if ($this->config['pathType']) {
            return DocumentStorageService::previewPath(
                $this->config['pathType'],
                $this->config['model'],
                $this->config['additionalData']
            );
        }

        return [
            'resolved_path' => $this->config['directory'],
            'template' => 'Statischer Pfad',
            'placeholders_used' => [],
            'is_fallback' => false
        ];
    }

    /**
     * Erstellt eine Konfiguration für Supplier-Dokumente
     */
    public static function forSuppliers($supplier = null): self
    {
        return new self([
            'pathType' => 'suppliers',
            'model' => $supplier,
            'title' => 'Lieferanten-Dokumente',
            'sectionTitle' => 'Lieferanten-Dokumente',
            'preserveFilenames' => false,
            'timestampFilenames' => true,
            'categories' => [
                'contract' => 'Vertrag',
                'invoice' => 'Rechnung',
                'certificate' => 'Zertifikat',
                'correspondence' => 'Korrespondenz',
                'technical' => 'Technische Unterlagen',
                'quality' => 'Qualitätsdokumente',
                'other' => 'Sonstiges',
            ],
            'categoryColors' => [
                'contract' => 'success',
                'invoice' => 'warning',
                'certificate' => 'info',
                'correspondence' => 'green',
                'technical' => 'blue',
                'quality' => 'purple',
                'other' => 'gray',
            ],
        ]);
    }

    /**
     * Erstellt eine Konfiguration für Contract-Dokumente
     */
    public static function forContracts($contract = null): self
    {
        return new self([
            'pathType' => 'contracts',
            'model' => $contract,
            'title' => 'Vertrags-Dokumente',
            'sectionTitle' => 'Vertrags-Dokumente',
            'categories' => [
                'contract' => 'Hauptvertrag',
                'amendment' => 'Nachtrag',
                'annex' => 'Anlage',
                'invoice' => 'Rechnung',
                'correspondence' => 'Korrespondenz',
                'termination' => 'Kündigung',
                'other' => 'Sonstiges',
            ],
            'categoryColors' => [
                'contract' => 'success',
                'amendment' => 'warning',
                'annex' => 'info',
                'invoice' => 'orange',
                'correspondence' => 'green',
                'termination' => 'danger',
                'other' => 'gray',
            ],
        ]);
    }

    /**
     * Erstellt eine Konfiguration für Client-Dokumente (Kunden)
     */
    public static function forClients($customer = null): self
    {
        return new self([
            'pathType' => 'clients',
            'model' => $customer,
            'title' => 'Kunden-Dokumente',
            'sectionTitle' => 'Kunden-Dokumente',
            'preserveFilenames' => false,
            'timestampFilenames' => true,
            'categories' => [
                'contract' => 'Vertrag',
                'invoice' => 'Rechnung',
                'offer' => 'Angebot',
                'correspondence' => 'Korrespondenz',
                'technical' => 'Technische Unterlagen',
                'legal' => 'Rechtsdokumente',
                'other' => 'Sonstiges',
            ],
            'categoryColors' => [
                'contract' => 'success',
                'invoice' => 'warning',
                'offer' => 'info',
                'correspondence' => 'green',
                'technical' => 'blue',
                'legal' => 'purple',
                'other' => 'gray',
            ],
        ]);
    }

    /**
     * Erstellt eine Konfiguration für Solaranlagen-Dokumente
     */
    public static function forSolarPlants($solarPlant = null): self
    {
        return new self([
            'pathType' => 'solar_plants',
            'model' => $solarPlant,
            'title' => 'Solaranlagen-Dokumente',
            'sectionTitle' => 'Solaranlagen-Dokumente',
            'preserveFilenames' => false,
            'timestampFilenames' => true,
            'categories' => [
                'planning' => 'Planung',
                'permits' => 'Genehmigungen',
                'installation' => 'Installation',
                'commissioning' => 'Inbetriebnahme',
                'maintenance' => 'Wartung',
                'monitoring' => 'Überwachung',
                'insurance' => 'Versicherung',
                'technical' => 'Technische Unterlagen',
                'financial' => 'Finanzielle Unterlagen',
                'legal' => 'Rechtsdokumente',
                'other' => 'Sonstiges',
            ],
            'categoryColors' => [
                'planning' => 'info',
                'permits' => 'success',
                'installation' => 'warning',
                'commissioning' => 'primary',
                'maintenance' => 'orange',
                'monitoring' => 'blue',
                'insurance' => 'purple',
                'technical' => 'cyan',
                'financial' => 'yellow',
                'legal' => 'red',
                'other' => 'gray',
            ],
        ]);
    }

    /**
     * Erstellt eine Konfiguration für Task-Dokumente (Aufgaben)
     */
    public static function forTasks($task = null): self
    {
        return new self([
            'pathType' => 'tasks',
            'model' => $task,
            'title' => 'Aufgaben-Dokumente',
            'sectionTitle' => 'Aufgaben-Dokumente',
            'preserveFilenames' => false,
            'timestampFilenames' => true,
            'categories' => [
                'protocol' => 'Protokoll',
                'attachment' => 'Anhang',
                'correspondence' => 'Korrespondenz',
                'report' => 'Bericht',
                'checklist' => 'Checkliste',
                'photo' => 'Foto',
                'manual' => 'Anleitung',
                'specification' => 'Spezifikation',
                'approval' => 'Freigabe',
                'other' => 'Sonstiges',
            ],
            'categoryColors' => [
                'protocol' => 'info',
                'attachment' => 'gray',
                'correspondence' => 'green',
                'report' => 'blue',
                'checklist' => 'warning',
                'photo' => 'purple',
                'manual' => 'cyan',
                'specification' => 'orange',
                'approval' => 'success',
                'other' => 'gray',
            ],
        ]);
    }

    /**
     * Erstellt eine Konfiguration für Lieferantenvertrags-Dokumente
     * Verwendet die Struktur: suppliers/{supplier_id}/contracts/{contract_internal_number}/
     */
    public static function forSupplierContracts($supplierContract = null): self
    {
        return new self([
            'pathType' => 'supplier_contracts',
            'model' => $supplierContract,
            'title' => 'Vertrags-Dokumente',
            'sectionTitle' => 'Vertrags-Dokumente',
            'preserveFilenames' => false,
            'timestampFilenames' => true,
            'categories' => [
                'contract' => 'Hauptvertrag',
                'amendment' => 'Nachtrag',
                'annex' => 'Anlage',
                'invoice' => 'Rechnung',
                'correspondence' => 'Korrespondenz',
                'technical' => 'Technische Unterlagen',
                'legal' => 'Rechtsdokumente',
                'termination' => 'Kündigung',
                'other' => 'Sonstiges',
            ],
            'categoryColors' => [
                'contract' => 'success',
                'amendment' => 'warning',
                'annex' => 'info',
                'invoice' => 'orange',
                'correspondence' => 'green',
                'technical' => 'blue',
                'legal' => 'purple',
                'termination' => 'danger',
                'other' => 'gray',
            ],
        ]);
    }

    /**
     * Erstellt eine Konfiguration für allgemeine Dokumente
     */
    public static function forGeneral(): self
    {
        return new self([
            'pathType' => 'general',
            'title' => 'Dokumente',
            'sectionTitle' => 'Dokumente',
        ]);
    }

    /**
     * Magic getter mit dynamischer Pfad-Auflösung
     */
    public function __get(string $key)
    {
        // Spezielle Behandlung für storage-relevante Properties
        if ($key === 'storageDirectory') {
            return $this->getStorageDirectory();
        }
        
        if ($key === 'diskName') {
            return $this->getDiskName();
        }

        return $this->get($key);
    }
}