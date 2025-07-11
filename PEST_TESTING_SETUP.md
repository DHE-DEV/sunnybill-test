# Pest Testing Setup für Laravel 12 + Filament 3

## Übersicht

Diese Anleitung dokumentiert die vollständige Einrichtung von automatisierten Softwaretests mit **Pest** als Ersatz für PHPUnit in einer Laravel 12 + Filament 3 Applikation. Das Setup verwendet eine separate MySQL-Testdatenbank für isolierte Tests.

## ✅ Implementierte Komponenten

### 1. Pest Installation und Konfiguration

**Installierte Pakete:**
```bash
composer require pestphp/pest --dev
composer require pestphp/pest-plugin-laravel --dev
composer require pestphp/pest-plugin-livewire --dev
```

**Konfigurationsdateien:**
- [`tests/Pest.php`](tests/Pest.php) - Pest-Hauptkonfiguration mit Helper-Funktionen
- [`phpunit.xml`](phpunit.xml) - Angepasst für MySQL-Testdatenbank
- [`composer.json`](composer.json) - Test-Script auf Pest umgestellt

### 2. Testdatenbank-Konfiguration

**MySQL-Testdatenbank:**
- Datenbankname: `voltmaster-pest-test`
- Verbindung: `mysql_test` in [`config/database.php`](config/database.php)
- Umgebung: [`.env.testing`](.env.testing) mit separaten DB-Parametern

**Wichtige Einstellungen:**
```env
DB_CONNECTION=mysql_test
DB_DATABASE_TEST=voltmaster-pest-test
APP_KEY=base64:qrNT4IfHBcLygB6rtMRywNfsMi9SUGeMUYlXEJUWe1E=
```

### 3. Filament-spezifische Tests

**Implementierte Test-Suites:**
- [`tests/Feature/Filament/CustomerResourceTest.php`](tests/Feature/Filament/CustomerResourceTest.php)
- [`tests/Feature/Filament/SupplierResourceTest.php`](tests/Feature/Filament/SupplierResourceTest.php)
- [`tests/Feature/Filament/TasksAndProjectsDashboardTest.php`](tests/Feature/Filament/TasksAndProjectsDashboardTest.php)
- [`tests/Feature/Filament/AuthenticationTest.php`](tests/Feature/Filament/AuthenticationTest.php)

**Test-Kategorien:**
- CRUD-Operationen (Create, Read, Update, Delete)
- Formular-Validierung
- Filament-Authentifizierung
- Benutzer-Berechtigungen
- Livewire-Komponenten-Tests
- Business Logic Tests
- Soft Delete Operations
- Table Filtering & Sorting

## 🚀 Verwendung

### Tests ausführen

```bash
# Alle Tests
./vendor/bin/pest

# Nur Filament-Tests
./vendor/bin/pest tests/Feature/Filament/

# Mit Composer-Script
composer test

# Spezifische Test-Datei
./vendor/bin/pest tests/Feature/Filament/CustomerResourceTest.php
```

### Testdatenbank vorbereiten

```bash
# Migrationen in Testumgebung ausführen
php artisan migrate --env=testing

# Testdatenbank zurücksetzen
php artisan migrate:fresh --env=testing
```

## 📁 Dateistruktur

```
├── .env.testing                           # Test-Umgebungskonfiguration
├── phpunit.xml                           # PHPUnit/Pest-Konfiguration
├── tests/
│   ├── Pest.php                          # Pest-Hauptkonfiguration
│   └── Feature/
│       └── Filament/
│           ├── CustomerResourceTest.php   # Kunden-Resource Tests
│           ├── SupplierResourceTest.php   # Lieferanten-Resource Tests
│           └── AuthenticationTest.php     # Authentifizierungs-Tests
├── database/
│   └── factories/
│       ├── SupplierFactory.php           # Lieferanten-Factory
│       └── SupplierTypeFactory.php       # Lieferantentyp-Factory
└── config/
    └── database.php                      # Erweiterte DB-Konfiguration
```

## 🔧 Helper-Funktionen

Die [`tests/Pest.php`](tests/Pest.php) Datei stellt nützliche Helper-Funktionen bereit:

```php
// Benutzer erstellen
$user = createUser(['email' => 'test@example.com']);

// Admin-Benutzer erstellen
$admin = createAdminUser();

// Kunde erstellen
$customer = createCustomer(['name' => 'Test Kunde']);

// Lieferant erstellen
$supplier = createSupplier(['company_name' => 'Test Lieferant GmbH']);

// Lieferantentyp erstellen
$supplierType = createSupplierType(['name' => 'Direktvermarkter']);

// Lieferant mit Typ erstellen
$supplier = createSupplierWithType(['company_name' => 'Test GmbH']);

// Als Filament-Benutzer authentifizieren
actingAsFilamentUser($user);
```

## 📊 Test-Ergebnisse

**Aktueller Status:** ✅ **Alle Tests bestanden**

### Customer Resource Tests
- ✅ **21 von 21 Tests bestanden** (CustomerResourceTest.php)
- Vollständige CRUD-Operationen getestet
- Formular-Validierung funktioniert
- Livewire-Integration erfolgreich

### Supplier Resource Tests
- ✅ **29 von 29 Tests bestanden** (SupplierResourceTest.php)
- Index Page Tests (7 Tests)
- Create Page Tests (5 Tests)
- Edit Page Tests (4 Tests)
- View Page Tests (2 Tests)
- Delete Operations Tests (3 Tests)
- Business Logic Tests (6 Tests)
- Table Columns Tests (2 Tests)

### Gesamt-Status
- ✅ **50 von 50 Tests bestanden**
- ✅ Pest-Framework läuft erfolgreich
- ✅ MySQL-Testdatenbank funktioniert
- ✅ Filament-Tests werden ausgeführt
- ✅ RefreshDatabase funktioniert korrekt
- ✅ Helper-Funktionen arbeiten ordnungsgemäß
- ✅ Factory-Pattern implementiert
- ✅ Soft Delete Operations getestet

## 🛠️ Troubleshooting

### Häufige Probleme und Lösungen

**1. Datenbankverbindung fehlgeschlagen:**
```bash
# MySQL-Service prüfen
mysql -u root -p

# Testdatenbank manuell erstellen
CREATE DATABASE `voltmaster-pest-test`;
```

**2. APP_KEY Fehler:**
```bash
# Neuen Schlüssel generieren
php artisan key:generate --show
# In .env.testing einfügen
```

**3. Migration-Fehler:**
```bash
# Migrationen zurücksetzen
php artisan migrate:fresh --env=testing
```

**4. Filament-Berechtigungen:**
- Prüfen Sie die `role`-Spalte in der User-Tabelle
- Admin-Benutzer benötigen `role = 'admin'`

## 📝 Best Practices

### Test-Organisation
- Verwenden Sie `describe()` für Test-Gruppierung
- Nutzen Sie `RefreshDatabase` für Datenbank-Isolation
- Erstellen Sie Helper-Funktionen für wiederkehrende Operationen

### Filament-Tests
```php
// Filament-Resource testen
test('kann Kunden erstellen', function () {
    $admin = createAdminUser();
    
    livewire(CreateCustomer::class)
        ->actingAs($admin)
        ->fillForm([
            'name' => 'Test Kunde',
            'email' => 'test@example.com'
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});
```

### Authentifizierung testen
```php
// Filament-Login testen
test('kann sich anmelden', function () {
    $user = createUser(['password' => bcrypt('password')]);
    
    $this->post('/admin/login', [
        'email' => $user->email,
        'password' => 'password'
    ])->assertRedirect('/admin');
});
```

### Supplier-Tests
```php
// Lieferanten-Resource testen
test('kann Lieferanten erstellen', function () {
    $supplierType = createSupplierType(['name' => 'Direktvermarkter']);
    
    livewire(CreateSupplier::class)
        ->fillForm([
            'company_name' => 'Test Lieferant GmbH',
            'supplier_type_id' => $supplierType->id,
            'email' => 'test@lieferant.de',
            'is_active' => true
        ])
        ->call('create')
        ->assertHasNoFormErrors();
});

// Business Logic testen
test('zeigt korrekten Anzeigenamen', function () {
    $supplier = createSupplierWithType([
        'name' => 'Max Mustermann',
        'company_name' => 'Mustermann GmbH'
    ]);
    
    expect($supplier->display_name)->toBe('Mustermann GmbH');
});

// Soft Delete testen
test('kann Lieferanten löschen und wiederherstellen', function () {
    $supplier = createSupplierWithType();
    
    $supplier->delete();
    expect($supplier->deleted_at)->not->toBeNull();
    
    $supplier->restore();
    expect($supplier->deleted_at)->toBeNull();
});
```

## 🔄 Wartung

### Regelmäßige Aufgaben
- Tests bei jeder Code-Änderung ausführen
- Testdatenbank regelmäßig zurücksetzen
- Test-Coverage überwachen
- Neue Features mit Tests abdecken

### Updates
- Pest-Pakete regelmäßig aktualisieren
- Laravel-Test-Features nutzen
- Filament-Test-Utilities verwenden

## 📚 Weiterführende Ressourcen

- [Pest Documentation](https://pestphp.com/)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Filament Testing](https://filamentphp.com/docs/panels/testing)
- [Livewire Testing](https://livewire.laravel.com/docs/testing)

---

**Setup abgeschlossen am:** 11.07.2025  
**Laravel Version:** 12.x  
**Filament Version:** 3.x  
**Pest Version:** 3.x