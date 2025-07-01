# Dateinamen-Konfiguration für Dokumente

## Übersicht

Die DocumentPathSetting-Lösung wurde um umfassende Dateinamen-Konfigurationen erweitert. Administratoren können jetzt nicht nur Pfadstrukturen, sondern auch Dateinamen-Strategien konfigurieren.

## Neue Funktionen

### 1. Dateinamen-Strategien

**Original (Standard)**
- Verwendet den ursprünglichen Dateinamen
- Sicher für bestehende Konfigurationen
- Optional mit Präfix/Suffix erweiterbar

**Random (Zufällig)**
- Generiert UUID + Timestamp für eindeutige Namen
- Format: `{uuid}_{timestamp}.{extension}`
- Verhindert Dateinamen-Konflikte
- Optional mit Präfix/Suffix erweiterbar

**Template (Vorlage)**
- Verwendet konfigurierbare Platzhalter
- Wiederverwendung der bestehenden Platzhalter-Syntax
- Beispiel: `{supplier_number}_vertrag_{date_y}`
- Vollständig anpassbar pro Konfiguration

### 2. Zusätzliche Optionen

**Präfix & Suffix**
- Flexibles Hinzufügen von Text vor/nach dem Dateinamen
- Funktioniert mit allen Strategien
- **Unterstützt Platzhalter**: Gleiche Platzhalter wie in Templates verfügbar
- Beispiel: Präfix "CONTRACT_{customer_number}_", Suffix "_{timestamp}"

**Dateierweiterungs-Erhaltung**
- Konfigurierbar ob Original-Extension beibehalten wird
- Standard: aktiviert für Kompatibilität

**Dateinamen-Bereinigung**
- Automatische Entfernung problematischer Zeichen
- Ersetzt Sonderzeichen durch Bindestriche
- Standard: aktiviert für Sicherheit

## Datenbank-Schema

### Neue Felder in `document_path_settings`

```sql
filename_strategy ENUM('original', 'random', 'template') DEFAULT 'original'
filename_template VARCHAR(500) NULL
filename_prefix VARCHAR(100) NULL
filename_suffix VARCHAR(100) NULL
preserve_extension BOOLEAN DEFAULT TRUE
sanitize_filename BOOLEAN DEFAULT TRUE
```

## Admin-Interface

### Neue Sektion: "Dateinamen-Konfiguration"

1. **Dateinamen-Strategie** (Dropdown)
   - Original, Random, Template

2. **Template** (Text, nur bei Template-Strategie)
   - Platzhalter-basierte Vorlage
   - Reactive: nur sichtbar wenn Template gewählt

3. **Präfix/Suffix** (Text)
   - Optional für alle Strategien

4. **Optionen** (Checkboxen)
   - Extension beibehalten
   - Dateinamen bereinigen

### Tabellen-Anzeige

- Neue Spalte "Dateinamen-Strategie"
- Farbige Badges für bessere Übersicht
- Vollständige CRUD-Unterstützung

## Code-Integration

### DocumentPathSetting Model

**Neue Methoden:**
- `generateFilename($originalFilename, $model = null)`
- `generateRandomFilename()`
- `generateTemplateFilename($model)`
- `sanitizeFilename($filename)`
- `getFilenameStrategies()`

### DocumentResource Integration

**Erweiterte FileUpload-Komponente:**
- `getUploadedFileNameForStorageUsing()` Hook
- Automatische Nutzung der konfigurierten Strategie
- Fallback für unvollständige Daten

**Neue Methode:**
- `generateConfigurableFilename()` für externe Nutzung

## Platzhalter-System

### Verfügbare Platzhalter (Template-Strategie)

**Datum/Zeit:**
- `{date_y}` - Jahr (2025)
- `{date_m}` - Monat (01-12)
- `{date_d}` - Tag (01-31)
- `{date_ymd}` - YYYY-MM-DD
- `{timestamp}` - Unix Timestamp

**Model-spezifisch:**
- `{supplier_number}` - Lieferantennummer
- `{customer_number}` - Kundennummer
- `{task_number}` - Aufgabennummer
- `{invoice_number}` - Rechnungsnummer
- `{plant_number}` - Anlagennummer

**Allgemein:**
- `{id}` - Model ID
- `{category}` - Dokumentkategorie

## Beispiele

### Template-basierte Dateinamen

**Konfiguration:**
- Template: `{supplier_number}_vertrag_{date_ymd}`
- Präfix: `CONTRACT_`
- Suffix: `_v1`

**Ergebnis:**
- Original: `Liefervertrag.pdf`
- Generiert: `CONTRACT_SUP001_vertrag_2025-07-01_v1.pdf`

### Platzhalter in Präfix/Suffix

**Konfiguration:**
- Strategie: Original
- Präfix: `{customer_number}_{timestamp}_`
- Suffix: `_{date_ymd}`

**Ergebnis:**
- Original: `rechnung.pdf`
- Generiert: `CUST001_1719842400_rechnung_2025-07-01.pdf`

### Random-Dateinamen

**Konfiguration:**
- Strategie: Random
- Präfix: `DOC_`

**Ergebnis:**
- Original: `Rechnung.pdf`
- Generiert: `DOC_550e8400-e29b-41d4-a716-446655440000_1719842400.pdf`

## Migration & Kompatibilität

### Bestehende Konfigurationen

- Automatische Migration mit Standard-Werten
- Alle bestehenden Konfigurationen nutzen 'original' Strategie
- Keine Änderung des bisherigen Verhaltens

### Neue Konfigurationen

- Standard-Strategie: 'original'
- Alle Optionen optional
- Benutzerfreundliche Defaults

## Testing

### Funktionale Tests

1. **Original-Strategie**
   - Dateiname bleibt unverändert
   - Präfix/Suffix werden korrekt angehängt

2. **Random-Strategie**
   - Eindeutige Namen werden generiert
   - Extension wird beibehalten

3. **Template-Strategie**
   - Platzhalter werden korrekt ersetzt
   - Model-Daten werden verwendet

4. **Bereinigung**
   - Sonderzeichen werden entfernt
   - Sichere Dateinamen entstehen

### Admin-Interface Tests

1. **Reactive Forms**
   - Template-Feld erscheint nur bei Template-Strategie
   - Alle Felder funktionieren korrekt

2. **CRUD-Operationen**
   - Erstellen, Bearbeiten, Löschen funktioniert
   - Validierung arbeitet korrekt

## Nächste Schritte

1. **Umfassende Tests** mit echten File-Uploads
2. **Performance-Optimierung** für große Dateien
3. **Erweiterte Platzhalter** nach Bedarf
4. **Bulk-Operations** für bestehende Dokumente
5. **API-Integration** für externe Systeme

## Technische Details

### Performance

- Lazy Loading der Model-Daten
- Caching von Pfad-Konfigurationen
- Optimierte Datenbankabfragen

### Sicherheit

- Automatische Dateinamen-Bereinigung
- Validierung aller Eingaben
- Schutz vor Directory Traversal

### Wartbarkeit

- Modularer Aufbau
- Klare Trennung der Verantwortlichkeiten
- Umfassende Dokumentation