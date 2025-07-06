# Erweiterte Abrechnungsliste für Subscription Details

## Übersicht

Die erweiterte Abrechnungsliste für Lieferantenverträge (Subscription Details) bietet eine umfassende Verwaltung und Darstellung aller zugehörigen Rechnungen mit erweiterten Funktionen.

## Implementierte Features

### 1. Erweiterte Spalten-Darstellung

- **Abrechnungsnummer**: Primärer Identifikator mit Kopier-Funktion
- **Anbieter-Rechnung**: Mit Lieferanten-Information als Beschreibung
- **Abrechnungstyp**: Badge-Darstellung (Rechnung/Gutschrift)
- **Abrechnungsperiode**: Monat/Jahr-Kombination als Badge
- **Titel**: Mit Tooltip für lange Titel und Beschreibung
- **Abrechnungsdatum**: Mit Fälligkeitsdatum als Beschreibung
- **Gesamtbetrag**: Farbkodiert nach Status
- **Status**: Badge mit Farbkodierung
- **Kostenträger**: Zusammenfassung der Aufteilung mit Tooltip
- **Dokumente**: Icon-Indikator für vorhandene Dokumente
- **Fälligkeit**: Intelligente Anzeige mit Farbkodierung

### 2. Umfassende Filter-Optionen

#### Basis-Filter
- **Status**: Mehrfachauswahl aller verfügbaren Status
- **Abrechnungstyp**: Rechnung oder Gutschrift
- **Jahr**: Mehrfachauswahl mit Standard auf aktuelles Jahr
- **Monat**: Mehrfachauswahl aller Monate
- **Quartal**: Q1-Q4 Auswahl

#### Erweiterte Filter
- **Betragsspanne**: Von/Bis Eingabe für Beträge
- **Abrechnungsdatum**: Datumsbereich-Filter
- **Fälligkeit**: Intelligente Fälligkeits-Filter
  - Überfällig
  - Heute fällig
  - Diese Woche fällig
  - Nächste Woche fällig
  - Diesen Monat fällig
  - Ohne Fälligkeitsdatum

#### Spezial-Filter
- **Kostenträger-Aufteilung**: Mit/Ohne Aufteilung
- **Vollständige Aufteilung**: 100% aufgeteilt oder nicht
- **Dokumente**: Mit/Ohne Dokumente
- **Anbieter-Rechnungsnummer**: Vorhanden/Fehlt

### 3. Erweiterte Aktionen

#### Einzelaktionen
- **Schnellansicht**: Modal mit detaillierter Übersicht
- **Vollständige Details**: Link zur Hauptansicht
- **Bearbeiten**: Modal-Bearbeitung
- **Duplizieren**: Kopie erstellen mit neuer Nummer
- **Kostenträger anzeigen**: Detaillierte Aufteilungsansicht
- **Löschen**: Standard-Löschfunktion

#### Bulk-Aktionen
- **Status ändern**: Mehrere Abrechnungen gleichzeitig
- **Export**: Ausgewählte Abrechnungen exportieren
- **Standard-Löschaktionen**: Löschen, Endgültig löschen, Wiederherstellen

#### Header-Aktionen
- **Neue Abrechnung**: Erweiterte Erstellung
- **Export**: Alle Abrechnungen exportieren
- **Statistiken**: Detaillierte Auswertungen

### 4. Intelligente Sortierung und Paginierung

- **Standard-Sortierung**: Nach Abrechnungsdatum absteigend
- **Persistente Sortierung**: Sortierung wird in Session gespeichert
- **Flexible Paginierung**: 10, 25, 50, 100 Einträge pro Seite
- **Standard**: 25 Einträge pro Seite

### 5. Performance-Optimierungen

- **Eager Loading**: Vorgeladene Beziehungen für bessere Performance
- **Deferred Loading**: Verzögertes Laden für große Datenmengen
- **Auto-Refresh**: Automatische Aktualisierung alle 30 Sekunden
- **Session-Persistenz**: Filter und Suche werden gespeichert

### 6. Benutzerfreundlichkeit

- **Responsive Design**: Optimiert für verschiedene Bildschirmgrößen
- **Tooltips**: Hilfreiche Zusatzinformationen
- **Farbkodierung**: Intuitive Statusanzeigen
- **Empty States**: Informative Leer-Zustände
- **Striped Rows**: Bessere Lesbarkeit

## Technische Details

### Dateien
- `app/Filament/Resources/SupplierContractResource/RelationManagers/BillingsRelationManager.php`
- `resources/views/filament/components/billing-detail-modal.blade.php`

### Abhängigkeiten
- Filament Tables
- Laravel Eloquent Relationships
- Carbon für Datumsberechnungen

### Performance-Überlegungen
- Eager Loading für verwandte Modelle
- Optimierte Queries für Aggregationen
- Caching von Filter-Optionen

## Verwendung

### Zugriff
Die erweiterte Abrechnungsliste ist verfügbar in der Detail-Ansicht jedes Lieferantenvertrags unter dem Tab "Abrechnungen".

### Workflow
1. **Filtern**: Verwenden Sie die umfassenden Filter-Optionen
2. **Sortieren**: Klicken Sie auf Spaltenüberschriften zum Sortieren
3. **Details anzeigen**: Nutzen Sie die Schnellansicht oder vollständige Details
4. **Aktionen ausführen**: Verwenden Sie die Aktions-Menüs für weitere Operationen
5. **Bulk-Operationen**: Wählen Sie mehrere Einträge für Massenaktionen

### Tipps
- Verwenden Sie die Statistiken-Funktion für Übersichten
- Nutzen Sie die Schnellansicht für schnelle Informationen
- Setzen Sie Filter-Kombinationen für spezifische Auswertungen
- Verwenden Sie die Export-Funktion für Berichte

## Zukünftige Erweiterungen

- PDF-Export-Funktionalität
- Excel-Export mit Formatierung
- Dashboard-Integration
- E-Mail-Benachrichtigungen für Fälligkeiten
- Automatische Erinnerungen
- Integration mit Buchhaltungssystemen