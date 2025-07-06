<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Customer;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentFormBuilder;
use App\Services\DocumentStorageService;
use Illuminate\Foundation\Application;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Customer Upload-Directories für alle Kategorien ===\n\n";

try {
    // 1. Lade einen Customer
    $customer = Customer::find('0197cf8d-f14d-70e3-ad9f-70100805b9d8');
    
    if (!$customer) {
        echo "❌ Customer nicht gefunden!\n";
        exit(1);
    }
    
    echo "1. Test-Customer: {$customer->name} ({$customer->customer_number})\n\n";
    
    // 2. Teste alle Kategorien
    $categories = ['contract', 'invoice', 'offer', 'correspondence', 'technical', 'legal', 'other'];
    
    echo "2. Upload-Directories für alle Kategorien:\n";
    
    foreach ($categories as $category) {
        $directory = DocumentStorageService::getUploadDirectoryForModel(
            'customers',
            $customer,
            ['category' => $category]
        );
        
        echo "   {$category}: {$directory}\n";
    }
    
    echo "\n3. Test ohne Kategorie (NULL):\n";
    $directoryNull = DocumentStorageService::getUploadDirectoryForModel(
        'customers',
        $customer,
        []
    );
    echo "   NULL: {$directoryNull}\n";
    
    echo "\n✅ Test abgeschlossen!\n";
    echo "\n=== Erwartung ===\n";
    echo "Alle Pfade sollten mit 'kunden/KD-0001/' beginnen\n";
    echo "und kategorie-spezifische Unterordner haben!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}