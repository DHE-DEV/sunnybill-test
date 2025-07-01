# DigitalOcean Storage Test - Dokumentation

## Übersicht

Das `TestDigitalOceanStorage` Command ist ein umfassendes Testtool für die DigitalOcean Spaces Storage-Funktionalität in SunnyBill. Es testet den kompletten Workflow von der Konfiguration bis zum Download.

## Verwendung

```bash
# Vollständiger Test mit Debug-Ausgaben
php artisan storage:test-digitalocean --debug

# Normaler Test ohne Debug-Details
php artisan storage:test-digitalocean

# Nur Aufräumen von Test-Dateien
php artisan storage:test-digitalocean --cleanup
```

## Test-Schritte

Das Tool führt folgende Schritte durch:

### 1. Admin-Einstellungen laden
- Lädt aktuelle Storage-Einstellungen aus der Datenbank
- Validiert DigitalOcean-Konfiguration
- Prüft Debug-Allocations

### 2. Storage-Verbindung testen
- Testet Netzwerk-Konnektivität zu DigitalOcean
- Validiert Credentials (Access Key, Secret Key)
- Prüft Space-Zugriff und Berechtigungen

### 3. Testverzeichnis erstellen
- Erstellt ein eindeutiges Testverzeichnis mit Zeitstempel
- Verifiziert erfolgreiche Erstellung

### 4. PDF-Datei hochladen
- Generiert eine gültige Test-PDF-Datei
- Lädt die Datei in das Testverzeichnis hoch
- Prüft Dateigröße und MIME-Type

### 5. Metadaten extrahieren
- Extrahiert Datei-Metadaten mit DocumentStorageService
- Validiert alle erforderlichen Felder
- Simuliert Benutzer-Authentifizierung

### 6. Datenbank-Eintrag erstellen
- Erstellt vollständigen Document-Eintrag in der Datenbank
- Verifiziert alle Pflichtfelder
- Lädt Dokument zur Verifikation neu

### 7. Download testen
- Lädt die Datei von DigitalOcean herunter
- Vergleicht Inhalt mit Original
- Prüft Datei-Integrität

### 8. Aufräumen
- Löscht alle Test-Dateien von DigitalOcean
- Entfernt Datenbank-Einträge
- Hinterlässt keine Test-Artefakte

## Ausgabe-Beispiel

```
🚀 DigitalOcean Storage Volltest gestartet
=============================================================

✅ Admin-Einstellungen laden erfolgreich (1.25ms)
✅ Storage-Verbindung testen erfolgreich (1275.6ms)
✅ Testverzeichnis erstellen erfolgreich (269.54ms)
✅ PDF-Datei hochladen erfolgreich (152.81ms)
✅ Metadaten extrahieren erfolgreich (81.07ms)
✅ Datenbank-Eintrag erstellen erfolgreich (75.3ms)
✅ Download testen erfolgreich (77.04ms)
✅ Aufräumen erfolgreich (273.36ms)

📊 TEST-ZUSAMMENFASSUNG
Erfolgreiche Schritte: 8/8
Gesamtdauer: 2205.97ms
🎉 ALLE TESTS ERFOLGREICH!
```

## Debug-Modus

Mit `--debug` werden zusätzliche Informationen angezeigt:

- Detaillierte Konfigurationsdaten
- Schritt-für-Schritt Verfolgen
- Metadaten-Details
- Datei-Größen und Checksums
- Datenbank-Feldwerte

## Fehlerbehebung

### Häufige Probleme

1. **403 Forbidden Fehler**
   - Prüfen Sie Access Key und Secret Key
   - Kontrollieren Sie Space-Berechtigungen
   - Überprüfen Sie IP-Beschränkungen

2. **Netzwerk-Timeout**
   - Prüfen Sie Firewall-Einstellungen
   - Testen Sie Internetverbindung
   - Kontrollieren Sie DNS-Auflösung

3. **Datenbank-Fehler**
   - Prüfen Sie Datenbank-Schema
   - Kontrollieren Sie Benutzer-Authentifizierung
   - Validieren Sie Pflichtfelder

### Logs

Das Tool schreibt detaillierte Logs nach:
- `storage/logs/laravel.log`
- Console-Ausgabe mit Zeitstempel

## Konfiguration

Das Tool verwendet die Einstellungen aus:
- **Storage-Einstellungen**: `http://sunnybill-test.test/admin/storage-settings`
- **Debug-Allocations**: `http://sunnybill-test.test/admin/debug-allocations`

## Sicherheit

- Test-Dateien werden automatisch gelöscht
- Keine sensiblen Daten in Logs
- Temporäre Verzeichnisse mit Zeitstempel
- Sichere Credential-Validierung

## Performance

Typische Ausführungszeiten:
- Lokaler Test: ~500ms
- DigitalOcean Test: ~2000ms
- Abhängig von Netzwerk-Latenz

## Integration

Das Tool kann in CI/CD-Pipelines integriert werden:

```bash
# Exit Code 0 = Erfolg, 1 = Fehler
php artisan storage:test-digitalocean
echo $? # Prüft Exit Code
```

## Entwicklung

Zum Erweitern des Tools:

1. Neue Test-Schritte in `testStep()` hinzufügen
2. Debug-Ausgaben mit `$this->verbose` steuern
3. Cleanup-Logik für neue Ressourcen erweitern
4. Fehlerbehandlung für spezifische Szenarien

## Siehe auch

- [`TestStorageConnection`](../app/Console/Commands/TestStorageConnection.php) - Basis Storage-Test
- [`DocumentStorageService`](../app/Services/DocumentStorageService.php) - Storage-Service
- [`StorageSetting`](../app/Models/StorageSetting.php) - Konfiguration