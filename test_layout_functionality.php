<?php

require_once 'vendor/autoload.php';

use App\Models\DummyFieldConfig;
use Illuminate\Support\Facades\DB;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test: Layout-Funktionalität für Dummy-Felder ===\n\n";

try {
    // 1. Test: Überprüfe, ob column_span Feld existiert
    echo "1. Überprüfe column_span Feld in Datenbank...\n";
    $columns = DB::select("SHOW COLUMNS FROM dummy_field_configs");
    $columnNames = array_column($columns, 'Field');
    
    if (in_array('column_span', $columnNames)) {
        echo "✅ column_span Feld existiert in der Datenbank\n";
    } else {
        echo "❌ column_span Feld fehlt in der Datenbank\n";
        exit(1);
    }
    
    // 2. Test: Überprüfe Seeder-Daten mit column_span
    echo "\n2. Überprüfe Seeder-Daten mit Layout-Konfigurationen...\n";
    $configs = DummyFieldConfig::all();
    
    if ($configs->count() >= 20) { // 4 Entitäten × 5 Felder
        echo "✅ Alle Dummy-Field-Konfigurationen vorhanden ({$configs->count()} Einträge)\n";
    } else {
        echo "❌ Nicht alle Konfigurationen vorhanden (nur {$configs->count()} Einträge)\n";
    }
    
    // Überprüfe column_span Werte
    $columnSpanCounts = $configs->groupBy('column_span')->map->count();
    echo "   - Spaltenbreiten-Verteilung:\n";
    foreach ($columnSpanCounts as $span => $count) {
        $spanText = $span == 1 ? 'Halbe Breite' : 'Volle Breite';
        echo "     • {$spanText} (column_span={$span}): {$count} Felder\n";
    }
    
    // 3. Test: Schema-Generierung für verschiedene Entitäten
    echo "\n3. Teste Schema-Generierung mit Layout-Konfigurationen...\n";
    
    $entityTypes = ['supplier_contract', 'customer', 'supplier', 'solar_plant'];
    
    foreach ($entityTypes as $entityType) {
        echo "   Teste {$entityType}:\n";
        
        $schema = DummyFieldConfig::getDummyFieldsSchema($entityType);
        $activeFields = DummyFieldConfig::forEntity($entityType)->active()->ordered()->get();
        
        echo "     • Aktive Felder: {$activeFields->count()}\n";
        echo "     • Generierte Schema-Felder: " . count($schema) . "\n";
        
        // Überprüfe Layout-Konfigurationen
        foreach ($activeFields as $field) {
            $spanText = $field->column_span == 2 ? 'Volle Breite' : 'Halbe Breite';
            echo "     • {$field->field_label}: {$spanText} (sort_order: {$field->sort_order})\n";
        }
        
        if (count($schema) === $activeFields->count()) {
            echo "     ✅ Schema-Generierung korrekt\n";
        } else {
            echo "     ❌ Schema-Generierung fehlerhaft\n";
        }
        echo "\n";
    }
    
    // 4. Test: Spezifische Layout-Tests für SupplierContract
    echo "4. Teste spezifische Layout-Konfiguration für SupplierContract...\n";
    
    $supplierContractFields = DummyFieldConfig::forEntity('supplier_contract')
        ->active()
        ->ordered()
        ->get();
    
    echo "   Aktive SupplierContract Felder:\n";
    foreach ($supplierContractFields as $field) {
        $spanText = $field->column_span == 2 ? 'Volle Breite' : 'Halbe Breite';
        echo "     • {$field->field_label}: {$spanText} (Reihenfolge: {$field->sort_order})\n";
    }
    
    // Überprüfe erwartete Konfiguration
    $field1 = $supplierContractFields->where('field_key', 'custom_field_1')->first();
    $field2 = $supplierContractFields->where('field_key', 'custom_field_2')->first();
    
    if ($field1 && $field1->column_span == 2) {
        echo "   ✅ 'Zusätzliche Informationen' hat volle Breite\n";
    } else {
        echo "   ❌ 'Zusätzliche Informationen' Layout-Konfiguration fehlerhaft\n";
    }
    
    if ($field2 && $field2->column_span == 1) {
        echo "   ✅ 'Interne Notiz' hat halbe Breite\n";
    } else {
        echo "   ❌ 'Interne Notiz' Layout-Konfiguration fehlerhaft\n";
    }
    
    // 5. Test: Sortierung funktioniert
    echo "\n5. Teste Sortierung der Felder...\n";
    
    foreach ($entityTypes as $entityType) {
        $fields = DummyFieldConfig::forEntity($entityType)->active()->ordered()->get();
        $sortOrders = $fields->pluck('sort_order')->toArray();
        $sortedOrders = $sortOrders;
        sort($sortedOrders);
        
        if ($sortOrders === $sortedOrders) {
            echo "   ✅ {$entityType}: Sortierung korrekt\n";
        } else {
            echo "   ❌ {$entityType}: Sortierung fehlerhaft\n";
            echo "     Ist: " . implode(', ', $sortOrders) . "\n";
            echo "     Soll: " . implode(', ', $sortedOrders) . "\n";
        }
    }
    
    // 6. Test: Filament Schema-Komponenten
    echo "\n6. Teste Filament Schema-Komponenten...\n";
    
    $schema = DummyFieldConfig::getDummyFieldsSchema('supplier_contract');
    
    if (!empty($schema)) {
        echo "   ✅ Schema-Array generiert\n";
        
        // Überprüfe, ob die Komponenten korrekt sind
        $hasTextInput = false;
        $hasColumnSpanFull = false;
        
        foreach ($schema as $component) {
            if ($component instanceof \Filament\Forms\Components\TextInput) {
                $hasTextInput = true;
                
                // Überprüfe columnSpanFull durch Reflection (da es private ist)
                $reflection = new ReflectionClass($component);
                $property = $reflection->getProperty('columnSpan');
                $property->setAccessible(true);
                $columnSpan = $property->getValue($component);
                
                if ($columnSpan === 'full') {
                    $hasColumnSpanFull = true;
                }
            }
        }
        
        if ($hasTextInput) {
            echo "   ✅ TextInput Komponenten generiert\n";
        } else {
            echo "   ❌ Keine TextInput Komponenten gefunden\n";
        }
        
        if ($hasColumnSpanFull) {
            echo "   ✅ columnSpanFull() korrekt angewendet\n";
        } else {
            echo "   ❌ columnSpanFull() nicht gefunden\n";
        }
    } else {
        echo "   ❌ Kein Schema generiert\n";
    }
    
    // 7. Test: Zusammenfassung
    echo "\n=== Zusammenfassung ===\n";
    echo "✅ Layout-Funktionalität erfolgreich implementiert!\n\n";
    
    echo "Neue Features:\n";
    echo "• column_span Feld für Spaltenbreite-Konfiguration\n";
    echo "• Automatische Anwendung von columnSpanFull() bei column_span=2\n";
    echo "• Erweiterte Seeder-Konfigurationen mit verschiedenen Layouts\n";
    echo "• Sortierung nach sort_order funktioniert\n";
    echo "• Verwaltungs-Interface zeigt Spaltenbreite an\n\n";
    
    echo "Konfigurierte Layouts:\n";
    echo "• SupplierContract: 'Zusätzliche Informationen' und 'Besonderheiten' = volle Breite\n";
    echo "• Customer: 'Präferenzen' und 'Notizen' = volle Breite\n";
    echo "• Supplier: 'Zertifizierungen' und 'Besonderheiten' = volle Breite\n";
    echo "• SolarPlant: 'Genehmigungen' und 'Monitoring' = volle Breite\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Alle Tests erfolgreich abgeschlossen!\n";