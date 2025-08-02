# SunnyBill API Test Suite

Diese umfassende Test-Suite deckt alle API-Endpunkte und Kernfunktionalitäten der SunnyBill-Anwendung ab.

## Überblick

Die Test-Suite ist in verschiedene Kategorien unterteilt:

### Test-Struktur

```
tests/
├── Feature/                 # Integration Tests
│   ├── Api/                # API Endpoint Tests
│   │   ├── TaskApiTest.php
│   │   ├── CustomerApiTest.php
│   │   ├── SolarPlantApiTest.php
│   │   ├── ProjectApiTest.php
│   │   └── AuthenticationTest.php
│   ├── ValidationTest.php   # Validierungstests
│   └── ErrorHandlingTest.php # Fehlerbehandlungstests
├── Unit/                   # Unit Tests
│   ├── Models/            # Model Tests
│   │   ├── CustomerTest.php
│   │   └── TaskTest.php
│   └── Services/          # Service Tests
├── Traits/                # Test Traits
│   ├── InteractsWithApi.php
│   └── CreatesTestData.php
└── TestCase.php           # Base Test Class
```

## Funktionen der Test-Suite

### 1. API-Endpunkt-Tests (`tests/Feature/Api/`)

- **TaskApiTest**: Alle CRUD-Operationen für Tasks
- **CustomerApiTest**: Kunden-Management API-Tests
- **SolarPlantApiTest**: Solaranlagen-API-Tests
- **ProjectApiTest**: Projekt-Management-Tests
- **AuthenticationTest**: Authentifizierung und Autorisierung

### 2. Model-Tests (`tests/Unit/Models/`)

- Relationship-Tests
- Scope-Tests
- Attribute-Tests
- Validation-Tests

### 3. Validierungs-Tests (`tests/Feature/ValidationTest.php`)

- Eingabevalidierung für alle API-Endpunkte
- Feldlängen-Validierung
- Datentyp-Validierung
- Business-Rule-Validierung

### 4. Fehlerbehandlungs-Tests (`tests/Feature/ErrorHandlingTest.php`)

- 404-Fehler für nicht existierende Ressourcen
- 401/403-Authentifizierungsfehler
- 422-Validierungsfehler
- 500-Server-Fehler

## Test-Hilfsmittel

### Traits

#### `InteractsWithApi`
```php
// API-Requests mit automatischer Authentifizierung
$response = $this->apiGet('/tasks', ['tasks:read']);
$response = $this->apiPost('/customers', $data, ['customers:create']);

// Testen von Authentifizierungsfehlern
$this->assertUnauthorized('GET', '/tasks');
$this->assertForbidden('POST', '/tasks', ['tasks:create']);
```

#### `CreatesTestData`
```php
// Einfache Testerstellung
$customer = $this->createCustomer();
$task = $this->createTask();
$project = $this->createProject();

// Multiple Entitäten erstellen
$customers = $this->createMultiple('customer', 5);

// Komplettes Projekt-Setup
$setup = $this->createCompleteProject();
```

### Factory-States

Jede Factory bietet verschiedene Zustände:

```php
// Task Factory
Task::factory()->urgent()->overdue()->create();
Task::factory()->withCustomer()->withAssignedUser()->create();

// Customer Factory
Customer::factory()->business()->create();
Customer::factory()->private()->deactivated()->create();

// Project Factory
Project::factory()->inProgress()->highPriority()->create();
Project::factory()->completed()->withContract()->create();
```

## Ausführung der Tests

### Alle Tests ausführen
```bash
php artisan test
```

### Spezifische Test-Kategorien
```bash
# Nur API-Tests
php artisan test tests/Feature/Api/

# Nur Model-Tests
php artisan test tests/Unit/Models/

# Nur Validierungstests
php artisan test tests/Feature/ValidationTest.php
```

### Einzelne Test-Klassen
```bash
php artisan test tests/Feature/Api/TaskApiTest.php
```

### Spezifische Test-Methoden
```bash
php artisan test --filter test_can_create_task
```

## Test-Konfiguration

### Database
- Tests verwenden separate Test-Datenbank (`mysql_test`)
- Automatisches Zurücksetzen nach jedem Test (`RefreshDatabase`)
- Automatic Seeding für Basisdaten

### Authentifizierung
- App-Token-basierte Authentifizierung
- Berechtigungssystem wird vollständig getestet
- Verschiedene Berechtigungsebenen

## Abgedeckte API-Endpunkte

### Tasks API (`/api/app/tasks`)
- ✅ `GET /` - Task-Liste
- ✅ `POST /` - Task erstellen
- ✅ `GET /{id}` - Task anzeigen
- ✅ `PUT /{id}` - Task aktualisieren
- ✅ `DELETE /{id}` - Task löschen
- ✅ `PATCH /{id}/status` - Status ändern
- ✅ `PATCH /{id}/assign` - Zuweisen
- ✅ `GET /{id}/subtasks` - Unteraufgaben

### Customers API (`/api/app/customers`)
- ✅ `GET /` - Kunden-Liste
- ✅ `POST /` - Kunde erstellen
- ✅ `GET /{id}` - Kunde anzeigen
- ✅ `PUT /{id}` - Kunde aktualisieren
- ✅ `DELETE /{id}` - Kunde löschen
- ✅ `PATCH /{id}/status` - Status ändern
- ✅ `GET /{id}/participations` - Beteiligungen
- ✅ `GET /{id}/projects` - Projekte
- ✅ `GET /{id}/tasks` - Aufgaben

### Solar Plants API (`/api/app/solar-plants`)
- ✅ `GET /` - Anlagen-Liste
- ✅ `POST /` - Anlage erstellen
- ✅ `GET /{id}` - Anlage anzeigen
- ✅ `PUT /{id}` - Anlage aktualisieren
- ✅ `DELETE /{id}` - Anlage löschen
- ✅ `GET /{id}/components` - Komponenten
- ✅ `GET /{id}/participations` - Beteiligungen
- ✅ `GET /{id}/statistics` - Statistiken

### Projects API (`/api/app/projects`)
- ✅ `GET /` - Projekt-Liste
- ✅ `POST /` - Projekt erstellen
- ✅ `GET /{id}` - Projekt anzeigen
- ✅ `PUT /{id}` - Projekt aktualisieren
- ✅ `DELETE /{id}` - Projekt löschen
- ✅ `PATCH /{id}/status` - Status ändern
- ✅ `GET /{id}/progress` - Fortschritt
- ✅ `PATCH /{id}/progress` - Fortschritt aktualisieren
- ✅ `GET /{id}/milestones` - Meilensteine
- ✅ `POST /{id}/milestones` - Meilenstein erstellen
- ✅ `GET /{id}/appointments` - Termine
- ✅ `POST /{id}/appointments` - Termin erstellen

## Best Practices

### 1. Test-Daten
- Verwende Factories anstatt direkte Model-Erstellung
- Nutze realistische aber anonymisierte Testdaten
- Teste Edge Cases und Grenzwerte

### 2. Assertions
- Verwende spezifische Assertions (`assertJsonStructure`, `assertJsonValidationErrors`)
- Teste sowohl erfolgreiche als auch fehlerhafte Fälle
- Überprüfe Datenbankzustände mit `assertDatabaseHas`

### 3. Test-Isolation
- Jeder Test sollte unabhängig ausführbar sein
- Verwende `RefreshDatabase` für saubere Testumgebung
- Keine geteilten Zustände zwischen Tests

### 4. Performance
- Minimiere Datenbankzugriffe
- Verwende `create()` nur wenn nötig, sonst `make()`
- Parallelisiere Tests wo möglich

## Erweiterte Features

### Test-Coverage
```bash
# Mit Coverage-Report
php artisan test --coverage --min=80
```

### Debugging
```bash
# Detaillierte Ausgabe
php artisan test --verbose

# Stoppen bei erstem Fehler
php artisan test --stop-on-failure
```

### CI/CD Integration
Die Tests sind für die Integration in CI/CD-Pipelines optimiert:
- Konsistente Testdatenbank
- Parallele Ausführung möglich
- Detaillierte Fehlerberichte

## Wartung

### Neue Tests hinzufügen
1. Entsprechende Factory-States erweitern
2. Test-Methoden in passende Klasse einordnen
3. Dokumentation aktualisieren

### Tests bei API-Änderungen
1. Entsprechende Test-Klasse aktualisieren
2. Validierungstests anpassen
3. Factory-Definitionen erweitern

Die Test-Suite bietet eine solide Grundlage für die Qualitätssicherung und Continuous Integration der SunnyBill-API.