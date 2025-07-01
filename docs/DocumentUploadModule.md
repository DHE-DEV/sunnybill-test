# Dokumenten-Upload-Modul

Ein vollständig wiederverwendbares und flexibles Modul für Dokumenten-Uploads in Laravel Filament-Anwendungen.

## 📋 Inhaltsverzeichnis

- [Überblick](#überblick)
- [Installation](#installation)
- [Komponenten](#komponenten)
- [Verwendung](#verwendung)
- [Konfiguration](#konfiguration)
- [Beispiele](#beispiele)
- [API-Referenz](#api-referenz)
- [Erweiterte Features](#erweiterte-features)
- [Troubleshooting](#troubleshooting)

## 🎯 Überblick

Das Dokumenten-Upload-Modul bietet eine einheitliche, wiederverwendbare Lösung für Datei-Uploads in Filament-Anwendungen. Es eliminiert Code-Duplikation und bietet maximale Flexibilität durch konfigurierbare Komponenten.

### ✨ Features

- **🔄 Wiederverwendbar**: Ein Modul für alle Upload-Anforderungen
- **⚙️ Konfigurierbar**: Flexible Anpassung an verschiedene Anwendungsfälle
- **🎨 UI-Komponenten**: Vorgefertigte Formulare und Tabellen
- **📁 Automatische Metadaten**: Extraktion von Dateigröße, MIME-Type, etc.
- **🏷️ Kategorisierung**: Flexible Kategorien-System
- **🔍 Suche & Filter**: Integrierte Such- und Filterfunktionen
- **📊 Statistiken**: Optional Dashboard mit Upload-Statistiken
- **🎯 Drag & Drop**: Optional Drag-and-Drop-Interface
- **🔒 Validierung**: Umfassende Datei-Validierung

## 🚀 Installation

Das Modul ist bereits in Ihrem Projekt installiert. Alle Komponenten befinden sich in:

```
app/
├── Traits/DocumentUploadTrait.php
├── Services/
│   ├── DocumentFormBuilder.php
│   ├── DocumentTableBuilder.php
│   └── DocumentUploadConfig.php
└── Filament/Components/DocumentUploadComponent.php

resources/views/filament/components/
└── document-upload-component.blade.php
```

## 🧩 Komponenten

### 1. DocumentUploadTrait

Ein Trait für RelationManager, der die komplette Upload-Funktionalität bereitstellt.

```php
use App\Traits\DocumentUploadTrait;

class MyDocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;
    
    protected static string $relationship = 'documents';
    
    protected function getDocumentUploadConfig(): array
    {
        return [
            'directory' => 'my-documents',
            'categories' => ['contract', 'invoice'],
            'maxSize' => 10240,
        ];
    }
}
```

### 2. DocumentFormBuilder

Service zum dynamischen Erstellen von Upload-Formularen.

```php
use App\Services\DocumentFormBuilder;

// Einfaches Upload-Feld
$uploadField = DocumentFormBuilder::quickUpload([
    'maxSize' => 5120,
    'acceptedFileTypes' => ['application/pdf']
]);

// Vollständiges Schema
$schema = DocumentFormBuilder::quickSchema([
    'categories' => ['contract' => 'Vertrag'],
    'showDescription' => true
]);
```

### 3. DocumentTableBuilder

Service zum dynamischen Erstellen von Dokumenten-Tabellen.

```php
use App\Services\DocumentTableBuilder;

public function table(Table $table): Table
{
    return DocumentTableBuilder::make([
        'showIcon' => true,
        'enableBulkActions' => true,
        'categories' => ['contract' => 'Vertrag']
    ])->build($table);
}
```

### 4. DocumentUploadComponent

Standalone Livewire-Komponente für die Verwendung außerhalb von RelationManagern.

```blade
<livewire:document-upload-component 
    :model="$supplier" 
    relationship="documents"
    :config="[
        'directory' => 'supplier-docs',
        'maxSize' => 20480,
        'showStats' => true
    ]"
/>
```

### 5. DocumentUploadConfig

Typisierte Konfigurationsklasse mit Validierung.

```php
use App\Services\DocumentUploadConfig;

// Vordefinierte Konfigurationen
$imageConfig = DocumentUploadConfig::forImages();
$documentConfig = DocumentUploadConfig::forDocuments();
$minimalConfig = DocumentUploadConfig::minimal();
$fullConfig = DocumentUploadConfig::full();

// Custom Konfiguration
$config = new DocumentUploadConfig([
    'maxSize' => 10240,
    'categories' => ['contract' => 'Vertrag']
]);
```

## 📖 Verwendung

### Einfache Verwendung mit Trait

```php
<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Traits\DocumentUploadTrait;
use Filament\Resources\RelationManagers\RelationManager;

class DocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;

    protected static string $relationship = 'documents';

    // Das war's! Keine weitere Konfiguration nötig.
    // Verwendet Standard-Einstellungen.
}
```

### Angepasste Konfiguration

```php
class DocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;

    protected static string $relationship = 'documents';

    protected function getDocumentUploadConfig(): array
    {
        return [
            'directory' => 'supplier-contracts',
            'categories' => [
                'contract' => 'Vertrag',
                'invoice' => 'Rechnung',
                'certificate' => 'Zertifikat',
            ],
            'maxSize' => 20480, // 20MB
            'acceptedFileTypes' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ],
            'defaultCategory' => 'contract',
            'showStats' => true,
            'enableDragDrop' => true,
            'createButtonLabel' => 'Dokument hochladen',
        ];
    }
}
```

### Standalone Komponente

```blade
{{-- In einer Blade-View --}}
<div class="space-y-6">
    <h2>Lieferanten-Dokumente</h2>
    
    <livewire:document-upload-component 
        :model="$supplier" 
        relationship="documents"
        :config="[
            'directory' => 'supplier-docs',
            'categories' => [
                'contract' => 'Vertrag',
                'invoice' => 'Rechnung'
            ],
            'maxSize' => 15360,
            'showStats' => true,
            'enableDragDrop' => true
        ]"
    />
</div>
```

### Programmatischer Upload

```php
use App\Filament\Components\DocumentUploadComponent;

// Einzelner Upload
$component = new DocumentUploadComponent();
$component->mount($supplier, 'documents', [
    'directory' => 'supplier-docs'
]);
$component->quickUpload('path/to/file.pdf', [
    'name' => 'Liefervertrag',
    'category' => 'contract',
    'description' => 'Hauptvertrag mit Lieferant XYZ'
]);

// Bulk Upload
$files = [
    [
        'path' => 'path/to/contract.pdf',
        'metadata' => ['name' => 'Vertrag', 'category' => 'contract']
    ],
    [
        'path' => 'path/to/invoice.pdf',
        'metadata' => ['name' => 'Rechnung', 'category' => 'invoice']
    ]
];
$component->bulkUpload($files);
```

## ⚙️ Konfiguration

### Basis-Konfiguration

```php
[
    // Upload-Einstellungen
    'directory' => 'documents',           // Upload-Verzeichnis
    'maxSize' => 10240,                  // Max. Größe in KB (10MB)
    'preserveFilenames' => true,         // Originale Dateinamen beibehalten
    'multiple' => false,                 // Mehrfach-Upload erlauben
    'required' => true,                  // Upload erforderlich
    
    // Erlaubte Dateitypen
    'acceptedFileTypes' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        // ...
    ],
    
    // Kategorien
    'categories' => [
        'contract' => 'Vertrag',
        'invoice' => 'Rechnung',
        'certificate' => 'Zertifikat',
        // ...
    ],
    'defaultCategory' => null,           // Standard-Kategorie
    'categoryRequired' => false,         // Kategorie erforderlich
    
    // UI-Einstellungen
    'modalWidth' => '4xl',              // Modal-Breite
    'title' => 'Dokumente',            // Titel
    'createButtonLabel' => 'Dokument hinzufügen',
    'emptyStateHeading' => 'Keine Dokumente vorhanden',
    
    // Features
    'showStats' => false,               // Statistiken anzeigen
    'enableDragDrop' => false,          // Drag & Drop aktivieren
    'showPreview' => true,              // Vorschau-Button
    'showDownload' => true,             // Download-Button
    'enableBulkActions' => true,        // Bulk-Aktionen
]
```

### Vordefinierte Konfigurationen

```php
// Für Bilder optimiert
DocumentUploadConfig::forImages()
    ->set('directory', 'photos')
    ->toArray();

// Für Dokumente optimiert
DocumentUploadConfig::forDocuments()
    ->set('extractText', true)
    ->toArray();

// Für Archive optimiert
DocumentUploadConfig::forArchives()
    ->set('maxSize', 102400) // 100MB
    ->toArray();

// Minimale Konfiguration
DocumentUploadConfig::minimal()
    ->set('categories', [])
    ->toArray();

// Vollständige Konfiguration
DocumentUploadConfig::full()
    ->set('enableVersioning', true)
    ->toArray();
```

## 💡 Beispiele

### Beispiel 1: Einfacher Dokumenten-Manager

```php
class SimpleDocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;

    protected static string $relationship = 'documents';
    
    // Verwendet Standard-Konfiguration - kein weiterer Code nötig!
}
```

### Beispiel 2: Foto-Galerie

```php
class PhotoGalleryRelationManager extends RelationManager
{
    use DocumentUploadTrait;

    protected static string $relationship = 'photos';

    protected function getDocumentUploadConfig(): array
    {
        return DocumentUploadConfig::forImages()
            ->set('directory', 'gallery')
            ->set('categories', [
                'product' => 'Produktfoto',
                'team' => 'Team-Foto',
                'event' => 'Event-Foto',
            ])
            ->set('enableDragDrop', true)
            ->set('showStats', true)
            ->set('generateThumbnails', true)
            ->toArray();
    }
}
```

### Beispiel 3: Vertrags-Verwaltung

```php
class ContractDocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;

    protected static string $relationship = 'documents';

    protected function getDocumentUploadConfig(): array
    {
        return [
            'directory' => 'contracts',
            'categories' => [
                'main_contract' => 'Hauptvertrag',
                'amendment' => 'Nachtrag',
                'termination' => 'Kündigung',
                'renewal' => 'Verlängerung',
            ],
            'acceptedFileTypes' => ['application/pdf'],
            'maxSize' => 51200, // 50MB
            'categoryRequired' => true,
            'defaultCategory' => 'main_contract',
            'validateFileContent' => true,
            'extractText' => true,
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->processDocumentUploadData($data);

        // Automatische Benennung basierend auf Kategorie
        if ($data['category'] === 'main_contract') {
            $data['name'] = 'Hauptvertrag - ' . now()->format('Y-m-d');
        }

        // Automatische Beschreibung
        if (empty($data['description'])) {
            $data['description'] = 'Automatisch hochgeladen am ' . now()->format('d.m.Y H:i');
        }

        return $data;
    }
}
```

### Beispiel 4: Standalone Upload-Seite

```php
// Livewire-Komponente
class DocumentManagerPage extends Component
{
    public $supplier;

    public function mount(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    public function render()
    {
        return view('livewire.document-manager-page');
    }
}
```

```blade
{{-- livewire/document-manager-page.blade.php --}}
<div class="max-w-7xl mx-auto py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Dokumente für {{ $supplier->name }}</h1>
        <p class="text-gray-600">Verwalten Sie alle Dokumente für diesen Lieferanten.</p>
    </div>

    <livewire:document-upload-component 
        :model="$supplier" 
        relationship="documents"
        :config="[
            'directory' => 'supplier-' . $supplier->id,
            'categories' => [
                'contract' => 'Vertrag',
                'invoice' => 'Rechnung',
                'certificate' => 'Zertifikat',
                'correspondence' => 'Korrespondenz',
            ],
            'maxSize' => 25600,
            'showStats' => true,
            'enableDragDrop' => true,
            'title' => 'Lieferanten-Dokumente',
            'createButtonLabel' => 'Neues Dokument hochladen',
        ]"
    />
</div>
```

## 📚 API-Referenz

### DocumentUploadTrait

#### Methoden

- `getDocumentUploadConfig(): array` - Konfiguration definieren
- `processDocumentUploadData(array $data): array` - Upload-Daten verarbeiten
- `mergeDocumentConfig(array $customConfig): array` - Konfiguration mergen

#### Hooks

- `mutateFormDataBeforeCreate(array $data): array` - Vor dem Erstellen
- `mutateFormDataBeforeSave(array $data): array` - Vor dem Speichern

### DocumentFormBuilder

#### Statische Methoden

- `make(array $config): self` - Builder erstellen
- `quickUpload(array $options): FileUpload` - Schnelles Upload-Feld
- `quickSchema(array $options): array` - Schnelles Schema

#### Instanz-Methoden

- `build(Form $form): Form` - Formular erstellen
- `getFormSchema(): array` - Schema abrufen

### DocumentTableBuilder

#### Statische Methoden

- `make(array $config): self` - Builder erstellen

#### Instanz-Methoden

- `build(Table $table): Table` - Tabelle erstellen

### DocumentUploadComponent

#### Öffentliche Methoden

- `quickUpload(string $filePath, array $metadata): void` - Schneller Upload
- `bulkUpload(array $files): void` - Bulk-Upload
- `openCreateModal(): void` - Modal öffnen
- `closeCreateModal(): void` - Modal schließen

### DocumentUploadConfig

#### Statische Factory-Methoden

- `forImages(): self` - Bild-Konfiguration
- `forDocuments(): self` - Dokument-Konfiguration
- `forArchives(): self` - Archiv-Konfiguration
- `minimal(): self` - Minimale Konfiguration
- `full(): self` - Vollständige Konfiguration

#### Instanz-Methoden

- `get(string $key, $default = null)` - Wert abrufen
- `set(string $key, $value): self` - Wert setzen
- `merge(array $config): self` - Konfiguration mergen
- `toArray(): array` - Als Array ausgeben

## 🚀 Erweiterte Features

### Custom Validierung

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data = $this->processDocumentUploadData($data);

    // Custom Validierung
    if ($data['size'] > 50 * 1024 * 1024) { // 50MB
        throw new \Exception('Datei ist zu groß für diese Kategorie');
    }

    // Virus-Scan (Beispiel)
    if ($this->config['scanForViruses'] ?? false) {
        $this->scanFileForViruses($data['path']);
    }

    return $data;
}
```

### Automatische Kategorisierung

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data = $this->processDocumentUploadData($data);

    // KI-basierte Kategorisierung (Beispiel)
    if (!isset($data['category'])) {
        $data['category'] = $this->classifyDocument($data['path']);
    }

    return $data;
}

private function classifyDocument(string $path): string
{
    $filename = strtolower(basename($path));
    
    if (str_contains($filename, 'vertrag') || str_contains($filename, 'contract')) {
        return 'contract';
    } elseif (str_contains($filename, 'rechnung') || str_contains($filename, 'invoice')) {
        return 'invoice';
    }
    
    return 'other';
}
```

### Versionierung

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data = $this->processDocumentUploadData($data);

    if ($this->config['enableVersioning'] ?? false) {
        // Prüfe auf existierende Dokumente mit gleichem Namen
        $existing = $this->getRelationship()
            ->where('name', $data['name'])
            ->latest('version')
            ->first();

        if ($existing) {
            $data['version'] = $existing->version + 1;
            $data['previous_version_id'] = $existing->id;
        } else {
            $data['version'] = 1;
        }
    }

    return $data;
}
```

## 🔧 Troubleshooting

### Häufige Probleme

#### 1. "Class DocumentFormBuilder not found"

**Lösung**: Stellen Sie sicher, dass alle Services im richtigen Namespace sind:

```php
use App\Services\DocumentFormBuilder;
use App\Services\DocumentTableBuilder;
use App\Services\DocumentUploadConfig;
```

#### 2. "Method getFormSchema does not exist"

**Problem**: DocumentFormBuilder hat keine öffentliche `getFormSchema()` Methode.

**Lösung**: Verwenden Sie `quickSchema()` für direkten Zugriff:

```php
$schema = DocumentFormBuilder::quickSchema($config);
```

#### 3. Upload-Fehler "Field 'original_name' doesn't have a default value"

**Lösung**: Stellen Sie sicher, dass `processDocumentUploadData()` aufgerufen wird:

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    return $this->processDocumentUploadData($data);
}
```

#### 4. Metadaten werden nicht extrahiert

**Lösung**: Prüfen Sie die DocumentStorageService-Konfiguration:

```php
// In der Konfiguration
'disk' => DocumentStorageService::getDiskName(),
'directory' => DocumentStorageService::getUploadDirectory($this->config('directory')),
```

### Debug-Tipps

1. **Logging aktivieren**:
```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    \Log::info('Upload Data Before Processing', $data);
    $data = $this->processDocumentUploadData($data);
    \Log::info('Upload Data After Processing', $data);
    return $data;
}
```

2. **Konfiguration prüfen**:
```php
protected function getDocumentUploadConfig(): array
{
    $config = [/* your config */];
    \Log::info('Document Upload Config', $config);
    return $config;
}
```

3. **Filament Debug-Modus**:
```php
// In .env
APP_DEBUG=true
FILAMENT_DEBUG=true
```

## 🎉 Fazit

Das Dokumenten-Upload-Modul bietet eine vollständige, wiederverwendbare Lösung für alle Upload-Anforderungen in Ihrer Filament-Anwendung. Mit minimaler Konfiguration erhalten Sie:

- ✅ Vollständige Upload-Funktionalität
- ✅ Automatische Metadaten-Extraktion
- ✅ Flexible Kategorisierung
- ✅ Responsive UI-Komponenten
- ✅ Umfassende Validierung
- ✅ Erweiterte Features nach Bedarf

**Nächste Schritte:**

1. Testen Sie das Modul mit einem einfachen RelationManager
2. Passen Sie die Konfiguration an Ihre Anforderungen an
3. Erweitern Sie das Modul mit custom Features
4. Implementieren Sie es in allen relevanten Bereichen Ihrer Anwendung

Bei Fragen oder Problemen konsultieren Sie diese Dokumentation oder prüfen Sie die Implementierung in den bestehenden RelationManagern.