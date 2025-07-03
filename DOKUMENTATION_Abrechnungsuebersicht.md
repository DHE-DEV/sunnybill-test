# Abrechnungsübersicht für Solaranlagen

## Übersicht

Das Abrechnungsübersicht-System bietet eine zentrale Stelle zur Überwachung und Verwaltung der monatlichen Lieferantenabrechnungen für Solaranlagen. Es hilft dabei sicherzustellen, dass für jede Solaranlage alle erforderlichen Lieferantenrechnungen erfasst wurden, bevor eine Kundenabrechnung erstellt wird.

## Problem

- Eine Solaranlage kann mehrere Lieferantenverträge haben
- Jeder Lieferantenvertrag muss monatlich abgerechnet werden
- Eine Kundenabrechnung kann nur erstellt werden, wenn alle Lieferantenabrechnungen für den Monat vollständig sind
- Bisher gab es keine zentrale Übersicht über den Status der Abrechnungen

## Lösung

### 1. Neue Filament Resource: `SolarPlantBillingOverviewResource`

**Pfad:** `app/Filament/Resources/SolarPlantBillingOverviewResource.php`

**Features:**
- Übersicht aller Solaranlagen mit Abrechnungsstatus
- Status-Anzeige für aktuellen Monat und Vormonat
- Anzahl fehlender Abrechnungen pro Anlage
- Filter für vollständige/unvollständige Abrechnungen
- Aktion zum automatischen Erstellen fehlender Entwurfs-Abrechnungen

**Spalten in der Tabelle:**
- Anlagennummer
- Anlagenname
- Anzahl aktiver Verträge
- Status aktueller Monat (Vollständig/Unvollständig/Keine Verträge)
- Status Vormonat
- Anzahl fehlender Abrechnungen

### 2. Detailansicht: `ViewSolarPlantBillingOverview`

**Pfad:** `app/Filament/Resources/SolarPlantBillingOverviewResource/Pages/ViewSolarPlantBillingOverview.php`

**Features:**
- Detaillierte Übersicht der letzten 12 Monate
- Liste aller aktiven Lieferantenverträge
- Status jedes Monats mit fehlenden Verträgen
- Aktion zum Erstellen aller fehlenden Abrechnungen

**Informationen pro Monat:**
- Monat und Jahr
- Status (Vollständig/Unvollständig)
- Anzahl fehlender Abrechnungen
- Namen der fehlenden Verträge

### 3. Dashboard Widget: `BillingOverviewStatsWidget`

**Pfad:** `app/Filament/Widgets/BillingOverviewStatsWidget.php`

**Features:**
- Schnelle Übersicht auf dem Dashboard
- Statistiken für aktuellen und vorherigen Monat
- Prozentuale Vollständigkeit
- Gesamtanzahl Solaranlagen mit Verträgen

**Statistiken:**
- Aktueller Monat - Vollständig
- Aktueller Monat - Unvollständig
- Vormonat - Vollständig
- Gesamt Solaranlagen

## Verwendung

### 1. Abrechnungsübersicht aufrufen

1. Im Filament-Admin-Panel zu "Solaranlagen" → "Abrechnungsübersicht" navigieren
2. Übersicht aller Solaranlagen mit aktuellem Abrechnungsstatus
3. Filter verwenden um nur unvollständige Abrechnungen anzuzeigen

### 2. Fehlende Abrechnungen identifizieren

**Farbkodierung:**
- 🟢 **Grün (Vollständig):** Alle Lieferantenabrechnungen für den Monat sind erfasst
- 🟡 **Gelb (Unvollständig):** Mindestens eine Lieferantenabrechnung fehlt
- ⚪ **Grau (Keine Verträge):** Keine aktiven Lieferantenverträge vorhanden

### 3. Fehlende Abrechnungen erstellen

**Option 1: Einzelne Anlage**
1. Auf "Details anzeigen" bei einer Solaranlage klicken
2. "Fehlende Abrechnungen erstellen" Button verwenden
3. Entwurfs-Abrechnungen werden automatisch erstellt

**Option 2: Alle fehlenden Abrechnungen**
1. In der Detailansicht einer Anlage
2. "Alle fehlenden Abrechnungen erstellen" verwenden
3. Erstellt Entwürfe für alle unvollständigen Monate

### 4. Workflow für monatliche Abrechnung

1. **Monatsbeginn:** Abrechnungsübersicht prüfen
2. **Fehlende identifizieren:** Filter "Unvollständig" verwenden
3. **Entwürfe erstellen:** Automatische Erstellung von Entwurfs-Abrechnungen
4. **Belege erfassen:** Lieferantenrechnungen in die Entwürfe eintragen
5. **Status prüfen:** Erneute Kontrolle der Vollständigkeit
6. **Kundenabrechnung:** Erst nach Vollständigkeit erstellen

## Technische Details

### Status-Berechnung

```php
public static function getBillingStatusForMonth(SolarPlant $solarPlant, string $month): string
{
    $activeContracts = $solarPlant->activeSupplierContracts;
    
    if ($activeContracts->isEmpty()) {
        return 'Keine Verträge';
    }

    $year = (int) substr($month, 0, 4);
    $monthNumber = (int) substr($month, 5, 2);

    $contractsWithBilling = $activeContracts->filter(function ($contract) use ($year, $monthNumber) {
        return $contract->billings()
            ->where('billing_year', $year)
            ->where('billing_month', $monthNumber)
            ->exists();
    });

    return $contractsWithBilling->count() === $activeContracts->count() ? 'Vollständig' : 'Unvollständig';
}
```

### Automatische Entwurfs-Erstellung

```php
SupplierContractBilling::create([
    'supplier_contract_id' => $contract->id,
    'billing_type' => 'invoice',
    'billing_year' => $year,
    'billing_month' => $month,
    'title' => "Abrechnung {$month} - {$contract->title}",
    'billing_date' => now(),
    'total_amount' => 0,
    'currency' => 'EUR',
    'status' => 'draft',
]);
```

## Navigation

**Menüpunkt:** Solaranlagen → Abrechnungsübersicht
**Icon:** 📋 (clipboard-document-check)
**Sortierung:** 5 (nach anderen Solaranlagen-Menüs)

## Berechtigungen

Das System verwendet die Standard-Filament-Berechtigungen. Benutzer benötigen:
- Lesezugriff auf SolarPlant-Model
- Schreibzugriff auf SupplierContractBilling-Model (für Entwurfs-Erstellung)

## Erweiterungsmöglichkeiten

### Zukünftige Features
1. **E-Mail-Benachrichtigungen:** Automatische Erinnerungen bei fehlenden Abrechnungen
2. **Bulk-Aktionen:** Massenbearbeitung mehrerer Anlagen
3. **Export-Funktionen:** Excel/PDF-Export der Übersicht
4. **Zeitraum-Filter:** Auswahl verschiedener Monate/Jahre
5. **Automatisierung:** Automatische Erstellung von Entwürfen am Monatsende
6. **Integration:** Verknüpfung mit Kundenabrechnung-System

### Performance-Optimierungen
- Caching der Status-Berechnungen
- Eager Loading der Beziehungen
- Indizierung der Datenbankabfragen

## Wartung

### Regelmäßige Aufgaben
1. **Monatlich:** Prüfung der Abrechnungsübersicht
2. **Quartalsweise:** Kontrolle der Datenintegrität
3. **Jährlich:** Performance-Review und Optimierung

### Monitoring
- Dashboard-Widget zeigt aktuelle Statistiken
- Farbkodierung ermöglicht schnelle Problemerkennung
- Filter helfen bei der gezielten Bearbeitung