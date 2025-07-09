# Variables PDF-Analyse-System - Technische Dokumentation

## Übersicht

Das variable PDF-Analyse-System ersetzt die bisherige hardcodierte E.ON-spezifische Lösung durch ein flexibles, regelbasiertes System zur automatischen Analyse und Zuordnung von PDF-Rechnungen beliebiger Lieferanten.

## Systemarchitektur

### Kernkomponenten

1. **Supplier Recognition Engine** - Automatische Lieferanten-Identifikation
2. **Rule-based Extraction System** - Konfigurierbare Datenextraktion
3. **Contract Matching Engine** - Intelligente Vertragszuordnung
4. **Admin Interface** - Filament-basierte Regel-Verwaltung

### Technologie-Stack

- **Framework**: Laravel 10+
- **Admin Interface**: Filament 3.x
- **Datenbank**: MySQL/PostgreSQL
- **PDF-Verarbeitung**: smalot/pdfparser
- **Testing**: PHPUnit mit Feature/Unit Tests

## Datenbankstruktur

### Tabellen

#### `supplier_recognition_patterns`
Speichert Pattern zur Lieferanten-Erkennung:
- `pattern_type`: email_domain, company_name, tax_id, regex, etc.
- `pattern_value`: Der konkrete Pattern-Wert
- `confidence_score`: Vertrauenswert (0.1 - 1.0)

#### `pdf_extraction_rules`
Definiert Regeln zur Datenextraktion:
- `extraction_method`: regex, keyword_search, position_based, etc.
- `extraction_pattern`: Das Extraktions-Pattern
- `data_type`: string, number, decimal, date, etc.
- `fallback_patterns`: Alternative Pattern bei Fehlschlag

#### `contract_matching_rules`
Konfiguriert Vertragszuordnung:
- `match_type`: exact, partial, fuzzy, regex, etc.
- `source_field`: Quellfeld aus PDF-Extraktion
- `target_field`: Zielfeld im Vertrag
- `match_threshold`: Mindest-Ähnlichkeit

## Service-Klassen

### SupplierRecognitionService

**Zweck**: Automatische Identifikation des Lieferanten basierend auf PDF-Inhalten

**Hauptmethoden**:
```php
recognizeSupplier(string $pdfText, float $confidenceThreshold = 0.5): array
testPattern(SupplierRecognitionPattern $pattern, string $text): array
getPatternsForSupplier(int $supplierId): Collection
```

**Pattern-Typen**:
- `email_domain`: E-Mail-Domain-Matching
- `company_name`: Firmenname-Erkennung
- `tax_id`: Steuer-ID-Matching
- `regex`: Reguläre Ausdrücke
- `keyword`: Schlüsselwort-Suche
- `address`: Adress-Matching

### RuleBasedExtractionService

**Zweck**: Konfigurierbare Extraktion von Datenfeldern aus PDF-Text

**Hauptmethoden**:
```php
extractData(int $supplierId, string $pdfText): array
testRule(PdfExtractionRule $rule, string $text): array
generateRulesFromSamples(int $supplierId, array $sampleTexts): array
```

**Extraktionsmethoden**:
- `regex`: Reguläre Ausdrücke
- `keyword_search`: Schlüsselwort-basierte Suche
- `position_based`: Positions-basierte Extraktion
- `table_extraction`: Tabellen-Extraktion
- `line_pattern`: Zeilen-Pattern-Matching
- `section_extraction`: Bereichs-Extraktion

**Datentypen**:
- `string`, `number`, `decimal`, `date`, `boolean`
- `email`, `phone`, `iban`, `currency`

### ContractMatchingService

**Zweck**: Intelligente Zuordnung extrahierter Daten zu bestehenden Verträgen

**Hauptmethoden**:
```php
findMatchingContract(int $supplierId, array $extractedData): array
testRule(ContractMatchingRule $rule, string $testValue, string $contractValue): array
getRulesForSupplier(int $supplierId): Collection
```

**Match-Typen**:
- `exact`: Exakte Übereinstimmung
- `partial`: Teilweise Übereinstimmung
- `fuzzy`: Fuzzy-String-Matching
- `regex`: Reguläre Ausdrücke
- `range`: Bereichs-Matching
- `contains`: Enthält-Prüfung
- `starts_with`: Beginnt-mit-Prüfung
- `ends_with`: Endet-mit-Prüfung

## Controller-Integration

### PdfAnalysisController

**Neue Endpunkte**:
- `GET /analyze-variable`: Formular für variable Analyse
- `POST /analyze-variable`: Variable Analyse mit View-Response
- `POST /analyze-variable-json`: Variable Analyse mit JSON-Response

**Analyseprozess**:
1. PDF-Upload und Text-Extraktion
2. Lieferanten-Erkennung
3. Datenextraktion basierend auf Lieferanten-Regeln
4. Vertragszuordnung
5. Confidence-Score-Berechnung
6. Ergebnis-Rückgabe

## Admin-Interface (Filament)

### Navigation
Alle Resources sind unter "PDF-Analyse System" gruppiert:
1. Erkennungsmuster (SupplierRecognitionPatternResource)
2. Extraktionsregeln (PdfExtractionRuleResource)
3. Vertrags-Matching (ContractMatchingRuleResource)

### Features
- **CRUD-Operationen**: Vollständige Verwaltung aller Regeln
- **Live-Tests**: Direkte Überprüfung von Pattern und Regeln
- **Bulk-Aktionen**: Massenaktivierung/-deaktivierung
- **Filter & Suche**: Erweiterte Filteroptionen
- **Prioritätsverwaltung**: Reihenfolge der Regel-Auswertung

### Test-Funktionalitäten
- **Pattern-Test**: Überprüfung von Erkennungsmustern
- **Extraktion-Test**: Live-Test der Datenextraktion
- **Matching-Test**: Vergleich von Werten mit Regeln

## API-Dokumentation

### Variable PDF-Analyse

**Endpunkt**: `POST /analyze-variable-json`

**Request**:
```json
{
    "pdf_file": "multipart/form-data"
}
```

**Response (Erfolg)**:
```json
{
    "success": true,
    "supplier_recognition": {
        "success": true,
        "supplier_id": 1,
        "confidence": 0.95,
        "matched_pattern_type": "company_name"
    },
    "data_extraction": {
        "success": true,
        "extracted_data": {
            "invoice_number": {
                "value": "RE-2024-001",
                "confidence": 0.9,
                "data_type": "string"
            },
            "total_amount": {
                "value": "119.00",
                "confidence": 0.85,
                "data_type": "decimal"
            }
        }
    },
    "contract_matching": {
        "success": true,
        "contract_id": 5,
        "confidence": 1.0,
        "match_type": "exact"
    },
    "confidence_scores": {
        "supplier_recognition": 0.95,
        "data_extraction": 0.875,
        "contract_matching": 1.0,
        "overall": 0.942
    },
    "processing_time": 0.234
}
```

**Response (Fehler)**:
```json
{
    "success": false,
    "error": "Lieferant konnte nicht erkannt werden",
    "supplier_recognition": {
        "success": false,
        "confidence": 0
    },
    "processing_time": 0.123
}
```

## Konfiguration und Setup

### 1. Migrationen ausführen
```bash
php artisan migrate
```

### 2. Filament-Resources registrieren
Die Resources werden automatisch in der Filament-Navigation angezeigt.

### 3. Beispiel-Konfiguration erstellen

#### Lieferanten-Erkennungspattern
```php
SupplierRecognitionPattern::create([
    'supplier_id' => 1,
    'pattern_type' => 'email_domain',
    'pattern_value' => 'eon.de',
    'confidence_score' => 0.95,
    'is_active' => true,
]);
```

#### Extraktionsregel
```php
PdfExtractionRule::create([
    'supplier_id' => 1,
    'field_name' => 'invoice_number',
    'extraction_method' => 'regex',
    'extraction_pattern' => '/Rechnungsnummer[:\s]*([A-Z0-9\-]+)/i',
    'data_type' => 'string',
    'confidence_threshold' => 0.8,
    'is_active' => true,
]);
```

#### Matching-Regel
```php
ContractMatchingRule::create([
    'supplier_id' => 1,
    'rule_name' => 'Vertragsnummer Exact Match',
    'source_field' => 'contract_number',
    'target_field' => 'contract_number',
    'match_type' => 'exact',
    'match_threshold' => 1.0,
    'is_active' => true,
]);
```

## Testing

### Test-Struktur
```
tests/
├── Unit/Services/
│   ├── SupplierRecognitionServiceTest.php
│   ├── RuleBasedExtractionServiceTest.php
│   └── ContractMatchingServiceTest.php
└── Feature/
    └── PdfAnalysisControllerTest.php
```

### Tests ausführen
```bash
# Alle Tests
php artisan test

# Nur Service-Tests
php artisan test tests/Unit/Services/

# Nur Feature-Tests
php artisan test tests/Feature/PdfAnalysisControllerTest.php
```

### Test-Coverage
- **SupplierRecognitionService**: 15 Tests, alle Pattern-Typen
- **RuleBasedExtractionService**: 20 Tests, alle Extraktionsmethoden
- **ContractMatchingService**: 25 Tests, alle Match-Typen
- **PdfAnalysisController**: 15 Feature-Tests, End-to-End-Szenarien

## Performance-Optimierung

### Caching-Strategien
- Pattern und Regeln werden pro Request gecacht
- Lieferanten-Daten werden in Memory gehalten
- Regex-Kompilierung wird optimiert

### Indexierung
Empfohlene Datenbankindizes:
```sql
-- Supplier Recognition Patterns
CREATE INDEX idx_srp_supplier_active ON supplier_recognition_patterns(supplier_id, is_active);
CREATE INDEX idx_srp_pattern_type ON supplier_recognition_patterns(pattern_type);

-- PDF Extraction Rules
CREATE INDEX idx_per_supplier_active ON pdf_extraction_rules(supplier_id, is_active);
CREATE INDEX idx_per_priority ON pdf_extraction_rules(priority);

-- Contract Matching Rules
CREATE INDEX idx_cmr_supplier_active ON contract_matching_rules(supplier_id, is_active);
CREATE INDEX idx_cmr_priority ON contract_matching_rules(priority);
```

## Monitoring und Logging

### Logging-Punkte
- Lieferanten-Erkennung mit Confidence-Scores
- Erfolgreiche/fehlgeschlagene Datenextraktionen
- Vertragszuordnungen und Match-Scores
- Performance-Metriken

### Monitoring-Metriken
- Erkennungsrate pro Lieferant
- Durchschnittliche Confidence-Scores
- Verarbeitungszeiten
- Fehlerquoten

## Wartung und Optimierung

### Regel-Optimierung
1. **Confidence-Analyse**: Regelmäßige Überprüfung der Confidence-Scores
2. **Pattern-Verfeinerung**: Anpassung basierend auf Fehlschlägen
3. **Performance-Monitoring**: Überwachung der Verarbeitungszeiten

### Automatische Regel-Generierung
Das System kann basierend auf Beispiel-PDFs automatisch Regeln vorschlagen:
```php
$generatedRules = $extractionService->generateRulesFromSamples($supplierId, $sampleTexts);
```

## Migration von der E.ON-Lösung

### Schritte
1. **Analyse bestehender E.ON-Regeln**: Extraktion der hardcodierten Logik
2. **Regel-Erstellung**: Übertragung in das neue System
3. **Testing**: Vergleich der Ergebnisse
4. **Schrittweise Migration**: Parallelbetrieb und Umstellung
5. **Cleanup**: Entfernung der alten Implementierung

### Kompatibilität
Das neue System ist vollständig rückwärtskompatibel und kann parallel zur bestehenden Lösung betrieben werden.

## Troubleshooting

### Häufige Probleme

#### Lieferant wird nicht erkannt
- **Ursache**: Keine passenden Pattern definiert
- **Lösung**: Pattern im Admin-Interface erstellen und testen

#### Datenextraktion fehlgeschlagen
- **Ursache**: Regex-Pattern stimmt nicht mit PDF-Format überein
- **Lösung**: Pattern mit Test-Funktion überprüfen und anpassen

#### Vertragszuordnung schlägt fehl
- **Ursache**: Match-Threshold zu hoch oder falsches Match-Type
- **Lösung**: Threshold anpassen oder anderen Match-Type verwenden

### Debug-Modus
Für detaillierte Analyse können Debug-Informationen aktiviert werden:
```php
$result = $analysisController->analyzeWithVariableSystem($request, true); // Debug-Modus
```

## Erweiterungsmöglichkeiten

### Geplante Features
1. **Machine Learning Integration**: Automatische Pattern-Erkennung
2. **OCR-Integration**: Verarbeitung gescannter PDFs
3. **Batch-Verarbeitung**: Massenverarbeitung von PDFs
4. **API-Erweiterungen**: RESTful API für externe Integration
5. **Reporting**: Detaillierte Analyse-Reports

### Plugin-Architektur
Das System ist erweiterbar durch:
- Custom Extraction Methods
- Custom Match Types
- Custom Data Types
- Custom Preprocessing Rules

## Support und Wartung

### Kontakt
- **Entwickler**: [Entwicklerteam]
- **Dokumentation**: Diese Datei
- **Issues**: [Issue-Tracker]

### Updates
- **Versionierung**: Semantic Versioning (SemVer)
- **Changelog**: Siehe CHANGELOG.md
- **Migration Guides**: Bei Breaking Changes

---

*Letzte Aktualisierung: 09.07.2025*
*Version: 1.0.0*