<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Produktions-Zugriff auf Admin-Panel ===\n\n";

try {
    // Teste verschiedene E-Mail-Adressen
    $testEmails = [
        'admin@example.com',
        'test@chargedata.eu',
        'user@gmail.com',
        'manager@company.com',
        'viewer@chargedata.eu'
    ];
    
    echo "App Environment: " . app()->environment() . "\n";
    echo "App URL: " . config('app.url') . "\n\n";
    
    foreach ($testEmails as $email) {
        echo "Teste E-Mail: {$email}\n";
        
        // Erstelle temporären Benutzer
        $user = User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'user',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        // Teste Panel-Zugriff
        try {
            $panel = \Filament\Facades\Filament::getDefaultPanel();
            $canAccess = $user->canAccessPanel($panel);
            echo "✓ canAccessPanel(): " . ($canAccess ? 'Ja' : 'Nein') . "\n";
        } catch (Exception $e) {
            echo "❌ Panel-Check Fehler: " . $e->getMessage() . "\n";
        }
        
        // Teste manuelle Prüfung
        $manualCheck = $user->email === 'admin@example.com' ||
                      str_ends_with($user->email, '@chargedata.eu') ||
                      (app()->environment('local') && str_contains(config('app.url'), '.test'));
        
        echo "✓ Manuelle Prüfung: " . ($manualCheck ? 'Ja' : 'Nein') . "\n";
        
        // Prüfe einzelne Bedingungen
        echo "  - admin@example.com: " . ($user->email === 'admin@example.com' ? 'Ja' : 'Nein') . "\n";
        echo "  - @chargedata.eu: " . (str_ends_with($user->email, '@chargedata.eu') ? 'Ja' : 'Nein') . "\n";
        echo "  - Local + .test: " . ((app()->environment('local') && str_contains(config('app.url'), '.test')) ? 'Ja' : 'Nein') . "\n";
        
        // Cleanup
        $user->delete();
        echo "\n";
    }
    
    echo "=== Zusammenfassung ===\n";
    echo "In der Produktionsumgebung haben nur folgende Benutzer Zugriff:\n";
    echo "✅ admin@example.com\n";
    echo "✅ *@chargedata.eu\n";
    echo "❌ Alle anderen E-Mail-Adressen\n\n";
    
    echo "Wenn ein Benutzer ohne Zugriff zu /admin weitergeleitet wird,\n";
    echo "erhält er einen 403 Forbidden Fehler.\n\n";
    
    echo "Die neue Lösung prüft den Zugriff und zeigt stattdessen\n";
    echo "eine Erfolgsseite für Benutzer ohne Admin-Zugriff.\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
