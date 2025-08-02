# Testing Guide für SunnyBill

Diese Anleitung zeigt, wie Sie die erstellte Test-Suite verwenden und ausführen können.

## Voraussetzungen

1. **PHP** muss installiert und im PATH verfügbar sein
2. **Composer** Dependencies müssen installiert sein
3. **Test-Datenbank** muss konfiguriert sein
4. **Environment** muss für Tests konfiguriert sein

## Schnellstart

### 1. Test-Environment prüfen

```bash
# Prüfen ob PHP verfügbar ist
php --version

# Prüfen ob Composer Dependencies installiert sind
composer install --dev

# Environment prüfen
php artisan env
```

### 2. Test-Datenbank konfigurieren

Stellen Sie sicher, dass in Ihrer `.env` oder `phpunit.xml` die Test-Datenbank konfiguriert ist:

```xml
<env name="DB_CONNECTION" value="mysql_test"/>
<env name="DB_DATABASE" value="voltmaster-pest-test"/>
```

### 3. Erste Tests ausführen

```bash
# Alle Tests ausführen
php artisan test

# Oder mit Pest direkt
./vendor/bin/pest

# Nur bestimmte Test-Dateien
php artisan test tests/Feature/BasicConnectionTest.php
php artisan test tests/Feature/FactoryTest.php
```

## Test-Kategorien

### A. Basis-Tests (Empfohlen als Startpunkt)

```bash
# Test der Grundfunktionalität
php artisan test tests/Feature/BasicConnectionTest.php

# Test der Factories
php artisan test tests/Feature/FactoryTest.php

# Test der Pest Helper-Funktionen
php artisan test tests/Feature/PestHelpersTest.php
```

### B. API-Tests

```bash
# Einfache API-Tests
php artisan test tests/Feature/Api/SimpleTaskTest.php

# Vollständige API-Test-Suite (wenn API-Controller existieren)
php artisan test tests/Feature/Api/
```

### C. Model-Tests

```bash
# Unit Tests für Models
php artisan test tests/Unit/Models/
```

### D. Validierungs- und Fehlerbehandlung

```bash
# Validation Tests
php artisan test tests/Feature/ValidationTest.php

# Error Handling Tests
php artisan test tests/Feature/ErrorHandlingTest.php
```

## Debugging von Tests

### Test-Ausgabe erhöhen

```bash
# Verbose Output
php artisan test --verbose

# Bei erstem Fehler stoppen
php artisan test --stop-on-failure

# Spezifische Test-Methode ausführen
php artisan test --filter "test_task_factory_works_correctly"
```

### Häufige Probleme und Lösungen

#### Problem: "Class 'Database\Factories\ProjectFactory' not found"

**Lösung:** Factory fehlt oder ist nicht korrekt geladen.

```bash
# Autoload neu generieren
composer dump-autoload

# Factories überprüfen
ls database/factories/
```

#### Problem: "SQLSTATE[42S02]: Base table or directory 'tasks' doesn't exist"

**Lösung:** Datenbank-Migration ausführen.

```bash
# Migrationen ausführen
php artisan migrate --env=testing

# Oder mit refresh
php artisan migrate:refresh --env=testing
```

#### Problem: "Target class [App\Models\Project] does not exist"

**Lösung:** Model existiert nicht oder Namespace ist falsch.

```bash
# Verfügbare Models überprüfen
ls app/Models/

# Model erstellen falls nötig
php artisan make:model Project
```

## Test-Struktur verstehen

### 1. Pest-basierte Tests

Die Tests verwenden Pest-Syntax:

```php
test('description', function () {
    // Test-Code hier
    expect($value)->toBe($expected);
});
```

### 2. Factory-basierte Daten

Tests verwenden Factories für Testdaten:

```php
$task = Task::factory()->create();
$customer = Customer::factory()->business()->create();
```

### 3. Helper-Funktionen

Pest Helper-Funktionen sind verfügbar:

```php
$user = createUser(['name' => 'Test User']);
$admin = actingAsAdmin();
```

## Anpassung der Tests

### 1. Neue Tests hinzufügen

```php
// In tests/Feature/MeinTest.php
test('my new test', function () {
    $model = MyModel::factory()->create();
    expect($model)->toBeInstanceOf(MyModel::class);
});
```

### 2. Factories anpassen

```php
// In database/factories/MyModelFactory.php
public function definition(): array
{
    return [
        'name' => $this->faker->name(),
        'email' => $this->faker->email(),
    ];
}
```

### 3. Neue Helper hinzufügen

```php
// In tests/Pest.php
function createMyModel(array $attributes = []): MyModel
{
    return MyModel::factory()->create($attributes);
}
```

## CI/CD Integration

### GitHub Actions Beispiel

```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php artisan test
```

## Best Practices

### 1. Test-Daten isolieren

```php
// Gut: Jeder Test erstellt eigene Daten
test('my test', function () {
    $user = User::factory()->create();
    // Test mit $user
});

// Schlecht: Geteilte Daten zwischen Tests
```

### 2. Aussagekräftige Test-Namen

```php
// Gut
test('user can create task with valid data', function () {

// Schlecht
test('test user task', function () {
```

### 3. Arrange-Act-Assert Pattern

```php
test('user can update task', function () {
    // Arrange
    $user = createUser();
    $task = createTask(['user_id' => $user->id]);
    
    // Act
    $task->update(['title' => 'New Title']);
    
    // Assert
    expect($task->fresh()->title)->toBe('New Title');
});
```

## Erweiterte Funktionen

### Coverage-Reports

```bash
# Mit Coverage (benötigt Xdebug)
php artisan test --coverage

# Coverage mit Minimum-Threshold
php artisan test --coverage --min=80
```

### Parallel Testing

```bash
# Parallel ausführen (Laravel 8+)
php artisan test --parallel
```

### Test-Datenbank zurücksetzen

```bash
# Komplett zurücksetzen
php artisan migrate:fresh --env=testing

# Mit Seeders
php artisan migrate:fresh --seed --env=testing
```

Diese Test-Suite bietet eine solide Grundlage für die Qualitätssicherung Ihrer SunnyBill-Anwendung. Beginnen Sie mit den Basis-Tests und erweitern Sie schrittweise je nach Ihren Anforderungen.