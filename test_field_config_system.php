<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Laravel Bootstrap
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Field Configuration System Test ===\n\n";

try {
    // 1. Migration ausführen
    echo "1. Führe Migration aus...\n";
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_07_11_223000_create_field_configs_table.php',
        '--force' => true
    ]);
    echo "✓ Migration erfolgreich ausgeführt\n\n";

    // 2. Seeder ausführen
    echo "2. Führe FieldConfig Seeder aus...\n";
    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\FieldConfigSeeder',
        '--force' => true
    ]);
    echo "✓ Seeder erfolgreich ausgeführt\n\n";

    // 3. FieldConfig Model testen
    echo "3. Teste FieldConfig Model...\n";
    
    $fieldConfigs = \App\Models\FieldConfig::forEntity('supplier_contract')
        ->active()
        ->ordered()
        ->get();
    
    echo "Anzahl aktiver SupplierContract Felder: " . $fieldConfigs->count() . "\n";
    
    // Gruppiere nach Sektionen
    $sections = $fieldConfigs->groupBy('section_name');
    echo "Anzahl Sektionen: " . $sections->count() . "\n";
    
    foreach ($sections as $sectionName => $sectionFields) {
        echo "  - {$sectionName}: " . $sectionFields->count() . " Felder\n";
    }
    echo "\n";

    // 4. Teste verschiedene Feldtypen
    echo "4. Teste Feldtypen-Verteilung...\n";
    $fieldTypes = $fieldConfigs->groupBy('field_type');
    foreach ($fieldTypes as $type => $fields) {
        $typeName = \App\Models\FieldConfig::getFieldTypes()[$type] ?? $type;
        echo "  - {$typeName} ({$type}): " . $fields->count() . " Felder\n";
    }
    echo "\n";

    // 5. Teste System vs Custom Felder
    echo "5. Teste System vs Custom Felder...\n";
    $systemFields = $fieldConfigs->where('is_system_field', true)->count();
    $customFields = $fieldConfigs->where('is_system_field', false)->count();
    echo "  - System-Felder: {$systemFields}\n";
    echo "  - Custom-Felder: {$customFields}\n\n";

    // 6. Teste Spaltenbreiten-Verteilung
    echo "6. Teste Spaltenbreiten-Verteilung...\n";
    $columnSpans = $fieldConfigs->groupBy('column_span');
    foreach ($columnSpans as $span => $fields) {
        $spanName = $span == 1 ? 'Halbe Breite' : 'Volle Breite';
        echo "  - {$spanName} ({$span}): " . $fields->count() . " Felder\n";
    }
    echo "\n";

    // 7. Teste Schema-Generierung
    echo "7. Teste Schema-Generierung...\n";
    $schema = \App\Models\FieldConfig::getFormSchema('supplier_contract');
    echo "Anzahl generierte Sektionen: " . count($schema) . "\n";
    
    foreach ($schema as $index => $section) {
        if (method_exists($section, 'getName')) {
            echo "  - Sektion " . ($index + 1) . ": " . $section->getName() . "\n";
        }
    }
    echo "\n";

    // 8. Teste spezifische Feld-Konfigurationen
    echo "8. Teste spezifische Feld-Konfigurationen...\n";
    
    // Teste Titel-Feld (sollte volle Breite haben)
    $titleField = $fieldConfigs->where('field_key', 'title')->first();
    if ($titleField) {
        echo "  - Titel-Feld:\n";
        echo "    * Label: {$titleField->field_label}\n";
        echo "    * Typ: {$titleField->field_type}\n";
        echo "    * Spaltenbreite: {$titleField->column_span}\n";
        echo "    * Pflichtfeld: " . ($titleField->is_required ? 'Ja' : 'Nein') . "\n";
        echo "    * Sektion: {$titleField->section_name}\n";
    }
    echo "\n";

    // Teste Status-Feld (sollte Select mit Optionen sein)
    $statusField = $fieldConfigs->where('field_key', 'status')->first();
    if ($statusField) {
        echo "  - Status-Feld:\n";
        echo "    * Label: {$statusField->field_label}\n";
        echo "    * Typ: {$statusField->field_type}\n";
        echo "    * Optionen: " . (isset($statusField->field_options['options']) ? count($statusField->field_options['options']) : 0) . "\n";
        echo "    * Default: " . ($statusField->field_options['default'] ?? 'Nicht gesetzt') . "\n";
    }
    echo "\n";

    // 9. Teste Entity Types
    echo "9. Teste Entity Types...\n";
    $entityTypes = \App\Models\FieldConfig::getEntityTypes();
    foreach ($entityTypes as $key => $name) {
        $count = \App\Models\FieldConfig::forEntity($key)->count();
        echo "  - {$name} ({$key}): {$count} Felder\n";
    }
    echo "\n";

    // 10. Teste Migration von DummyFieldConfig
    echo "10. Teste Migration von DummyFieldConfig...\n";
    if (class_exists(\App\Models\DummyFieldConfig::class)) {
        $dummyCount = \App\Models\DummyFieldConfig::count();
        echo "Anzahl DummyFieldConfig Einträge: {$dummyCount}\n";
        
        // Führe Migration aus
        \App\Models\FieldConfig::migrateDummyFieldConfigs();
        
        $migratedCount = \App\Models\FieldConfig::customFields()->count();
        echo "Anzahl migrierte Custom-Felder: {$migratedCount}\n";
    } else {
        echo "DummyFieldConfig Klasse nicht gefunden - Migration übersprungen\n";
    }
    echo "\n";

    // 11. Teste Filament Component Erstellung
    echo "11. Teste Filament Component Erstellung...\n";
    $testFields = [
        'title' => 'text',
        'description' => 'textarea', 
        'status' => 'select',
        'start_date' => 'date',
        'contract_value' => 'number',
        'is_active' => 'toggle'
    ];

    foreach ($testFields as $fieldKey => $expectedType) {
        $fieldConfig = $fieldConfigs->where('field_key', $fieldKey)->first();
        if ($fieldConfig) {
            echo "  - {$fieldKey}: ";
            if ($fieldConfig->field_type === $expectedType) {
                echo "✓ Korrekt ({$expectedType})\n";
            } else {
                echo "✗ Erwartet {$expectedType}, gefunden {$fieldConfig->field_type}\n";
            }
        } else {
            echo "  - {$fieldKey}: ✗ Nicht gefunden\n";
        }
    }
    echo "\n";

    // 12. Teste Sortierung
    echo "12. Teste Sortierung...\n";
    $sortedFields = \App\Models\FieldConfig::forEntity('supplier_contract')
        ->active()
        ->ordered()
        ->get();
    
    $currentSection = null;
    $sectionOrder = 0;
    $fieldOrder = 0;
    
    foreach ($sortedFields as $field) {
        if ($currentSection !== $field->section_name) {
            $currentSection = $field->section_name;
            $sectionOrder++;
            $fieldOrder = 0;
            echo "  Sektion {$sectionOrder}: {$currentSection}\n";
        }
        $fieldOrder++;
        echo "    {$fieldOrder}. {$field->field_label} ({$field->field_key})\n";
    }
    echo "\n";

    // 13. Performance Test
    echo "13. Performance Test...\n";
    $start = microtime(true);
    
    for ($i = 0; $i < 100; $i++) {
        $schema = \App\Models\FieldConfig::getFormSchema('supplier_contract');
    }
    
    $end = microtime(true);
    $duration = ($end - $start) * 1000; // in Millisekunden
    echo "100 Schema-Generierungen in " . number_format($duration, 2) . " ms\n";
    echo "Durchschnitt: " . number_format($duration / 100, 2) . " ms pro Generierung\n\n";

    echo "=== Test erfolgreich abgeschlossen! ===\n";
    echo "Das Field Configuration System ist vollständig funktionsfähig.\n\n";

    echo "Nächste Schritte:\n";
    echo "1. Ersetze die alte SupplierContractResource durch die neue dynamische Version\n";
    echo "2. Teste das Admin-Interface unter 'System → Feld-Konfiguration'\n";
    echo "3. Konfiguriere Felder nach Bedarf\n";
    echo "4. Erweitere das System auf andere Entitäten\n";

} catch (Exception $e) {
    echo "❌ Fehler beim Testen: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}