# Laravel Herd Test-Ausführung Guide

## Verfügbare API Test-Dateien

Die folgenden API-Tests wurden erstellt und sind bereit zur Ausführung:

```
tests/Feature/Api/
├── AuthenticationTest.php      - Authentifizierung & Autorisierung
├── CustomerApiTest.php         - Kunden-API Tests
├── ProjectApiTest.php          - Projekt-API Tests  
├── SimpleTaskTest.php          - Einfache Task-Tests (Empfohlen zum Start)
├── SolarPlantApiTest.php       - Solaranlagen-API Tests
├── TaskApiPestTest.php         - Task-API Tests (Pest-Style)
└── TaskApiTest.php             - Task-API Tests (PHPUnit-Style)
```

## Mit Laravel Herd ausführen

### 1. Alle API-Tests ausführen

```bash
# In PowerShell oder CMD im Projektverzeichnis
php artisan test tests/Feature/Api/
```

### 2. Einzelne Test-Dateien ausführen

```bash
# Einfache Task-Tests (Empfohlen zum Start)
php artisan test tests/Feature/Api/SimpleTaskTest.php

# Authentifizierung testen
php artisan test tests/Feature/Api/AuthenticationTest.php

# Kunden-API testen
php artisan test tests/Feature/Api/CustomerApiTest.php

# Projekt-API testen
php artisan test tests/Feature/Api/ProjectApiTest.php

# Solaranlagen-API testen
php artisan test tests/Feature/Api/SolarPlantApiTest.php
```

### 3. Spezifische Tests ausführen

```bash
# Nur Factory-Tests
php artisan test --filter "factory"

# Nur API-Connection-Tests
php artisan test --filter "can_create"

# Mit mehr Details
php artisan test tests/Feature/Api/SimpleTaskTest.php --verbose
```

### 4. Alle Tests (inkl. andere Kategorien)

```bash
# Basis-Tests zuerst
php artisan test tests/Feature/BasicConnectionTest.php
php artisan test tests/Feature/FactoryTest.php

# Dann alle Tests
php artisan test
```

## Erwartete Ergebnisse

### ✅ Diese Tests sollten erfolgreich sein:
- `FactoryTest.php` - Factory-Erstellung
- `BasicConnectionTest.php` - Basis-Verbindungen
- `SimpleTaskTest.php` - Grundlegende Model-Tests

### ⚠️ Diese Tests können fehlschlagen (normal):
- API-Tests mit echten HTTP-Requests (wenn Controller nicht implementiert)
- Authentifizierung-Tests (wenn Middleware fehlt)
- Validierungs-Tests (wenn Validation Rules fehlen)

## Debugging bei Fehlern

### 1. Database Errors
```bash
# Migrationen ausführen
php artisan migrate:fresh --env=testing

# Seeders ausführen falls nötig
php artisan db:seed --env=testing
```

### 2. Class Not Found Errors
```bash
# Autoload aktualisieren
composer dump-autoload
```

### 3. Test-spezifische Optionen
```bash
# Bei erstem Fehler stoppen
php artisan test --stop-on-failure

# Nur fehlgeschlagene Tests erneut ausführen
php artisan test --retry

# Mit Coverage (falls Xdebug installiert)
php artisan test --coverage
```

## Nächste Schritte

1. **Starten Sie mit den Basis-Tests:**
   ```bash
   php artisan test tests/Feature/BasicConnectionTest.php
   ```

2. **Dann Factory-Tests:**
   ```bash
   php artisan test tests/Feature/FactoryTest.php
   ```

3. **Einfache API-Tests:**
   ```bash
   php artisan test tests/Feature/Api/SimpleTaskTest.php
   ```

4. **Bei Erfolg, alle API-Tests:**
   ```bash
   php artisan test tests/Feature/Api/
   ```

## Test-Status Interpretation

- **PASS** ✅ - Test erfolgreich
- **FAIL** ❌ - Test fehlgeschlagen (Details werden angezeigt)
- **SKIP** ⏭️ - Test übersprungen
- **ERROR** 🚨 - Schwerwiegender Fehler (Syntax, Class not found, etc.)

Die Tests sind so aufgebaut, dass sie schrittweise komplexer werden. Beginnen Sie mit den einfacheren Tests und arbeiten Sie sich vor!