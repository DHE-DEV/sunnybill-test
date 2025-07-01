# Dynamisches Pfad-Management-System für Dokument-Uploads

## Übersicht

Das neue dynamische Pfad-Management-System ermöglicht es, strukturierte und konfigurierbare Speicherpfade für Dokument-Uploads zu definieren. Anstatt alle Dokumente im generischen "documents" Ordner zu speichern, können jetzt spezifische Pfade mit Platzhaltern verwendet werden.

## Funktionen

### 1. Platzhalter-System

Das System unterstützt verschiedene Platzhalter, die zur Laufzeit durch echte Werte ersetzt werden:

#### Standard-Platzhalter
- `{timestamp}` - Aktueller Zeitstempel (Y-m-d_H-i-s)
- `{date}` - Aktuelles Datum (Y-m-d)
- `{year}` - Aktuelles Jahr
- `{month}` - Aktueller Monat
- `{day}` - Aktueller Tag

#### Supplier-spezifische Platzhalter
- `{supplier_number}` - Lieferantennummer
- `{supplier_name}` - Lieferantenname
- `{supplier_id}` - Lieferanten-ID
- `{document_number}` - Dokumentnummer
- `{document_name}` - Dokumentname

#### Contract-spezifische Platzhalter
- `{contract_number}` - Vertragsnummer
- `{contract_name}` - Vertragsname
- `{contract_id}` - Vertrags-ID
- `{supplier_number}` - Lieferantennummer (falls Vertrag zu Supplier gehört)
- `{supplier_name}` - Lieferantenname (falls Vertrag zu Supplier gehört)

### 2. Konfigurierbare Pfad-Templates

In den Storage-Einstellungen können verschiedene Pfad-Templates definiert werden:

```json
{
  "suppliers": {
    "name": "Lieferanten-Dokumente",
    "path": "suppliers/{supplier_number}-{supplier_name}/{document_number}-{document_name}",
    "description": "Strukturierte Ablage für Lieferanten-Dokumente"
  },
  "contracts": {
    "name": "Vertrags-Dokumente", 
    "path": "contracts/{contract_number}/{supplier_name}/{document_name}",
    "description": "Strukturierte Ablage für Vertrags-Dokumente"
  },
  "general": {
    "name": "Allgemeine Dokumente",
    "path": "documents/{year}/{month}/{document_name}",
    "description": "Zeitbasierte Ablage für allgemeine Dokumente"
  }
}
```

### 3. Beispiel-Pfade

Mit einem Lieferanten "Mustermann GmbH" (SUP-001) und einem Dokument "Vertrag-2024.pdf":

**Template:** `suppliers/{supplier_number}-{supplier_name}/{document_number}-{document_name}`

**Resultat:** `suppliers/SUP-001-Mustermann-GmbH/DOC-123-Vertrag-2024.pdf`

## Technische Implementierung

### 1. Datenbank-Schema

Die `storage_settings` Tabelle wurde um eine `storage_paths` JSON-Spalte erweitert:

```sql
ALTER TABLE storage_settings ADD COLUMN storage_paths JSON;
```

### 2. Model-Erweiterungen

#### StorageSetting Model

```php
// Pfad für einen bestimmten Typ abrufen
$pathConfig = $storageSetting->getStoragePath('suppliers');

// Dynamischen Pfad generieren
$resolvedPath = $storageSetting->resolvePath('suppliers', $supplier, [
    'document_number' => 'DOC-123',
    'document_name' => 'Vertrag-2024.pdf'
]);

// Verfügbare Platzhalter abrufen
$placeholders = $storageSetting->getAvailablePlaceholders('suppliers');
```

#### DocumentStorageService

```php
// Dynamisches Upload-Verzeichnis
$directory = DocumentStorageService::getUploadDirectoryForModel('suppliers', $supplier);

// Pfad-Vorschau
$preview = DocumentStorageService::previewPath('suppliers', $supplier);

// Vollständigen Upload-Pfad generieren
$fullPath = DocumentStorageService::generateFullUploadPath('suppliers', 'vertrag.pdf', $supplier);
```

#### DocumentUploadConfig

```php
// Factory-Methoden für verschiedene Typen
$config = DocumentUploadConfig::forSuppliers($supplier);
$config = DocumentUploadConfig::forContracts($contract);
$config = DocumentUploadConfig::forGeneral();

// Dynamische Pfad-Konfiguration
$config->setPathType('suppliers', $supplier);
$config->setAdditionalData(['document_number' => 'DOC-123']);

// Storage-Verzeichnis abrufen (automatisch aufgelöst)
$directory = $config->getStorageDirectory();
```

### 3. Relation Manager Integration

```php
class DocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;

    protected function getDocumentUploadConfig(): DocumentUploadConfig
    {
        $supplier = $this->getOwnerRecord();
        
        return DocumentUploadConfig::forSuppliers($supplier)
            ->merge([
                // Zusätzliche Konfiguration...
            ]);
    }
}
```

## Sicherheit und Validierung

### 1. Pfad-Bereinigung

Alle Werte werden automatisch bereinigt:
- Gefährliche Zeichen werden entfernt/ersetzt
- Mehrfache Bindestriche werden reduziert
- Führende/nachfolgende Bindestriche werden entfernt
- Leere Werte werden durch "unknown" ersetzt

### 2. Fallback-Mechanismus

Falls keine Pfad-Konfiguration vorhanden ist, greift das System auf Standard-Pfade zurück:
- `suppliers` → `supplier-documents`
- `contracts` → `contract-documents`
- `general` → `documents`

## Migration und Kompatibilität

### 1. Bestehende Dokumente

Bestehende Dokumente bleiben unverändert. Das neue System wirkt nur auf neue Uploads.

### 2. Schrittweise Migration

```php
// Migration ausführen
php artisan migrate

// Standard-Pfade werden automatisch in storage_paths eingefügt
```

### 3. Rückwärtskompatibilität

Das System ist vollständig rückwärtskompatibel. Wenn keine `pathType` gesetzt ist, wird das statische `directory` verwendet.

## Konfiguration

### 1. Storage-Einstellungen erweitern

Die Pfad-Konfiguration kann über die Admin-Oberfläche oder direkt in der Datenbank verwaltet werden:

```sql
UPDATE storage_settings 
SET storage_paths = JSON_OBJECT(
    'suppliers', JSON_OBJECT(
        'name', 'Lieferanten-Dokumente',
        'path', 'suppliers/{supplier_number}-{supplier_name}/{document_number}-{document_name}',
        'description', 'Strukturierte Ablage für Lieferanten-Dokumente'
    )
)
WHERE is_active = 1;
```

### 2. Neue Pfad-Typen hinzufügen

```php
// In StorageSetting Model
private function addModelPlaceholders(array &$replacements, $model): void
{
    // Neue Model-Typen hinzufügen
    if ($model instanceof \App\Models\Customer) {
        $replacements['{customer_number}'] = $this->sanitizeValue($model->customer_number);
        $replacements['{customer_name}'] = $this->sanitizeValue($model->name);
    }
}
```

## Vorteile

1. **Strukturierte Ablage**: Dokumente werden logisch organisiert
2. **Flexibilität**: Pfade können per Konfiguration angepasst werden
3. **Skalierbarkeit**: Neue Platzhalter und Typen können einfach hinzugefügt werden
4. **Sicherheit**: Automatische Pfad-Bereinigung verhindert Sicherheitsprobleme
5. **Kompatibilität**: Vollständig rückwärtskompatibel mit bestehenden Systemen

## Beispiel-Verzeichnisstruktur

```
DigitalOcean Space Root/
├── suppliers/
│   ├── SUP-001-Mustermann-GmbH/
│   │   ├── DOC-123-Vertrag-2024.pdf
│   │   ├── DOC-124-Rechnung-Januar.pdf
│   │   └── DOC-125-Zertifikat-ISO.pdf
│   └── SUP-002-Beispiel-AG/
│       ├── DOC-200-Rahmenvertrag.pdf
│       └── DOC-201-Preisliste.xlsx
├── contracts/
│   ├── VERT-2024-001/
│   │   └── Mustermann-GmbH/
│   │       ├── Hauptvertrag.pdf
│   │       └── Nachtrag-1.pdf
│   └── VERT-2024-002/
└── documents/
    ├── 2024/
    │   ├── 01/
    │   └── 02/
    └── 2025/
```

## Testing

Das System kann mit dem Test-Command überprüft werden:

```bash
php artisan tinker --execute="
\$results = \App\Services\DocumentStorageService::testPathGeneration();
foreach (\$results as \$type => \$result) {
    echo \$type . ': ' . \$result['with_test_data']['resolved_path'] . PHP_EOL;
}
"