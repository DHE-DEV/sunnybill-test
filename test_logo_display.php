<?php

require_once 'vendor/autoload.php';

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LOGO DISPLAY DIAGNOSE ===\n\n";

try {
    $settings = CompanySetting::current();
    
    echo "1. Company Settings gefunden:\n";
    echo "   - Company Name: " . ($settings->company_name ?? 'Nicht gesetzt') . "\n";
    echo "   - Logo Path: " . ($settings->logo_path ?? 'Nicht gesetzt') . "\n";
    echo "   - Logo Width: " . ($settings->logo_width ?? 'Nicht gesetzt') . "\n";
    echo "   - Logo Height: " . ($settings->logo_height ?? 'Nicht gesetzt') . "\n\n";
    
    if ($settings->logo_path) {
        echo "2. Logo-Pfad Details:\n";
        echo "   - Relativer Pfad: " . $settings->logo_path . "\n";
        echo "   - Storage URL: " . Storage::url($settings->logo_path) . "\n";
        echo "   - Asset URL: " . asset('storage/' . $settings->logo_path) . "\n";
        echo "   - Datei existiert: " . (Storage::exists($settings->logo_path) ? 'JA' : 'NEIN') . "\n";
        
        if (Storage::exists($settings->logo_path)) {
            echo "   - Dateigröße: " . Storage::size($settings->logo_path) . " Bytes\n";
            echo "   - MIME Type: " . Storage::mimeType($settings->logo_path) . "\n";
        }
        
        echo "\n3. Vollständiger Dateipfad:\n";
        $fullPath = storage_path('app/public/' . $settings->logo_path);
        echo "   - Storage Pfad: " . $fullPath . "\n";
        echo "   - Datei existiert (filesystem): " . (file_exists($fullPath) ? 'JA' : 'NEIN') . "\n";
        
        if (file_exists($fullPath)) {
            echo "   - Dateigröße (filesystem): " . filesize($fullPath) . " Bytes\n";
            echo "   - Berechtigung lesbar: " . (is_readable($fullPath) ? 'JA' : 'NEIN') . "\n";
        }
        
        echo "\n4. Public Storage Link:\n";
        $publicPath = public_path('storage/' . $settings->logo_path);
        echo "   - Public Pfad: " . $publicPath . "\n";
        echo "   - Public Link existiert: " . (file_exists($publicPath) ? 'JA' : 'NEIN') . "\n";
        
        // Prüfe ob storage:link ausgeführt wurde
        $storageLink = public_path('storage');
        echo "   - Storage Link existiert: " . (is_link($storageLink) || is_dir($storageLink) ? 'JA' : 'NEIN') . "\n";
        
        if (is_link($storageLink)) {
            echo "   - Storage Link Ziel: " . readlink($storageLink) . "\n";
        }
        
    } else {
        echo "2. Kein Logo hochgeladen\n";
    }
    
    echo "\n5. Filament Brand Logo Konfiguration:\n";
    echo "   - Brand Name: " . ($settings->company_name ?? 'VoltMaster') . "\n";
    
    $logoUrl = $settings->logo_path 
        ? asset('storage/' . $settings->logo_path)
        : asset('images/voltmaster-logo.svg');
    echo "   - Brand Logo URL: " . $logoUrl . "\n";
    
    echo "\n6. Storage Konfiguration:\n";
    echo "   - Default Disk: " . config('filesystems.default') . "\n";
    echo "   - Public Disk Root: " . config('filesystems.disks.public.root') . "\n";
    echo "   - Public Disk URL: " . config('filesystems.disks.public.url') . "\n";
    echo "   - APP_URL: " . config('app.url') . "\n";
    
} catch (\Exception $e) {
    echo "FEHLER: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DIAGNOSE ABGESCHLOSSEN ===\n";
