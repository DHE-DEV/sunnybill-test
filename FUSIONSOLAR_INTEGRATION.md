# FusionSolar Integration

Diese Anwendung unterstützt die automatische Synchronisation von Solaranlagen-Daten über die Huawei FusionSolar Northbound API.

## Konfiguration

### 1. FusionSolar Northbound-Schnittstelle einrichten

1. Melden Sie sich bei Ihrem FusionSolar-Portal an
2. Navigieren Sie zu **System Management** → **Northbound Interface**
3. Erstellen Sie einen neuen Northbound-Benutzer mit:
   - **Username**: Ihr gewählter Benutzername
   - **Password**: Ihr gewähltes Passwort (wird als systemCode verwendet)
   - **Permissions**: Aktivieren Sie ALLE verfügbaren Berechtigungen

**⚠️ WICHTIG - Berechtigungen konfigurieren:**

4. Nach dem Erstellen des Northbound-Benutzers:
   - Gehen Sie zu **System Management** → **User Management**
   - Suchen Sie Ihren Northbound-Benutzer
   - Klicken Sie auf **"Authorize"** oder **"Berechtigen"**
   - **Weisen Sie dem Benutzer Zugriff auf ALLE Ihre Anlagen zu**
   - Aktivieren Sie alle verfügbaren API-Berechtigungen:
     - Station List Query
     - Station Real-time Data Query
     - Station Historical Data Query
     - Device List Query
     - Device Real-time Data Query
     - Alarm Query

5. Speichern Sie die Konfiguration und warten Sie 5-10 Minuten, bis die Änderungen aktiv werden

### 2. Umgebungsvariablen konfigurieren

Fügen Sie folgende Variablen zu Ihrer `.env` Datei hinzu:

```env
# FusionSolar API Configuration
FUSIONSOLAR_BASE_URL=https://eu5.fusionsolar.huawei.com/thirdData
FUSIONSOLAR_USERNAME=ihr_northbound_username
FUSIONSOLAR_PASSWORD=ihr_northbound_password
```

**Wichtige Hinweise:**
- Die `FUSIONSOLAR_PASSWORD` ist der **systemCode** aus der Northbound-Konfiguration
- Für andere Regionen ändern Sie die `FUSIONSOLAR_BASE_URL`:
  - Europa: `https://eu5.fusionsolar.huawei.com/thirdData`
  - Asien-Pazifik: `https://ap5.fusionsolar.huawei.com/thirdData`
  - Amerika: `https://la5.fusionsolar.huawei.com/thirdData`

## Verwendung

### Automatische Synchronisation über die Web-Oberfläche

1. Navigieren Sie zu **Solaranlagen** in der Filament-Oberfläche
2. Klicken Sie auf **"Von FusionSolar synchronisieren"**
3. Bestätigen Sie die Synchronisation
4. Die Anlagen werden automatisch importiert oder aktualisiert

### Manuelle Synchronisation über die Kommandozeile

```bash
# Alle Anlagen synchronisieren
php artisan fusionsolar:sync

# Synchronisation erzwingen (auch kürzlich synchronisierte Anlagen)
php artisan fusionsolar:sync --force
```

### Automatische Synchronisation einrichten

Fügen Sie folgenden Cron-Job zu Ihrem System hinzu für stündliche Synchronisation:

```bash
# Crontab-Eintrag für stündliche Synchronisation
0 * * * * cd /pfad/zu/ihrer/anwendung && php artisan fusionsolar:sync >> /dev/null 2>&1
```

## Synchronisierte Daten

Die Integration synchronisiert folgende Daten von FusionSolar:

### Anlagen-Grunddaten
- **Name**: Anlagenname aus FusionSolar
- **Standort**: Anlagen-Adresse
- **Gesamtleistung**: Installierte Kapazität in kW
- **Installationsdatum**: Errichtungsdatum der Anlage
- **Status**: Aktueller Anlagenstatus (Aktiv, Wartung, Offline)

### Technische Daten
- **Erwarteter Jahresertrag**: Prognostizierte jährliche Energieproduktion
- **Anlagenstatus**: Betriebszustand der Anlage

### Metadaten
- **FusionSolar ID**: Eindeutige Kennung in FusionSolar
- **Letzter Sync**: Zeitpunkt der letzten Synchronisation

## Status-Mapping

FusionSolar-Status werden wie folgt auf das interne System gemappt:

| FusionSolar Status | Interner Status | Beschreibung |
|-------------------|-----------------|--------------|
| 1 (Normal)        | active          | Anlage läuft normal |
| 2 (Alarm)         | maintenance     | Anlage hat Alarme/Wartung nötig |
| 3 (Offline)       | inactive        | Anlage ist offline |
| Andere            | planned         | Unbekannter Status |

## Verfügbare API-Funktionen

Der `FusionSolarService` bietet folgende Methoden:

### Anlagen-Management
- `getPlantList()`: Alle verfügbaren Anlagen abrufen
- `getPlantDetails($stationCode)`: Detaillierte Anlagen-Informationen
- `syncAllPlants()`: Alle Anlagen synchronisieren

### Echtzeitdaten
- `getPlantRealtimeData($stationCode)`: Aktuelle Leistungsdaten
- `getPlantHistoricalData($stationCode, $start, $end)`: Historische Daten

### Ertragsdaten
- `getMonthlyYield($stationCode, $year, $month)`: Monatliche Ertragsdaten

### Alarme
- `getPlantAlarms($stationCode)`: Aktuelle Anlagen-Alarme

## Fehlerbehebung

### Häufige Probleme

**1. Authentifizierung fehlgeschlagen**
- Prüfen Sie Username und Password in der `.env` Datei
- Stellen Sie sicher, dass der Northbound-Benutzer korrekt konfiguriert ist
- Überprüfen Sie die Base-URL für Ihre Region

**2. Keine Anlagen gefunden**
- Stellen Sie sicher, dass Ihr Northbound-Benutzer Zugriff auf die Anlagen hat
- Prüfen Sie die Berechtigungen in der FusionSolar-Konfiguration

**3. Synchronisation schlägt fehl**
- Überprüfen Sie die Logs in `storage/logs/laravel.log`
- Testen Sie die Verbindung mit `php artisan fusionsolar:sync`

### Logs

Alle FusionSolar-Aktivitäten werden in den Laravel-Logs protokolliert:

```bash
# Logs anzeigen
tail -f storage/logs/laravel.log | grep FusionSolar
```

## Sicherheit

- **API-Credentials**: Speichern Sie niemals Ihre FusionSolar-Zugangsdaten im Code
- **HTTPS**: Die API verwendet ausschließlich verschlüsselte Verbindungen
- **Token-Management**: Authentifizierungs-Token werden automatisch verwaltet und erneuert

## Erweiterte Konfiguration

### Custom Base-URL
Für spezielle Installationen können Sie eine custom Base-URL verwenden:

```env
FUSIONSOLAR_BASE_URL=https://ihre-custom-domain.com/thirdData
```

### Timeout-Konfiguration
Die HTTP-Timeouts können in der `FusionSolarService` Klasse angepasst werden.

## Support

Bei Problemen mit der FusionSolar-Integration:

1. Überprüfen Sie die Logs
2. Testen Sie die Verbindung mit dem Sync-Command
3. Stellen Sie sicher, dass alle Umgebungsvariablen korrekt gesetzt sind
4. Kontaktieren Sie den Support mit den relevanten Log-Einträgen