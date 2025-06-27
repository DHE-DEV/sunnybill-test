# Lieferanten-Daten Reset

## Überblick

Der `suppliers:reset` Befehl ermöglicht es, alle Lieferanten-Daten komplett zurückzusetzen und neue Testdaten zu erstellen. Dies ist nützlich für Entwicklung und Testing.

## Verwendung

### Interaktiver Reset (mit Bestätigung)
```bash
php artisan suppliers:reset
```

### Automatischer Reset (ohne Bestätigung)
```bash
php artisan suppliers:reset --force
```

## Was wird gelöscht

Der Befehl löscht in folgender Reihenfolge:

1. **Solaranlagen-Lieferanten-Zuordnungen** (`solar_plant_suppliers`)
   - Alle Zuordnungen von Lieferanten zu Solaranlagen
   - Inklusive Rollen, Notizen und Ansprechpartner-Zuordnungen

2. **Solaranlagen-Notizen** (`solar_plant_notes`)
   - Alle Notizen zu Solaranlagen
   - Verschiedene Typen: Allgemein, Wartung, Probleme, Verbesserungen

3. **Telefonnummern der Lieferanten-Mitarbeiter** (`phone_numbers`)
   - Alle Telefonnummern von Lieferanten-Mitarbeitern
   - Geschäftlich, privat und mobil

4. **Lieferanten-Notizen** (`supplier_notes`)
   - Alle Notizen zu Lieferanten

5. **Lieferanten-Mitarbeiter** (`supplier_employees`)
   - Alle Mitarbeiter-Datensätze der Lieferanten

6. **Lieferanten** (`suppliers`)
   - Alle Lieferanten-Stammdaten

## Was wird neu erstellt

Nach dem Löschen werden neue Testdaten erstellt:

- **3 Lieferanten**:
  - SolarTech GmbH (Installateur)
  - Green Energy Components AG (Komponenten-Lieferant)
  - Solar Service Nord (Wartungsservice)

- **5 Mitarbeiter** mit verschiedenen Positionen
- **6 Telefonnummern** (geschäftlich und mobil)
- **4 Lieferanten-Notizen** mit Beispielinhalten
- **9 Solaranlagen-Notizen** (3 pro Anlage):
  - Allgemeine Notizen zur Installation und Inbetriebnahme
  - Wartungsnotizen mit Details zu durchgeführten Arbeiten
  - Verbesserungsvorschläge und Optimierungen
- **15 Solaranlagen-Zuordnungen** (mehrere Lieferanten pro Anlage):
  - Jede Anlage hat mehrere Lieferanten mit verschiedenen Rollen
  - Verschiedene Ansprechpartner für unterschiedliche Bereiche
  - Realistische Projektabläufe mit Start- und Enddaten

## Sicherheit

- Der Befehl fragt standardmäßig nach Bestätigung
- Mit `--force` wird die Bestätigung übersprungen
- **ACHTUNG**: Alle Lieferanten-Daten gehen unwiderruflich verloren!

## Beispiel-Output

```
Starting supplier data reset...
Deleting solar plant supplier assignments...
Deleted 15 supplier assignments.
Deleting solar plant notes...
Deleted 9 solar plant notes.
Deleting supplier employee phone numbers...
Deleted 6 phone numbers.
Deleting supplier notes...
Deleted 4 supplier notes.
Deleting supplier employees...
Deleted 5 supplier employees.
Deleting suppliers...
Deleted 3 suppliers.
Creating new test data...
Solar plant notes created successfully.
New supplier test data created successfully.

Reset completed successfully!
New data summary:
- Suppliers: 3
- Employees: 5
- Phone numbers: 6
- Supplier notes: 4
- Solar plant notes: 9
- Solar plant assignments: 15
```

## Verwendungszwecke

- **Entwicklung**: Saubere Testdaten für neue Features
- **Testing**: Konsistente Ausgangslage für Tests
- **Demo**: Vorbereitung für Präsentationen
- **Fehlerbehebung**: Reset bei Datenproblemen

## Technische Details

- Verwendet `DELETE` statt `TRUNCATE` wegen Foreign Key Constraints
- Respektiert die Lösch-Reihenfolge für referenzielle Integrität
- Führt automatisch den `SupplierSeeder` aus
- Zeigt detaillierte Statistiken über gelöschte und erstellte Datensätze