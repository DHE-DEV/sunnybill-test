<?php

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\AppToken;

echo "=== Test: Customer API Response - Welche Daten werden zurückgegeben? ===\n\n";

try {
    // Test 1: Direkte Controller Simulation
    echo "1. Teste CustomerApiController->index() Simulation...\n";
    
    $query = Customer::with(['solarPlants', 'participations']);
    
    // Simuliere die Paginierung wie im Controller
    $perPage = 15;
    $customers = $query->paginate($perPage);
    
    echo "✅ Query funktioniert\n";
    echo "   Anzahl Kunden: " . $customers->total() . "\n";
    echo "   Aktuelle Seite: " . $customers->currentPage() . "\n";
    echo "   Pro Seite: " . $customers->perPage() . "\n\n";
    
    if ($customers->count() > 0) {
        $firstCustomer = $customers->first();
        echo "2. Erste Kunde Datenstruktur:\n";
        echo "   ID: " . $firstCustomer->id . "\n";
        echo "   Name: " . $firstCustomer->name . "\n";
        echo "   Email: " . $firstCustomer->email . "\n";
        echo "   Customer Type: " . $firstCustomer->customer_type . "\n";
        echo "   Customer Number: " . $firstCustomer->customer_number . "\n";
        echo "   Company Name: " . $firstCustomer->company_name . "\n";
        echo "   Phone: " . $firstCustomer->phone . "\n";
        echo "   Street: " . $firstCustomer->street . "\n";
        echo "   City: " . $firstCustomer->city . "\n";
        echo "   Postal Code: " . $firstCustomer->postal_code . "\n";
        echo "   Is Active: " . ($firstCustomer->is_active ? 'Yes' : 'No') . "\n";
        echo "   Created At: " . $firstCustomer->created_at . "\n";
        
        echo "\n   Beziehungen:\n";
        echo "   - solarPlants: " . $firstCustomer->solarPlants->count() . "\n";
        echo "   - participations: " . $firstCustomer->participations->count() . "\n";
        
        echo "\n3. Vollständige Datenstruktur als Array:\n";
        $customerArray = $firstCustomer->toArray();
        
        echo "   Verfügbare Felder:\n";
        foreach (array_keys($customerArray) as $field) {
            $value = $customerArray[$field];
            if (is_array($value)) {
                echo "   - {$field}: [Array mit " . count($value) . " Elementen]\n";
            } else {
                $displayValue = is_null($value) ? 'NULL' : (is_bool($value) ? ($value ? 'true' : 'false') : $value);
                echo "   - {$field}: {$displayValue}\n";
            }
        }
    }
    
    echo "\n4. Teste Response Format wie in Controller:\n";
    $response = [
        'success' => true,
        'data' => $customers->items(),
        'pagination' => [
            'current_page' => $customers->currentPage(),
            'last_page' => $customers->lastPage(),
            'per_page' => $customers->perPage(),
            'total' => $customers->total(),
        ]
    ];
    
    echo "✅ Response Struktur erstellt\n";
    echo "   Success: " . ($response['success'] ? 'true' : 'false') . "\n";
    echo "   Data Count: " . count($response['data']) . "\n";
    echo "   Pagination: " . json_encode($response['pagination']) . "\n";
    
    echo "\n5. Prüfe Token-Berechtigung:\n";
    $token = AppToken::where('name', 'like', '%customers%')
        ->whereJsonContains('abilities', 'customers:read')
        ->first();
    
    if ($token) {
        echo "✅ Token mit customers:read Berechtigung gefunden\n";
        echo "   Token Name: " . $token->name . "\n";
        echo "   Token ID: " . $token->id . "\n";
        echo "   Berechtigungen: " . json_encode($token->abilities) . "\n";
    } else {
        echo "❌ Kein Token mit customers:read Berechtigung gefunden\n";
    }
    
    echo "\n🎉 Test abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
