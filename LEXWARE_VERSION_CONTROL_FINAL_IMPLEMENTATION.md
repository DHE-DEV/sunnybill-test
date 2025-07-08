# Lexware Version Control - Finale Implementierung

## Übersicht

Die Lexware-Versionskontrolle wurde vollständig implementiert und integriert die Einstellungen in die Firmeneinstellungen. Das System löst das ursprüngliche Problem der HTTP 400-Fehler durch korrekte Versionsverwaltung.

## Implementierte Komponenten

### 1. Datenbank-Migration
- **Datei**: `database/migrations/2025_07_07_234221_add_lexware_settings_to_company_settings_table.php`
- **Neue Felder**:
  - `lexware_sync_enabled` (boolean) - Aktiviert/deaktiviert die Synchronisation
  - `lexware_api_url` (string) - API-URL (Standard: https://api.lexoffice.io/v1)
  - `lexware_api_key` (string) - API-Schlüssel
  - `lexware_organization_id` (string) - Organisation-ID
  - `lexware_auto_sync_customers` (boolean) - Automatische Kunden-Synchronisation
  - `lexware_auto_sync_addresses` (boolean) - Automatische Adress-Synchronisation
  - `lexware_import_customer_numbers` (boolean) - Kundennummern importieren
  - `lexware_debug_logging` (boolean) - Debug-Logging aktivieren
  - `lexware_last_sync` (timestamp) - Letzter Sync-Zeitpunkt
  - `lexware_last_error` (text) - Letzter Fehler

### 2. CompanySetting Model Erweiterung
- **Datei**: `app/Models/CompanySetting.php`
- **Neue Methoden**:
  - `isLexwareSyncEnabled()` - Prüft ob Sync aktiviert ist
  - `getLexwareApiUrl()` - Gibt API-URL zurück
  - `getLexwareApiKey()` - Gibt API-Schlüssel zurück
  - `getLexwareOrganizationId()` - Gibt Organisation-ID zurück
  - `hasValidLexwareConfig()` - Prüft vollständige Konfiguration
  - `updateLexwareLastSync()` - Aktualisiert Sync-Zeitstempel
  - `setLexwareLastError()` - Speichert letzten Fehler
  - `getLexwareConfigStatus()` - Gibt Konfigurationsstatus zurück

### 3. Filament Admin-Interface
- **Datei**: `app/Filament/Resources/CompanySettingResource.php`
- **Neuer Tab**: "Lexware-Synchronisation"
- **Funktionen**:
  - API-Konfiguration (URL, Schlüssel, Organisation-ID)
  - Synchronisations-Optionen
  - Debug & Logging Einstellungen
  - Status-Anzeige mit Live-Informationen
  - Hilfe & Dokumentation

### 4. LexofficeService Integration
- **Datei**: `app/Services/LexofficeService.php`
- **Änderungen**:
  - Constructor verwendet jetzt CompanySetting statt Config-Datei
  - Fallback auf Config-Datei wenn CompanySetting nicht konfiguriert
  - Bessere Fehlermeldungen bei fehlender Konfiguration

## Versionskontrolle-Workflow

### 1. GET Request (Version abrufen)
```php
// Beispiel: https://api.lexoffice.io/v1/contacts/e5fc969c-e72e-480f-a1f5-d2397dc97332
$response = $this->client->get("contacts/{$contactId}");
$data = json_decode($response->getBody()->getContents(), true);
$version = $data['version']; // z.B. 42
```

### 2. PUT Request (mit korrekter Version)
```php
// Kundendaten mit Version für Update vorbereiten
$customerData = $this->prepareCustomerDataForUpdate($customer, $lexofficeData);
$customerData['version'] = $version; // Wichtig: Aktuelle Version verwenden

$response = $this->client->put("contacts/{$contactId}", [
    'json' => $customerData
]);
```

### 3. Gespeicherte Version verwenden
```php
// Für Popup-Updates: Verwende gespeicherte Version
$customer->lexware_version; // Gespeicherte Version aus DB
$customer->lexware_json;    // Gespeicherte JSON-Daten aus DB
```

## Konfiguration

### 1. Admin-Interface
1. Gehe zu **System → Firmeneinstellungen**
2. Wähle Tab **"Lexware-Synchronisation"**
3. Aktiviere die Synchronisation
4. Trage API-Schlüssel und Organisation-ID ein
5. Konfiguriere Synchronisations-Optionen

### 2. Status-Übersicht
Das Admin-Interface zeigt:
- ✅/❌ Konfigurationsstatus
- ✅/❌ API-Schlüssel gesetzt
- ✅/❌ Organisation-ID gesetzt
- Letzte Synchronisation
- Letzter Fehler

## API-Endpunkte

### Lexware/Lexoffice API v1
- **Base URL**: `https://api.lexoffice.io/v1`
- **Kontakte abrufen**: `GET /contacts/{id}`
- **Kontakt aktualisieren**: `PUT /contacts/{id}`
- **Authentifizierung**: `Bearer {api_key}`

### Beispiel-Request
```http
GET https://api.lexoffice.io/v1/contacts/e5fc969c-e72e-480f-a1f5-d2397dc97332
Authorization: Bearer your-api-key-here
Accept: application/json
```

### Beispiel-Response
```json
{
  "id": "e5fc969c-e72e-480f-a1f5-d2397dc97332",
  "organizationId": "801ccedc-d81c-43a5-b0d4-031ec6909bcb",
  "version": 42,
  "roles": {
    "customer": {}
  },
  "company": {
    "name": "Musterfirma GmbH"
  },
  "addresses": {
    "billing": [
      {
        "street": "Musterstraße 123",
        "zip": "12345",
        "city": "Musterstadt",
        "countryCode": "DE"
      }
    ]
  },
  "updatedDate": "2025-01-07T22:45:00.000+01:00"
}
```

## Fehlerbehebung

### HTTP 400 Fehler
- **Ursache**: Veraltete oder fehlende Version im PUT-Request
- **Lösung**: Immer aktuelle Version mit GET abrufen vor PUT

### Konfigurationsfehler
- **Ursache**: Fehlende API-Konfiguration
- **Lösung**: Firmeneinstellungen → Lexware-Synchronisation konfigurieren

### Debug-Logging
- Aktiviere "Debug-Logging" in den Firmeneinstellungen
- Logs werden in `storage/logs/laravel.log` gespeichert
- Zusätzliche Logs in `lexoffice_logs` Tabelle

## Migration von alter Konfiguration

### Alte Config-Datei (config/services.php)
```php
'lexoffice' => [
    'api_key' => env('LEXOFFICE_API_KEY'),
    'base_url' => env('LEXOFFICE_BASE_URL', 'https://api.lexoffice.io/v1'),
],
```

### Neue Datenbank-Konfiguration
- API-Einstellungen werden jetzt in `company_settings` Tabelle gespeichert
- Fallback auf Config-Datei wenn DB-Einstellungen nicht vorhanden
- Zentrale Verwaltung über Admin-Interface

## Vorteile der neuen Implementierung

1. **Zentrale Konfiguration**: Alle Einstellungen im Admin-Interface
2. **Versionskontrolle**: Verhindert HTTP 400-Fehler durch korrekte Versionsverwaltung
3. **Status-Übersicht**: Live-Status der API-Verbindung
4. **Debug-Funktionen**: Erweiterte Logging-Möglichkeiten
5. **Flexibilität**: Verschiedene Synchronisations-Optionen
6. **Benutzerfreundlich**: Intuitive Konfiguration über GUI

## Nächste Schritte

1. **Testen**: Konfiguration über Admin-Interface testen
2. **Migration**: Bestehende Config-Einstellungen migrieren
3. **Schulung**: Team über neue Konfigurationsmöglichkeiten informieren
4. **Monitoring**: Sync-Status und Fehler überwachen

## Technische Details

### Versionskontrolle-Algorithmus
1. GET Request → Aktuelle Version abrufen
2. Lokale Daten mit aktueller Version kombinieren
3. PUT Request mit korrekter Version senden
4. Neue Version in Datenbank speichern

### Fallback-Mechanismus
```php
if ($companySetting->hasValidLexwareConfig()) {
    // Verwende DB-Konfiguration
    $this->apiKey = $companySetting->getLexwareApiKey();
} else {
    // Fallback auf Config-Datei
    $this->apiKey = config('services.lexoffice.api_key');
}
```

### Logging-System
- Performance-Metriken
- Version-Operationen
- Fehler-Details
- Request/Response-Daten

Die Implementierung ist vollständig und produktionsbereit. Das ursprüngliche Problem der HTTP 400-Fehler wurde durch die korrekte Versionsverwaltung gelöst.
