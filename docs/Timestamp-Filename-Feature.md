# Zeitstempel-Dateinamen Feature

## Übersicht

Das Dokumenten-Upload-Modul wurde um eine automatische Zeitstempel-Funktionalität erweitert, die Dateiüberschreibungen verhindert und eine chronologische Versionierung ermöglicht.

## Implementierte Features

### 1. Automatische Zeitstempel-Generierung

**Format**: `originalname_YYYY-MM-DD_HH-MM-SS.extension`

**Beispiele**:
- `vertrag.pdf` → `vertrag_2025-01-07_11-30-54.pdf`
- `Rechnung 2024.docx` → `Rechnung-2024_2025-01-07_11-30-54.docx`
- `Foto mit Leerzeichen.jpg` → `Foto-mit-Leerzeichen_2025-01-07_11-30-54.jpg`

### 2. Intelligente Dateinamen-Bereinigung

- **Leerzeichen** werden durch **Bindestriche** ersetzt
- **Sonderzeichen** werden entfernt (außer Bindestriche und Unterstriche)
- **Umlaute** bleiben erhalten
- **Dateiendungen** werden korrekt behandelt

### 3. Konfigurierbare Aktivierung

```php
// Standard-Konfiguration (deaktiviert)
'preserveFilenames' => true,
'timestampFilenames' => false,

// Für Supplier-Dokumente (aktiviert)
'preserveFilenames' => false,
'timestampFilenames' => true,
```

## Technische Implementierung

### 1. DocumentFormBuilder Erweiterung

**Datei**: [`app/Services/DocumentFormBuilder.php`](app/Services/DocumentFormBuilder.php)

```php
/**
 * Generiert einen Dateinamen mit Zeitstempel
 */
private function generateTimestampedFilename(string $originalFilename): string
{
    $pathInfo = pathinfo($originalFilename);
    $basename = $pathInfo['filename'] ?? $originalFilename;
    $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
    
    // Bereinige den Dateinamen
    $cleanBasename = preg_replace('/[^a-zA-Z0-9äöüÄÖÜß\-_]/', '-', $basename);
    $cleanBasename = preg_replace('/-+/', '-', $cleanBasename);
    $cleanBasename = trim($cleanBasename, '-');
    
    // Generiere Zeitstempel
    $timestamp = now()->format('Y-m-d_H-i-s');
    
    return $cleanBasename . '_' . $timestamp . $extension;
}
```

### 2. FileUpload-Feld Integration

```php
$field->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file) use ($config) {
    if (!$config->get('preserveFilenames') && $config->get('timestampFilenames')) {
        return $this->generateTimestampedFilename($file->getClientOriginalName());
    }
    return $file->getClientOriginalName();
});
```

### 3. DocumentUploadTrait Konfiguration

**Datei**: [`app/Traits/DocumentUploadTrait.php`](app/Traits/DocumentUploadTrait.php)

```php
// Standard-Einstellungen für automatische Zeitstempel
'preserveFilenames' => false,
'timestampFilenames' => true,
```

### 4. DocumentUploadConfig Erweiterung

**Datei**: [`app/Services/DocumentUploadConfig.php`](app/Services/DocumentUploadConfig.php)

```php
// Neue Konfigurationsoption hinzugefügt
'timestampFilenames' => false,

// Supplier-spezifische Aktivierung
public static function forSuppliers($supplier = null): self
{
    return new self([
        'preserveFilenames' => false,
        'timestampFilenames' => true,
        // ... weitere Konfiguration
    ]);
}
```

## Vorteile der Zeitstempel-Benennung

### ✅ Keine Dateiüberschreibungen
- Jede hochgeladene Datei erhält einen eindeutigen Namen
- Mehrfache Uploads derselben Datei werden als separate Versionen gespeichert

### ✅ Chronologische Sortierung
- Dateien können nach Upload-Zeitpunkt sortiert werden
- Einfache Identifikation der neuesten Version

### ✅ Automatische Versionierung
- Keine manuelle Versionsnummerierung erforderlich
- Zeitstempel zeigt exakten Upload-Zeitpunkt

### ✅ Eindeutige Identifikation
- Jede Datei ist durch Zeitstempel eindeutig identifizierbar
- Vermeidung von Namenskonflikten

## Finale Ordnerstruktur

```
DigitalOcean Space: jtsolarbau/
└── documents/
    └── suppliers/
        └── LF-009-Alpine-Solar-Systems-AG/
            ├── vertrag_2025-01-07_11-28-52.pdf
            ├── rechnung_2025-01-07_11-29-15.docx
            ├── foto_2025-01-07_11-29-33.jpg
            └── zertifikat_2025-01-07_11-30-01.pdf
```

## Kompatibilität

### ✅ Vereinfachte Ordnerstruktur
- Funktioniert mit der neuen vereinfachten Supplier-Struktur
- Keine Unterverzeichnisse für `{document_number}-{document_name}` mehr erforderlich

### ✅ Bestehende Dateien
- Alte Dateien in `suppliers-documents/` bleiben unverändert
- Neue Uploads verwenden automatisch die Zeitstempel-Benennung

### ✅ Flexible Konfiguration
- Feature kann pro Dokumenttyp aktiviert/deaktiviert werden
- Standard-Verhalten bleibt für andere Module unverändert

## Verwendung

### Automatische Aktivierung
Das Feature ist automatisch für alle Supplier-Dokumente aktiviert:

```php
$config = DocumentUploadConfig::forSuppliers($supplier);
// timestampFilenames ist automatisch auf true gesetzt
```

### Manuelle Konfiguration
```php
$config = new DocumentUploadConfig([
    'preserveFilenames' => false,
    'timestampFilenames' => true,
]);
```

### Deaktivierung
```php
$config = new DocumentUploadConfig([
    'preserveFilenames' => true,
    'timestampFilenames' => false,
]);
```

## Test-Verifikation

Das Feature wurde erfolgreich getestet mit:
- ✅ Verschiedenen Dateiformaten (PDF, DOCX, JPG, TXT)
- ✅ Sonderzeichen und Leerzeichen in Dateinamen
- ✅ Dateien ohne Dateiendung
- ✅ Integration mit vereinfachter Ordnerstruktur
- ✅ Korrekte Zeitstempel-Generierung

## Fazit

Die Zeitstempel-Funktionalität bietet eine robuste Lösung für:
- **Dateiüberschreibungs-Vermeidung**
- **Automatische Versionierung**
- **Chronologische Organisation**
- **Eindeutige Dateiidentifikation**

Das Feature ist vollständig in das bestehende Dokumenten-Upload-System integriert und kann flexibel konfiguriert werden.