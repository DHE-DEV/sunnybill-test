<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Rollenbasierte Zugriffskontrolle ===\n\n";

try {
    echo "App Environment: " . app()->environment() . "\n";
    echo "App URL: " . config('app.url') . "\n\n";
    
    // Test-Szenarien
    $testUsers = [
        [
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'role' => 'admin',
            'expected' => true,
            'reason' => 'Admin-Rolle'
        ],
        [
            'name' => 'Manager User', 
            'email' => 'manager@yahoo.com',
            'role' => 'manager',
            'expected' => true,
            'reason' => 'Manager-Rolle'
        ],
        [
            'name' => 'Normal User',
            'email' => 'user@gmail.com', 
            'role' => 'user',
            'expected' => false,
            'reason' => 'Normale Benutzer-Rolle ohne @chargedata.eu'
        ],
        [
            'name' => 'ChargeData User',
            'email' => 'employee@chargedata.eu',
            'role' => 'user',
            'expected' => true,
            'reason' => '@chargedata.eu Domain'
        ],
        [
            'name' => 'Inactive Admin',
            'email' => 'inactive@example.com',
            'role' => 'admin',
            'is_active' => false,
            'expected' => false,
            'reason' => 'Inaktiver Benutzer'
        ]
    ];
    
    foreach ($testUsers as $testData) {
        echo "Teste: {$testData['name']} ({$testData['email']}, {$testData['role']})\n";
        
        // Erstelle temporären Benutzer
        $user = User::create([
            'name' => $testData['name'],
            'email' => $testData['email'],
            'password' => Hash::make('password'),
            'role' => $testData['role'],
            'is_active' => $testData['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);
        
        // Teste Panel-Zugriff
        try {
            $panel = \Filament\Facades\Filament::getDefaultPanel();
            $canAccess = $user->canAccessPanel($panel);
            
            $status = $canAccess ? '✅ ZUGRIFF' : '❌ KEIN ZUGRIFF';
            $expected = $testData['expected'] ? '✅ ZUGRIFF' : '❌ KEIN ZUGRIFF';
            $correct = ($canAccess === $testData['expected']) ? '✓' : '❌ FEHLER';
            
            echo "  Ergebnis: {$status} | Erwartet: {$expected} | {$correct}\n";
            echo "  Grund: {$testData['reason']}\n";
            
        } catch (Exception $e) {
            echo "  ❌ Panel-Check Fehler: " . $e->getMessage() . "\n";
        }
        
        // Teste manuelle Fallback-Logik
        $manualCheck = $user->is_active && (
            $user->email === 'admin@example.com' ||
            in_array($user->role, ['admin', 'manager']) ||
            str_ends_with($user->email, '@chargedata.eu') ||
            (app()->environment('local') && str_contains(config('app.url'), '.test'))
        );
        
        echo "  Fallback-Check: " . ($manualCheck ? '✅ ZUGRIFF' : '❌ KEIN ZUGRIFF') . "\n";
        
        // Cleanup
        $user->delete();
        echo "\n";
    }
    
    echo "=== Zusammenfassung der neuen Zugriffskontrolle ===\n";
    echo "✅ Admin-Rolle: Vollzugriff (unabhängig von E-Mail-Domain)\n";
    echo "✅ Manager-Rolle: Vollzugriff (unabhängig von E-Mail-Domain)\n";
    echo "✅ @chargedata.eu: Zugriff für alle Rollen\n";
    echo "✅ admin@example.com: Spezielle Admin-E-Mail\n";
    echo "✅ Lokale Entwicklung: Alle Benutzer haben Zugriff\n";
    echo "❌ Inaktive Benutzer: Kein Zugriff\n";
    echo "❌ Normale Benutzer ohne @chargedata.eu: Kein Zugriff\n\n";
    
    echo "🎯 Lösung für Ihr Problem:\n";
    echo "Setzen Sie die Rolle des Benutzers auf 'admin' oder 'manager',\n";
    echo "dann hat er automatisch Zugriff auf das Admin-Panel,\n";
    echo "unabhängig von der E-Mail-Domain!\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
