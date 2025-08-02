<?php

// Einfaches PHP-Skript zum Testen der Test-Suite ohne artisan
// Dies hilft bei der Diagnose von Problemen

echo "=== SunnyBill Test Suite Validation ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Working Directory: " . getcwd() . "\n\n";

// Autoloader laden
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "❌ ERROR: vendor/autoload.php not found. Run 'composer install' first.\n";
    exit(1);
}

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
if (!file_exists(__DIR__ . '/bootstrap/app.php')) {
    echo "❌ ERROR: Laravel bootstrap file not found.\n";
    exit(1);
}

try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    echo "✅ Laravel bootstrap loaded successfully\n";
} catch (Exception $e) {
    echo "❌ ERROR loading Laravel: " . $e->getMessage() . "\n";
    exit(1);
}

// Test file validation
$testFiles = [
    'tests/Feature/Api/SimpleTaskTest.php',
    'tests/Feature/Api/TaskApiPestTest.php', 
    'tests/Feature/FactoryTest.php',
    'tests/Feature/BasicConnectionTest.php',
    'tests/Traits/InteractsWithApi.php',
    'tests/Traits/CreatesTestData.php'
];

echo "\n=== Test File Validation ===\n";
foreach ($testFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ {$file} exists\n";
        
        // Syntax check
        $syntax = shell_exec("php -l " . escapeshellarg(__DIR__ . '/' . $file) . " 2>&1");
        if (strpos($syntax, 'No syntax errors') !== false) {
            echo "   ✅ Syntax OK\n";
        } else {
            echo "   ❌ Syntax Error: {$syntax}\n";
        }
    } else {
        echo "❌ {$file} missing\n";
    }
}

// Model validation
echo "\n=== Model Validation ===\n";
$models = ['User', 'Task', 'Customer', 'Supplier', 'SolarPlant'];
foreach ($models as $model) {
    $className = "App\\Models\\{$model}";
    if (class_exists($className)) {
        echo "✅ {$className} exists\n";
        
        // Check if factory exists
        $factoryClass = "Database\\Factories\\{$model}Factory";
        if (class_exists($factoryClass)) {
            echo "   ✅ Factory exists\n";
        } else {
            echo "   ⚠️  Factory missing\n";
        }
    } else {
        echo "❌ {$className} not found\n";
    }
}

// Factory validation
echo "\n=== Factory Validation ===\n";
$factoryFiles = [
    'database/factories/UserFactory.php',
    'database/factories/TaskFactory.php',
    'database/factories/CustomerFactory.php',
    'database/factories/SupplierFactory.php',
    'database/factories/SolarPlantFactory.php'
];

foreach ($factoryFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✅ {$file} exists\n";
    } else {
        echo "❌ {$file} missing\n";
    }
}

// Configuration check
echo "\n=== Configuration Check ===\n";
try {
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "✅ Laravel kernel bootstrapped\n";
    echo "Environment: " . app()->environment() . "\n";
    echo "Database driver: " . config('database.default') . "\n";
    
} catch (Exception $e) {
    echo "❌ Configuration error: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
echo "Test suite structure appears to be ready for execution.\n";
echo "To run tests, use one of these commands:\n";
echo "- php artisan test\n";
echo "- ./vendor/bin/pest\n";
echo "- composer test (if configured)\n";

?>