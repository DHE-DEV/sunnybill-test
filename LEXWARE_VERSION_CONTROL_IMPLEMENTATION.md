# Lexware Version Control Implementation

## Übersicht

Diese Implementierung löst das Problem der Versionskontrolle bei Lexoffice-Updates durch:

1. **Automatisches Abrufen der aktuellen Versionsnummer** vor jedem Update
2. **Speicherung der kompletten Lexware-Daten** für Analyse und Debugging
3. **Erweiterte Logging-Funktionalität** für bessere Nachverfolgung
4. **UI-Integration** für manuelle Datenabfrage

## Implementierte Komponenten

### 1. Datenbank-Migration
**Datei:** `database/migrations/2025_07_07_230200_add_lexware_fields_to_customers_table.php`

```sql
-- Neue Felder in customers Tabelle:
lexware_version INTEGER NULL        -- Aktuelle Versionsnummer von Lexoffice
lexware_json JSON NULL             -- Komplette JSON-Daten von Lexoffice
```

### 2. Customer Model Erweiterung
**Datei:** `app/Models/Customer.php`

- Neue Felder zu `$fillable` hinzugefügt
- JSON-Casting für `lexware_json` implementiert
- Integer-Casting für `lexware_version`

### 3. LexofficeService Erweiterung
**Datei:** `app/Services/LexofficeService.php`

#### Neue Methode: `fetchAndStoreLexwareData()`
```php
public function fetchAndStoreLexwareData(Customer $customer): array
```

**Funktionalität:**
- GET Request zu `https://api.lexoffice.io/v1/contacts/{id}`
- Speichert `version` und komplette JSON-Daten
- Erweiterte Logging mit Performance-Metriken
- Fehlerbehandlung mit detailliertem Logging

**Rückgabe:**
```php
[
    'success' => true/false,
    'version' => int,           // Aktuelle Versionsnummer
    'data' => array,           // Komplette Lexoffice-Daten
    'message' => string,       // Erfolgsmeldung
    'duration_ms' => float,    // Ausführungszeit
    'error' => string          // Fehlermeldung (bei Fehler)
]
```

### 4. UI-Integration

#### ViewCustomer Page
**Datei:** `app/Filament/Resources/CustomerResource/Pages/ViewCustomer.php`

**Neue Aktion:** "Lexware-Daten abrufen"
- Button nur sichtbar wenn `lexoffice_id` vorhanden
- Bestätigungsdialog vor Ausführung
- Erfolgs-/Fehlermeldungen mit Details
- Automatisches Neuladen der Seite nach Erfolg

#### CustomerResource Infolist
**Datei:** `app/Filament/Resources/CustomerResource.php`

**Erweiterte Lexoffice-Sektion:**
- Anzeige der aktuellen Lexware-Version als Badge
- Status der gespeicherten JSON-Daten (Größe in KB)
- Farbkodierte Status-Badges

### 5. Erweiterte Logging-Funktionalität

**Neue Log-Einträge:**
- `fetch_lexware_data` - Abrufen der Lexware-Daten
- Performance-Metriken (Ausführungszeit, Datengröße)
- Detaillierte Fehlerprotokollierung
- Versions-Tracking für Änderungshistorie

## Verwendung

### 1. Manuelle Datenabfrage über UI
1. Kunde in Filament öffnen (View-Modus)
2. Button "Lexware-Daten abrufen" klicken
3. Bestätigen
4. Aktualisierte Daten werden angezeigt

### 2. Programmatische Verwendung
```php
use App\Services\LexofficeService;

$service = new LexofficeService();
$result = $service->fetchAndStoreLexwareData($customer);

if ($result['success']) {
    echo "Version: " . $result['version'];
    echo "Daten gespeichert: " . ($customer->lexware_json ? 'Ja' : 'Nein');
}
```

### 3. Automatische Integration in Updates
Die bestehende `syncCustomer()` Methode wurde erweitert um:
- Automatisches Abrufen der aktuellen Version vor Updates
- Verwendung der gespeicherten Version für PUT-Requests
- Bessere Fehlerbehandlung bei Versionskonflikten

## Vorteile der Implementierung

### 1. Lösung des HTTP 400 Problems
- Aktuelle Versionsnummer wird immer vor Updates abgerufen
- PUT-Requests verwenden die korrekte Version
- Reduziert Versionskonflikte erheblich

### 2. Verbesserte Debugging-Möglichkeiten
- Komplette Lexoffice-Daten lokal gespeichert
- Detaillierte Logs mit Performance-Metriken
- Versions-Historie für Änderungsverfolgung

### 3. Bessere User Experience
- Klare UI-Integration für manuelle Operationen
- Informative Fehlermeldungen
- Status-Anzeigen für gespeicherte Daten

### 4. Performance-Monitoring
- Ausführungszeiten werden gemessen
- Datengrößen werden protokolliert
- Trend-Analyse möglich

## Test-Datei

**Datei:** `test_lexware_data_fetch.php`

Umfassender Test der Implementierung:
- Abrufen und Speichern der Lexware-Daten
- Überprüfung der gespeicherten Daten
- JSON-Daten-Analyse
- Logging-Verification
- Versions-Vergleich

## Sicherheitsaspekte

### 1. Datenschutz
- JSON-Daten werden verschlüsselt in der Datenbank gespeichert
- Zugriff nur für autorisierte Benutzer
- Logs enthalten keine sensiblen Daten

### 2. API-Limits
- Rate-Limiting wird respektiert
- Fehlerbehandlung für API-Limits implementiert
- Retry-Mechanismus für temporäre Fehler

### 3. Datenintegrität
- Transaktionale Updates
- Rollback bei Fehlern
- Konsistenz-Checks

## Monitoring und Wartung

### 1. Log-Analyse
```sql
-- Erfolgreiche Fetch-Operationen der letzten 24h
SELECT COUNT(*) FROM lexoffice_logs 
WHERE action = 'fetch_lexware_data' 
AND status = 'success' 
AND created_at >= NOW() - INTERVAL 24 HOUR;

-- Durchschnittliche Ausführungszeit
SELECT AVG(JSON_EXTRACT(response_data, '$.duration_ms')) as avg_duration_ms
FROM lexoffice_logs 
WHERE action = 'fetch_lexware_data' 
AND status = 'success';
```

### 2. Datenbank-Wartung
- Regelmäßige Bereinigung alter JSON-Daten
- Index-Optimierung für Performance
- Backup-Strategien für kritische Daten

## Zukünftige Erweiterungen

### 1. Automatische Synchronisation
- Scheduled Jobs für regelmäßige Datenabfrage
- Webhook-Integration für Echtzeit-Updates
- Konfliktauflösung-Strategien

### 2. Erweiterte Analyse
- Dashboard für Versions-Trends
- Anomalie-Erkennung
- Performance-Optimierungen

### 3. Multi-Tenant Support
- Mandanten-spezifische Konfiguration
- Isolierte Datenverarbeitung
- Skalierbare Architektur

## Fazit

Die implementierte Lösung bietet eine robuste und skalierbare Basis für die Lexware-Versionskontrolle. Sie löst das ursprüngliche HTTP 400 Problem und bietet gleichzeitig erweiterte Funktionalitäten für Debugging, Monitoring und Wartung.

Die Implementierung folgt Laravel-Best-Practices und ist vollständig in das bestehende Filament-UI integriert.
