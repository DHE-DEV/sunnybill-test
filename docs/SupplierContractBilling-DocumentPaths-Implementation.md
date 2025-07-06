# SupplierContractBilling Document Path Integration

## Übersicht

Die Integration von **SupplierContractBilling** in das DocumentPathSetting-System wurde erfolgreich implementiert. Dies ermöglicht es, strukturierte und konfigurierbare Pfade für Dokumente zu Lieferanten-Abrechnungen zu verwenden.

## Implementierte Komponenten

### 1. DocumentPathSetting Model Erweiterung

**Datei:** `app/Models/DocumentPathSetting.php`

- **Dokumenttyp registriert:** `App\Models\SupplierContractBilling` → "Lieferanten-Abrechnung"
- **23 Platzhalter verfügbar** für flexible Pfad-Generierung
- **Standard-Konfigurationen** werden automatisch erstellt

#### Verfügbare Platzhalter:
```
{timestamp}              - Aktueller Zeitstempel (Y-m-d_H-i-s)
{date}                   - Aktuelles Datum (Y-m-d)
{year}                   - Aktuelles Jahr
{month}                  - Aktueller Monat
{day}                    - Aktueller Tag
{billing_number}         - Abrechnungsnummer
{supplier_invoice_number} - Lieferanten-Rechnungsnummer
{billing_type}           - Abrechnungstyp
{billing_year}           - Abrechnungsjahr
{billing_month}          - Abrechnungsmonat
{billing_period}         - Abrechnungsperiode (YYYY-MM)
{billing_status}         - Abrechnungsstatus
{billing_id}             - Abrechnungs-ID
{billing_title}          - Abrechnungstitel
{billing_date_year}      - Abrechnungsdatum Jahr
{billing_date_month}     - Abrechnungsdatum Monat
{billing_date_day}       - Abrechnungsdatum Tag
{contract_number}        - Vertragsnummer (vom zugehörigen Vertrag)
{contract_title}         - Vertragstitel (vom zugehörigen Vertrag)
{contract_id}            - Vertrags-ID (vom zugehörigen Vertrag)
{supplier_number}        - Lieferantennummer (vom zugehörigen Lieferanten)
{supplier_name}          - Lieferantenname (vom zugehörigen Lieferanten)
{supplier_id}            - Lieferanten-ID (vom zugehörigen Lieferanten)
```

### 2. DocumentUploadConfig Service Erweiterung

**Datei:** `app/Services/DocumentUploadConfig.php`

- **Neue Methode:** `forSupplierContractBillings(SupplierContractBilling $billing)`
- **8 Dokumentkategorien** definiert:
  - `invoice` → Rechnung
  - `credit_note` → Gutschrift
  - `statement` → Abrechnung
  - `correspondence` → Korrespondenz
  - `technical` → Technische Unterlagen
  - `certificates` → Nachweise
  - `supporting_documents` → Belege
  - `other` → Sonstiges

### 3. DocumentStorageService Integration

**Datei:** `app/Services/DocumentStorageService.php`

- **Erweiterte Methoden:**
  - `getUploadDirectoryForModel()` - Unterstützt jetzt SupplierContractBilling
  - `previewPath()` - Zeigt Pfad-Vorschau mit DocumentPathSetting-Integration
  - `getFallbackDirectory()` - Fallback für `supplier_contract_billings`

### 4. Standard-Konfigurationen

Die folgenden Standard-Pfad-Konfigurationen werden automatisch erstellt:

#### Standard-Pfad (ohne Kategorie)
```
Template: abrechnungen/{supplier_number}/{contract_number}/{billing_period}
Beispiel: abrechnungen/LF-0003/VTR-001/2024-04
```

#### Kategorien-spezifische Pfade
```
invoices:       abrechnungen/{supplier_number}/{contract_number}/{billing_period}/rechnungen
certificates:   abrechnungen/{supplier_number}/{contract_number}/{billing_period}/nachweise
correspondence: abrechnungen/{supplier_number}/{contract_number}/{billing_period}/korrespondenz
technical:      abrechnungen/{supplier_number}/{contract_number}/{billing_period}/unterlagen
```

## Verwendung

### 1. In Filament RelationManager

```php
use App\Services\DocumentUploadConfig;

// In der RelationManager-Klasse
protected function getDocumentUploadConfig(): DocumentUploadConfig
{
    return DocumentUploadConfig::forSupplierContractBillings($this->getOwnerRecord());
}
```

### 2. Direkte Pfad-Generierung

```php
use App\Services\DocumentStorageService;

// Mit Model und Kategorie
$uploadDir = DocumentStorageService::getUploadDirectoryForModel(
    'supplier_contract_billings',
    $billing,
    ['category' => 'invoices']
);
// Ergebnis: abrechnungen/LF-0003/VTR-001/2024-04/rechnungen

// Standard-Pfad (ohne Kategorie)
$uploadDir = DocumentStorageService::getUploadDirectoryForModel(
    'supplier_contract_billings',
    $billing
);
// Ergebnis: abrechnungen/LF-0003/VTR-001/2024-04
```

### 3. Pfad-Vorschau

```php
$preview = DocumentStorageService::previewPath(
    'supplier_contract_billings',
    $billing,
    ['category' => 'invoices']
);

// $preview enthält:
// - resolved_path: Der generierte Pfad
// - template: Das verwendete Template
// - placeholders_used: Verwendete Platzhalter
// - is_fallback: false (da DocumentPathSetting verwendet wird)
```

## Admin-Interface Integration

Die SupplierContractBilling-Pfade sind vollständig in das Admin-Interface unter `/admin/document-path-settings` integriert:

1. **Dokumenttyp-Auswahl:** "Lieferanten-Abrechnung" ist verfügbar
2. **Platzhalter-Vorschau:** Alle 23 Platzhalter werden angezeigt
3. **Kategorien:** Alle 8 Kategorien sind konfigurierbar
4. **Standard-Konfigurationen:** Werden automatisch erstellt

## Beispiel-Pfade

Für eine Abrechnung mit:
- Lieferant-Nr: `LF-0003`
- Vertragsnummer: `VTR-001`
- Abrechnungsperiode: `2024-04`

Werden folgende Pfade generiert:

```
Standard:       abrechnungen/LF-0003/VTR-001/2024-04/
Rechnungen:     abrechnungen/LF-0003/VTR-001/2024-04/rechnungen/
Nachweise:      abrechnungen/LF-0003/VTR-001/2024-04/nachweise/
Korrespondenz:  abrechnungen/LF-0003/VTR-001/2024-04/korrespondenz/
Unterlagen:     abrechnungen/LF-0003/VTR-001/2024-04/unterlagen/
```

## Vorteile

1. **Strukturierte Organisation:** Dokumente werden automatisch nach Lieferant, Vertrag und Periode organisiert
2. **Flexible Konfiguration:** Pfade können über das Admin-Interface angepasst werden
3. **Kategorisierung:** Verschiedene Dokumenttypen werden in separaten Unterordnern abgelegt
4. **Konsistenz:** Einheitliche Pfad-Struktur für alle Abrechnungsdokumente
5. **Skalierbarkeit:** System unterstützt beliebig viele Lieferanten und Verträge

## Status

✅ **Vollständig implementiert und getestet**
- DocumentPathSetting-Integration funktioniert
- DocumentUploadConfig generiert korrekte Pfade
- DocumentStorageService nutzt die neuen Pfad-Konfigurationen
- Admin-Interface zeigt alle Optionen korrekt an
- Standard-Konfigurationen werden automatisch erstellt
