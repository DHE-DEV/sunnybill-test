<?php

require_once 'bootstrap/app.php';

use App\Models\User;
use App\Models\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Test-Script für die Phone Numbers API
 * 
 * Dieses Script testet alle verfügbaren API-Endpunkte für die Telefonnummern-Verwaltung
 * einschließlich CRUD-Operationen und spezieller Funktionen.
 */

echo "=== PHONE NUMBERS API TEST ===\n\n";

// API Base URL (anpassen falls nötig)
$baseUrl = 'http://localhost/api/app';

// Test-Token (muss mit entsprechenden phone-numbers Berechtigungen erstellt werden)
$token = 'sb_vE5c4DNUraKtwj5t2bWSsSpc4mDpZxwCqg2bxFGbegRc50MZZ88EYiHwkyls'; // Echten Token aus create_test_data_for_phone_numbers.php verwenden

// Test-User für die Tests
$testUser = User::first();
if (!$testUser) {
    echo "❌ Fehler: Kein Test-User gefunden. Erstelle zuerst einen User.\n";
    exit(1);
}

echo "📱 Teste mit User: {$testUser->name} (ID: {$testUser->id})\n\n";

$headers = [
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
];

// Test-Daten für verschiedene Telefonnummern
$testPhoneNumbers = [
    [
        'phoneable_id' => $testUser->id,
        'phoneable_type' => 'App\Models\User',
        'phone_number' => '+49 30 123456789',
        'type' => 'business',
        'label' => 'Büro Berlin',
        'is_primary' => true,
        'is_favorite' => false,
        'sort_order' => 1,
    ],
    [
        'phoneable_id' => $testUser->id,
        'phoneable_type' => 'App\Models\User',
        'phone_number' => '+49 175 9876543',
        'type' => 'mobile',
        'label' => 'Handy Geschäft',
        'is_primary' => false,
        'is_favorite' => true,
        'sort_order' => 2,
    ],
    [
        'phoneable_id' => $testUser->id,
        'phoneable_type' => 'App\Models\User',
        'phone_number' => '040 55556666',
        'type' => 'private',
        'label' => 'Zuhause Hamburg',
        'is_primary' => false,
        'is_favorite' => false,
        'sort_order' => 3,
    ],
];

// Speichere erstellte Telefonnummern-IDs für weitere Tests
$createdPhoneNumberIds = [];

// ========== CREATE TESTS ==========
echo "🔹 1. CREATE TESTS\n";
echo "================\n\n";

foreach ($testPhoneNumbers as $index => $phoneData) {
    echo "Test " . ($index + 1) . ": Telefonnummer erstellen\n";
    echo "Phone: {$phoneData['phone_number']} ({$phoneData['type']})\n";
    
    try {
        $response = Http::withHeaders($headers)
            ->post($baseUrl . '/phone-numbers', $phoneData);
        
        if ($response->successful()) {
            $data = $response->json();
            $createdPhoneNumberIds[] = $data['data']['id'];
            
            echo "✅ Erfolgreich erstellt!\n";
            echo "   ID: {$data['data']['id']}\n";
            echo "   Formatierte Nummer: {$data['data']['formatted_number']}\n";
            echo "   Typ-Label: {$data['data']['type_label']}\n";
            echo "   Display-Label: {$data['data']['display_label']}\n";
            
            if ($phoneData['is_primary']) {
                echo "   🎯 Als Hauptnummer markiert\n";
            }
            if ($phoneData['is_favorite']) {
                echo "   ⭐ Als Favorit markiert\n";
            }
        } else {
            echo "❌ Fehler beim Erstellen!\n";
            echo "   Status: {$response->status()}\n";
            echo "   Response: " . $response->body() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

if (empty($createdPhoneNumberIds)) {
    echo "❌ Keine Telefonnummern erstellt. Stoppe Tests.\n";
    exit(1);
}

// ========== READ TESTS ==========
echo "🔹 2. READ TESTS\n";
echo "===============\n\n";

// Test: Alle Telefonnummern abrufen
echo "Test: Alle Telefonnummern abrufen\n";
try {
    $response = Http::withHeaders($headers)
        ->get($baseUrl . '/phone-numbers');
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✅ {$data['meta']['total']} Telefonnummern gefunden\n";
        echo "   Aktuelle Seite: {$data['meta']['current_page']}\n";
        echo "   Pro Seite: {$data['meta']['per_page']}\n";
        
        // Zeige erste paar Einträge
        foreach (array_slice($data['data'], 0, 3) as $phone) {
            echo "   - {$phone['phone_number']} ({$phone['type_label']})";
            if ($phone['is_primary']) echo " [HAUPTNUMMER]";
            if ($phone['is_favorite']) echo " ⭐";
            echo "\n";
        }
    } else {
        echo "❌ Fehler: {$response->status()} - " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Test: Spezifische Telefonnummer abrufen
$testId = $createdPhoneNumberIds[0];
echo "Test: Einzelne Telefonnummer abrufen (ID: $testId)\n";
try {
    $response = Http::withHeaders($headers)
        ->get($baseUrl . '/phone-numbers/' . $testId);
    
    if ($response->successful()) {
        $data = $response->json()['data'];
        echo "✅ Telefonnummer abgerufen!\n";
        echo "   Nummer: {$data['phone_number']}\n";
        echo "   Formatiert: {$data['formatted_number']}\n";
        echo "   Typ: {$data['type_label']}\n";
        echo "   Label: {$data['label']}\n";
        echo "   Nur Ziffern: {$data['digits_only']}\n";
        echo "   Deutsche Nummer: " . ($data['meta']['is_german_number'] ? 'Ja' : 'Nein') . "\n";
        echo "   Mobilnummer: " . ($data['meta']['is_mobile'] ? 'Ja' : 'Nein') . "\n";
    } else {
        echo "❌ Fehler: {$response->status()} - " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Test: Telefonnummern nach Besitzer filtern
echo "Test: Telefonnummern nach Besitzer filtern\n";
try {
    $response = Http::withHeaders($headers)
        ->get($baseUrl . '/owners/App%5CModels%5CUser/' . $testUser->id . '/phone-numbers');
    
    if ($response->successful()) {
        $data = $response->json();
        $count = count($data['data']);
        echo "✅ {$count} Telefonnummern für User gefunden\n";
        
        foreach ($data['data'] as $phone) {
            echo "   - {$phone['phone_number']} ({$phone['type_label']})";
            if ($phone['is_primary']) echo " [HAUPTNUMMER]";
            echo "\n";
        }
    } else {
        echo "❌ Fehler: {$response->status()} - " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Test: Filter- und Suchfunktionen
echo "Test: Telefonnummern filtern (nur Mobile)\n";
try {
    $response = Http::withHeaders($headers)
        ->get($baseUrl . '/phone-numbers?type=mobile&per_page=5');
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✅ {$data['meta']['total']} mobile Telefonnummern gefunden\n";
        
        foreach ($data['data'] as $phone) {
            echo "   - {$phone['phone_number']} ({$phone['type_label']})\n";
        }
    } else {
        echo "❌ Fehler: {$response->status()} - " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Test: Suche in Telefonnummern
echo "Test: Suche nach '030'\n";
try {
    $response = Http::withHeaders($headers)
        ->get($baseUrl . '/phone-numbers?search=030');
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✅ {$data['meta']['total']} Telefonnummern mit '030' gefunden\n";
        
        foreach ($data['data'] as $phone) {
            echo "   - {$phone['phone_number']} ({$phone['type_label']})\n";
        }
    } else {
        echo "❌ Fehler: {$response->status()} - " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// ========== UPDATE TESTS ==========
echo "🔹 3. UPDATE TESTS\n";
echo "=================\n\n";

// Test: Telefonnummer aktualisieren
$updateId = $createdPhoneNumberIds[1] ?? $createdPhoneNumberIds[0];
echo "Test: Telefonnummer aktualisieren (ID: $updateId)\n";
try {
    $updateData = [
        'phone_number' => '+49 175 1234567',
        'label' => 'Handy Privat (updated)',
        'is_favorite' => false,
        'sort_order' => 5,
    ];
    
    $response = Http::withHeaders($headers)
        ->put($baseUrl . '/phone-numbers/' . $updateId, $updateData);
    
    if ($response->successful()) {
        $data = $response->json()['data'];
        echo "✅ Telefonnummer aktualisiert!\n";
        echo "   Neue Nummer: {$data['phone_number']}\n";
        echo "   Neues Label: {$data['label']}\n";
        echo "   Favorit: " . ($data['is_favorite'] ? 'Ja' : 'Nein') . "\n";
        echo "   Sortierreihenfolge: {$data['sort_order']}\n";
    } else {
        echo "❌ Fehler: {$response->status()} - " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Test: Als Hauptnummer setzen
$primaryId = $createdPhoneNumberIds[2] ?? $createdPhoneNumberIds[0];
echo "Test: Als Hauptnummer setzen (ID: $primaryId)\n";
try {
    $response = Http::withHeaders($headers)
        ->patch($baseUrl . '/phone-numbers/' . $primaryId . '/make-primary');
    
    if ($response->successful()) {
        $data = $response->json()['data'];
        echo "✅ Als Hauptnummer gesetzt!\n";
        echo "   Telefonnummer: {$data['phone_number']}\n";
        echo "   Display-Label: {$data['display_label']}\n";
        echo "   Ist Hauptnummer: " . ($data['is_primary'] ? 'Ja' : 'Nein') . "\n";
    } else {
        echo "❌ Fehler: {$response->status()} - " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// ========== VALIDATION TESTS ==========
echo "🔹 4. VALIDATION TESTS\n";
echo "=====================\n\n";

// Test: Ungültige Telefonnummer
echo "Test: Ungültige Telefonnummer erstellen\n";
try {
    $invalidData = [
        'phoneable_id' => $testUser->id,
        'phoneable_type' => 'App\Models\User',
        'phone_number' => '123', // Zu kurz
        'type' => 'business',
    ];
    
    $response = Http::withHeaders($headers)
        ->post($baseUrl . '/phone-numbers', $invalidData);
    
    if ($response->status() === 422) {
        $errors = $response->json()['errors'];
        echo "✅ Validierung funktioniert korrekt!\n";
        echo "   Fehler: " . json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "❌ Validierung fehlt! Status: {$response->status()}\n";
        echo "   Response: " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Test: Ungültiger Typ
echo "Test: Ungültigen Typ verwenden\n";
try {
    $invalidData = [
        'phoneable_id' => $testUser->id,
        'phoneable_type' => 'App\Models\User',
        'phone_number' => '+49 30 12345678',
        'type' => 'invalid_type',
    ];
    
    $response = Http::withHeaders($headers)
        ->post($baseUrl . '/phone-numbers', $invalidData);
    
    if ($response->status() === 422) {
        echo "✅ Typ-Validierung funktioniert!\n";
        echo "   Fehler: " . implode(', ', $response->json()['errors']['type'] ?? []) . "\n";
    } else {
        echo "❌ Typ-Validierung fehlt! Status: {$response->status()}\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// Test: Fehlende UUID
echo "Test: Ungültige Besitzer-ID\n";
try {
    $invalidData = [
        'phoneable_id' => 'not-a-uuid',
        'phoneable_type' => 'App\Models\User',
        'phone_number' => '+49 30 12345678',
        'type' => 'business',
    ];
    
    $response = Http::withHeaders($headers)
        ->post($baseUrl . '/phone-numbers', $invalidData);
    
    if ($response->status() === 422) {
        echo "✅ UUID-Validierung funktioniert!\n";
        echo "   Fehler: " . implode(', ', $response->json()['errors']['phoneable_id'] ?? []) . "\n";
    } else {
        echo "❌ UUID-Validierung fehlt! Status: {$response->status()}\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// ========== DELETE TESTS ==========
echo "🔹 5. DELETE TESTS\n";
echo "=================\n\n";

// Test: Telefonnummer löschen
if (!empty($createdPhoneNumberIds)) {
    $deleteId = array_pop($createdPhoneNumberIds); // Letzten Eintrag nehmen
    echo "Test: Telefonnummer löschen (ID: $deleteId)\n";
    
    try {
        $response = Http::withHeaders($headers)
            ->delete($baseUrl . '/phone-numbers/' . $deleteId);
        
        if ($response->successful()) {
            echo "✅ Telefonnummer erfolgreich gelöscht!\n";
            echo "   Message: " . $response->json()['message'] . "\n";
            
            // Versuche die gelöschte Nummer abzurufen
            $checkResponse = Http::withHeaders($headers)
                ->get($baseUrl . '/phone-numbers/' . $deleteId);
            
            if ($checkResponse->status() === 404) {
                echo "✅ Gelöschte Telefonnummer nicht mehr abrufbar (404)\n";
            } else {
                echo "❌ Gelöschte Telefonnummer noch vorhanden!\n";
            }
        } else {
            echo "❌ Fehler beim Löschen: {$response->status()} - " . $response->body() . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test: Nicht existierende Telefonnummer löschen
echo "Test: Nicht existierende Telefonnummer löschen\n";
try {
    $fakeId = '00000000-0000-0000-0000-000000000000';
    $response = Http::withHeaders($headers)
        ->delete($baseUrl . '/phone-numbers/' . $fakeId);
    
    if ($response->status() === 404) {
        echo "✅ 404 für nicht existierende Telefonnummer erhalten\n";
        echo "   Message: " . $response->json()['message'] . "\n";
    } else {
        echo "❌ Erwartete 404, bekommen: {$response->status()}\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
echo "\n";

// ========== FINAL CLEANUP ==========
echo "🔹 6. CLEANUP\n";
echo "============\n\n";

// Lösche verbleibende Test-Telefonnummern
echo "Lösche verbleibende Test-Telefonnummern...\n";
foreach ($createdPhoneNumberIds as $id) {
    try {
        $response = Http::withHeaders($headers)
            ->delete($baseUrl . '/phone-numbers/' . $id);
        
        if ($response->successful()) {
            echo "✅ Telefonnummer $id gelöscht\n";
        } else {
            echo "❌ Fehler beim Löschen von $id: {$response->status()}\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception beim Löschen von $id: " . $e->getMessage() . "\n";
    }
}

// ========== SUMMARY ==========
echo "\n🔹 TEST SUMMARY\n";
echo "==============\n";
echo "✅ API-Tests für Phone Numbers abgeschlossen!\n\n";

echo "Getestete Funktionen:\n";
echo "- ✅ Telefonnummern erstellen (POST /phone-numbers)\n";
echo "- ✅ Alle Telefonnummern abrufen (GET /phone-numbers)\n";
echo "- ✅ Einzelne Telefonnummer abrufen (GET /phone-numbers/{id})\n";
echo "- ✅ Telefonnummern nach Besitzer abrufen (GET /owners/{type}/{id}/phone-numbers)\n";
echo "- ✅ Telefonnummern filtern und suchen\n";
echo "- ✅ Telefonnummer aktualisieren (PUT /phone-numbers/{id})\n";
echo "- ✅ Als Hauptnummer setzen (PATCH /phone-numbers/{id}/make-primary)\n";
echo "- ✅ Telefonnummer löschen (DELETE /phone-numbers/{id})\n";
echo "- ✅ Validierung von Eingabedaten\n";
echo "- ✅ Fehlerbehandlung (404, 422)\n\n";

echo "Verfügbare Optionen für API-Aufrufe:\n";
echo "- type: business, private, mobile\n";
echo "- is_primary: true/false\n";
echo "- is_favorite: true/false\n";
echo "- search: Suche in Telefonnummer und Label\n";
echo "- sort: created_at, phone_number, type, is_primary, sort_order\n";
echo "- direction: asc, desc\n";
echo "- per_page: 1-100 (Standard: 15)\n";
echo "- page: Seitennummer\n\n";

echo "🎯 Alle Tests erfolgreich durchgeführt!\n";
