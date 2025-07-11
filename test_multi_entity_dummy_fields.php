<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DummyFieldConfig;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use App\Models\SupplierContract;

echo "=== Multi-Entity Dummy Fields Test ===\n\n";

// 1. Test DummyFieldConfig für alle Entitätstypen
echo "1. Teste DummyFieldConfig für alle Entitätstypen:\n";
$entityTypes = DummyFieldConfig::getEntityTypes();
foreach ($entityTypes as $key => $label) {
    $configs = DummyFieldConfig::forEntity($key)->get();
    echo "   - {$label} ({$key}): " . $configs->count() . " Konfigurationen\n";
    
    foreach ($configs as $config) {
        echo "     * {$config->field_label} ({$config->field_key}) - " . 
             ($config->is_active ? 'Aktiv' : 'Inaktiv') . "\n";
    }
}

echo "\n";

// 2. Test Schema-Generierung für jede Entität
echo "2. Teste Schema-Generierung:\n";
foreach ($entityTypes as $key => $label) {
    try {
        $schema = DummyFieldConfig::getDummyFieldsSchema($key);
        echo "   - {$label}: " . count($schema) . " Felder generiert\n";
    } catch (Exception $e) {
        echo "   - {$label}: FEHLER - " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 3. Test Model-Erweiterungen
echo "3. Teste Model-Erweiterungen (fillable Felder):\n";

// Customer
$customerFillable = (new Customer())->getFillable();
$customerCustomFields = array_filter($customerFillable, fn($field) => str_starts_with($field, 'custom_field_'));
echo "   - Customer: " . count($customerCustomFields) . " custom_field_* Felder\n";
echo "     " . implode(', ', $customerCustomFields) . "\n";

// Supplier
$supplierFillable = (new Supplier())->getFillable();
$supplierCustomFields = array_filter($supplierFillable, fn($field) => str_starts_with($field, 'custom_field_'));
echo "   - Supplier: " . count($supplierCustomFields) . " custom_field_* Felder\n";
echo "     " . implode(', ', $supplierCustomFields) . "\n";

// SolarPlant
$solarPlantFillable = (new SolarPlant())->getFillable();
$solarPlantCustomFields = array_filter($solarPlantFillable, fn($field) => str_starts_with($field, 'custom_field_'));
echo "   - SolarPlant: " . count($solarPlantCustomFields) . " custom_field_* Felder\n";
echo "     " . implode(', ', $solarPlantCustomFields) . "\n";

// SupplierContract
$supplierContractFillable = (new SupplierContract())->getFillable();
$supplierContractCustomFields = array_filter($supplierContractFillable, fn($field) => str_starts_with($field, 'custom_field_'));
echo "   - SupplierContract: " . count($supplierContractCustomFields) . " custom_field_* Felder\n";
echo "     " . implode(', ', $supplierContractCustomFields) . "\n";

echo "\n";

// 4. Test Datenbank-Struktur
echo "4. Teste Datenbank-Struktur:\n";
try {
    $tables = ['customers', 'suppliers', 'solar_plants', 'supplier_contracts'];
    foreach ($tables as $table) {
        $columns = DB::select("SHOW COLUMNS FROM {$table} LIKE 'custom_field_%'");
        echo "   - {$table}: " . count($columns) . " custom_field_* Spalten\n";
        foreach ($columns as $column) {
            echo "     * {$column->Field} ({$column->Type})\n";
        }
    }
} catch (Exception $e) {
    echo "   FEHLER beim Testen der Datenbank-Struktur: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Test Unique Constraint
echo "5. Teste Unique Constraint:\n";
try {
    // Versuche doppelten Eintrag zu erstellen
    $existingConfig = DummyFieldConfig::first();
    if ($existingConfig) {
        echo "   - Teste Duplikat für: {$existingConfig->entity_type} / {$existingConfig->field_key}\n";
        
        try {
            DummyFieldConfig::create([
                'entity_type' => $existingConfig->entity_type,
                'field_key' => $existingConfig->field_key,
                'field_label' => 'Test Duplikat',
                'is_active' => true,
                'sort_order' => 999
            ]);
            echo "   - FEHLER: Duplikat wurde erstellt (sollte nicht passieren!)\n";
        } catch (Exception $e) {
            echo "   - OK: Duplikat wurde korrekt verhindert\n";
            echo "     Fehler: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "   FEHLER beim Testen des Unique Constraints: " . $e->getMessage() . "\n";
}

echo "\n=== Test abgeschlossen ===\n";