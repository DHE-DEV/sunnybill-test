# SupplierContract & SupplierContractBilling Document Path Configuration

## Übersicht

Die DocumentPathSetting-Funktionalität wurde erweitert, um vollständige Unterstützung für SupplierContract-Dokumente und SupplierContractBilling-Dokumente zu bieten. Dies ermöglicht eine strukturierte und automatisierte Verwaltung von Dokumenten, die zu Lieferantenverträgen und deren Abrechnungen gehören.

## Neue Funktionen

### 1. SupplierContract als Dokumenttyp

- **Dokumenttyp**: `App\Models\SupplierContract` → "Lieferantenvertrag"
- Vollständige Integration in das bestehende DocumentPathSetting-System
- Unterstützung für alle verfügbaren Kategorien

### 2. Verfügbare Platzhalter

Für SupplierContract-Dokumente stehen folgende Platzhalter zur Verfügung:

#### Standard-Platzhalter
- `{timestamp}` - Aktueller Zeitstempel (Y-m-d_H-i-s)
- `{date}` - Aktuelles Datum (Y-m-d)
- `{year}` - Aktuelles Jahr
- `{month}` - Aktueller Monat
- `{day}` - Aktueller Tag

#### SupplierContract-spezifische Platzhalter
- `{contract_number}` - Vertragsnummer
- `{contract_title}` - Vertragstitel
- `{contract_id}` - Vertrags-ID
- `{contract_status}` - Vertragsstatus
- `{supplier_number}` - Lieferantennummer (vom zugehörigen Lieferanten)
- `{supplier_name}` - Lieferantenname (vom zugehörigen Lieferanten)
- `{supplier_id}` - Lieferanten-ID (vom zugehörigen Lieferanten)
- `{contract_start_year}` - Vertragsbeginn Jahr
- `{contract_start_month}` - Vertragsbeginn Monat

### 3. Standard-Pfadkonfigurationen

Bei der Erstellung der Standard-Konfigurationen werden automatisch folgende Pfade für SupplierContract erstellt:

#### Standard-Pfad (keine Kategorie)
- **Template**: `vertraege/{supplier_number}/{contract_number}`
- **Beschreibung**: Standard-Pfad für Dokumente zu Vertragsdaten
- **Beispiel**: `vertraege/LF-001/V-2024-001`

#### Vertragsdokumente (Kategorie: contracts)
- **Template**: `vertraege/{supplier_number}/{contract_number}/vertragsdokumente`
- **Beschreibung**: Pfad für Vertragsdokumente und Anhänge
- **Beispiel**: `vertraege/LF-001/V-2024-001/vertragsdokumente`

#### Korrespondenz (Kategorie: correspondence)
- **Template**: `vertraege/{supplier_number}/{contract_number}/korrespondenz`
- **Beschreibung**: Pfad für Korrespondenz zu Verträgen
- **Beispiel**: `vertraege/LF-001/V-2024-001/korrespondenz`

#### Abrechnungen (Kategorie: invoices)
- **Template**: `vertraege/{supplier_number}/{contract_number}/abrechnungen/{year}`
- **Beschreibung**: Pfad für Abrechnungen zu Verträgen
- **Beispiel**: `vertraege/LF-001/V-2024-001/abrechnungen/2024`

## SupplierContractBilling-Unterstützung

### 1. SupplierContractBilling als Dokumenttyp

- **Dokumenttyp**: `App\Models\SupplierContractBilling` → "Lieferanten-Abrechnung"
- Vollständige Integration für Abrechnungsdokumente
- Unterstützung für alle verfügbaren Kategorien

### 2. SupplierContractBilling-spezifische Platzhalter

Zusätzlich zu den Standard-Platzhaltern stehen folgende spezifische Platzhalter zur Verfügung:

#### Abrechnungs-spezifische Platzhalter
- `{billing_number}` - Abrechnungsnummer
- `{supplier_invoice_number}` - Lieferanten-Rechnungsnummer
- `{billing_type}` - Abrechnungstyp
- `{billing_year}` - Abrechnungsjahr
- `{billing_month}` - Abrechnungsmonat
- `{billing_period}` - Abrechnungsperiode (YYYY-MM)
- `{billing_status}` - Abrechnungsstatus
- `{billing_id}` - Abrechnungs-ID
- `{billing_title}` - Abrechnungstitel
- `{billing_date_year}` - Abrechnungsdatum Jahr
- `{billing_date_month}` - Abrechnungsdatum Monat
- `{billing_date_day}` - Abrechnungsdatum Tag

#### Vertrags- und Lieferanten-Platzhalter (vom zugehörigen Vertrag)
- `{contract_number}` - Vertragsnummer
- `{contract_title}` - Vertragstitel
- `{contract_id}` - Vertrags-ID
- `{supplier_number}` - Lieferantennummer
- `{supplier_name}` - Lieferantenname
- `{supplier_id}` - Lieferanten-ID

### 3. Standard-Pfadkonfigurationen für SupplierContractBilling

#### Standard-Pfad (keine Kategorie)
- **Template**: `abrechnungen/{supplier_number}/{contract_number}/{billing_period}`
- **Beschreibung**: Standard-Pfad für Lieferanten-Abrechnungsdokumente
- **Beispiel**: `abrechnungen/LF-001/V-2024-001/2024-03`

#### Rechnungen (Kategorie: invoices)
- **Template**: `abrechnungen/{supplier_number}/{contract_number}/{billing_period}/rechnungen`
- **Beschreibung**: Pfad für Abrechnungs-Rechnungsdokumente
- **Beispiel**: `abrechnungen/LF-001/V-2024-001/2024-03/rechnungen`

#### Korrespondenz (Kategorie: correspondence)
- **Template**: `abrechnungen/{supplier_number}/{contract_number}/{billing_period}/korrespondenz`
- **Beschreibung**: Pfad für Korrespondenz zu Abrechnungen
- **Beispiel**: `abrechnungen/LF-001/V-2024-001/2024-03/korrespondenz`

#### Technische Unterlagen (Kategorie: technical)
- **Template**: `abrechnungen/{supplier_number}/{contract_number}/{billing_period}/unterlagen`
- **Beschreibung**: Pfad für technische Unterlagen zu Abrechnungen
- **Beispiel**: `abrechnungen/LF-001/V-2024-001/2024-03/unterlagen`

#### Nachweise (Kategorie: certificates)
- **Template**: `abrechnungen/{supplier_number}/{contract_number}/{billing_period}/nachweise`
- **Beschreibung**: Pfad für Nachweise und Zertifikate zu Abrechnungen
- **Beispiel**: `abrechnungen/LF-001/V-2024-001/2024-03/nachweise`

### 4. Beispiel-Templates für SupplierContractBilling

```
# Nach Abrechnungstyp organisiert
abrechnungen/{supplier_number}/{billing_type}/{billing_period}

# Mit Abrechnungsnummer
abrechnungen/{supplier_number}/{contract_number}/{billing_number}

# Jahresbasierte Organisation
abrechnungen/{billing_year}/{supplier_number}/{contract_number}/{billing_month}

# Status-basierte Organisation
abrechnungen/{supplier_number}/{billing_status}/{billing_period}
```

## Verwendung

### 1. Standard-Konfigurationen erstellen

1. Navigieren Sie zu: `https://sunnybill-test.test/admin/document-path-settings`
2. Klicken Sie auf "Standard-Konfigurationen erstellen"
3. Die neuen SupplierContract-Pfadkonfigurationen werden automatisch erstellt

### 2. Individuelle Anpassungen

Sie können die Standard-Pfade nach Bedarf anpassen:

1. Gehen Sie zur Dokumentpfad-Einstellungen-Übersicht
2. Bearbeiten Sie bestehende SupplierContract-Konfigurationen
3. Oder erstellen Sie neue, spezifische Konfigurationen

### 3. Beispiel-Templates

Hier sind einige nützliche Template-Beispiele:

```
# Nach Lieferant und Jahr organisiert
vertraege/{supplier_number}/{contract_start_year}/{contract_number}

# Mit Vertragsstatus
vertraege/{supplier_number}/{contract_status}/{contract_number}

# Zeitbasierte Organisation
vertraege/{contract_start_year}/{supplier_number}/{contract_number}

# Detaillierte Struktur mit Kategorien
vertraege/{supplier_number}/{contract_number}/{category}/{year}
```

## Technische Details

### Model-Integration

Die SupplierContract-Unterstützung wurde in folgenden Bereichen implementiert:

1. **DocumentPathSetting::getDocumentableTypes()** - Hinzufügung des SupplierContract-Typs
2. **DocumentPathSetting::addModelPlaceholders()** - SupplierContract-spezifische Platzhalter-Logik
3. **DocumentPathSetting::getAvailablePlaceholders()** - Platzhalter-Definitionen für die UI
4. **DocumentPathSetting::createDefaults()** - Standard-Konfigurationen

### Platzhalter-Auflösung

Die Platzhalter werden automatisch aufgelöst basierend auf:
- Den Eigenschaften des SupplierContract-Models
- Den Eigenschaften des zugehörigen Supplier-Models (über Relation)
- Datum-basierten Informationen (start_date)
- Standard-Zeitstempel-Platzhaltern

### Pfad-Bereinigung

Alle generierten Pfade werden automatisch bereinigt:
- Gefährliche Zeichen werden entfernt/ersetzt
- Mehrfache Slashes werden reduziert
- Pfade werden normalisiert

## Vorteile

1. **Strukturierte Organisation**: Dokumente werden automatisch in einer logischen Struktur organisiert
2. **Konsistenz**: Einheitliche Pfadstrukturen für alle Verträge
3. **Flexibilität**: Anpassbare Templates für verschiedene Anforderungen
4. **Automatisierung**: Keine manuelle Pfaderstellung erforderlich
5. **Integration**: Nahtlose Integration in das bestehende Dokumentensystem

## Wartung

- Die Konfigurationen können jederzeit über die Admin-Oberfläche angepasst werden
- Neue Platzhalter können bei Bedarf hinzugefügt werden
- Die Standard-Konfigurationen können erneut ausgeführt werden, um Updates zu übernehmen

## Support

Bei Fragen oder Problemen mit der SupplierContract-Dokumentpfad-Konfiguration wenden Sie sich an das Entwicklungsteam.
