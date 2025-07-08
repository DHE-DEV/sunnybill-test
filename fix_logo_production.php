<?php

require_once 'vendor/autoload.php';

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LOGO PRODUCTION FIX ===\n\n";

try {
    // 1. Storage Link erstellen
    echo "1. Erstelle Storage Link...\n";
    Artisan::call('storage:link');
    echo "   ✅ Storage Link erstellt\n\n";
    
    // 2. Prüfe Company Settings
    $settings = CompanySetting::current();
    echo "2. Company Settings:\n";
    echo "   - Company Name: " . ($settings->company_name ?? 'Nicht gesetzt') . "\n";
    echo "   - Logo Path: " . ($settings->logo_path ?? 'Nicht gesetzt') . "\n\n";
    
    if ($settings->logo_path) {
        // 3. Prüfe Logo-Verfügbarkeit
        echo "3. Logo-Verfügbarkeit:\n";
        echo "   - Storage existiert: " . (Storage::exists($settings->logo_path) ? 'JA' : 'NEIN') . "\n";
        echo "   - Public Link existiert: " . (file_exists(public_path('storage/' . $settings->logo_path)) ? 'JA' : 'NEIN') . "\n";
        echo "   - Logo URL: " . asset('storage/' . $settings->logo_path) . "\n\n";
        
        // 4. Teste URL-Zugriff (falls möglich)
        $logoUrl = asset('storage/' . $settings->logo_path);
        echo "4. URL-Test:\n";
        echo "   - Logo URL: " . $logoUrl . "\n";
        
        // Prüfe ob die Datei über HTTP erreichbar ist (nur in Produktionsumgebung sinnvoll)
        if (function_exists('curl_init') && config('app.env') === 'production') {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $logoUrl);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "   - HTTP Status: " . $httpCode . "\n";
            echo "   - Erreichbar: " . ($httpCode === 200 ? 'JA' : 'NEIN') . "\n";
        } else {
            echo "   - HTTP-Test übersprungen (Entwicklungsumgebung)\n";
        }
        
    } else {
        echo "3. Kein Logo hochgeladen\n";
    }
    
    echo "\n5. Berechtigungen prüfen:\n";
    $storagePublicPath = storage_path('app/public');
    $publicStoragePath = public_path('storage');
    
    echo "   - Storage/app/public lesbar: " . (is_readable($storagePublicPath) ? 'JA' : 'NEIN') . "\n";
    echo "   - Storage/app/public schreibbar: " . (is_writable($storagePublicPath) ? 'JA' : 'NEIN') . "\n";
    echo "   - Public/storage existiert: " . (file_exists($publicStoragePath) ? 'JA' : 'NEIN') . "\n";
    
    if (is_link($publicStoragePath)) {
        echo "   - Public/storage ist Symlink: JA\n";
        echo "   - Symlink Ziel: " . readlink($publicStoragePath) . "\n";
    } else {
        echo "   - Public/storage ist Symlink: NEIN\n";
    }
    
    echo "\n✅ Logo Production Fix abgeschlossen!\n";
    echo "\nDas Logo sollte jetzt unter folgender URL verfügbar sein:\n";
    if ($settings->logo_path) {
        echo asset('storage/' . $settings->logo_path) . "\n";
    } else {
        echo "Kein Logo hochgeladen.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIX ABGESCHLOSSEN ===\n";
