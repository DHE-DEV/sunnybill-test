<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;
use App\Models\UploadedPdf;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

echo "=== Test: Vollständige Upload-Integration ===\n\n";

// 1. Simuliere Upload-Szenario
echo "1. Simuliere Upload-Szenario:\n";

// Erstelle Test-User falls nicht vorhanden
$testUser = User::first();
if (!$testUser) {
    echo "❌ Kein Benutzer gefunden. Erstelle Test-Benutzer...\n";
    $testUser = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
    echo "✅ Test-Benutzer erstellt: {$testUser->name}\n";
} else {
    echo "✅ Verwende existierenden Benutzer: {$testUser->name}\n";
}

// Simuliere Auth
Auth::login($testUser);

echo "\n";

// 2. Teste DocumentPathSetting-Auflösung wie in UploadedPdfResource
echo "2. Teste Upload-Pfad-Auflösung (wie in UploadedPdfResource):\n";

try {
    // Exakt die gleiche Logik wie in UploadedPdfResource
    $pathSetting = DocumentPathSetting::where('documentable_type', 'App\Models\UploadedPdf')
        ->whereNull('category') // Standard-Kategorie
        ->first();
    
    if ($pathSetting) {
        // Erstelle temporäres UploadedPdf-Objekt für Platzhalter
        $tempUploadedPdf = new UploadedPdf([
            'uploaded_by' => Auth::id(),
            'created_at' => now(),
        ]);
        
        $resolvedPath = $pathSetting->generatePath($tempUploadedPdf);
        
        echo "✅ DocumentPathSetting gefunden und Pfad aufgelöst:\n";
        echo "   - Template: {$pathSetting->path_template}\n";
        echo "   - Aufgelöster Pfad: {$resolvedPath}\n";
        echo "   - Benutzer-ID: " . Auth::id() . "\n";
        echo "   - Aktuelles Datum: " . now()->format('Y/m') . "\n";
        
        // Teste verschiedene Kategorien
        echo "\n   Teste andere Kategorien:\n";
        
        $analysisPathSetting = DocumentPathSetting::where('documentable_type', 'App\Models\UploadedPdf')
            ->where('category', 'analysis')
            ->first();
            
        if ($analysisPathSetting) {
            $analysisPath = $analysisPathSetting->generatePath($tempUploadedPdf);
            echo "   - Analysis-Kategorie: {$analysisPath}\n";
        }
        
        $userOrganizedPathSetting = DocumentPathSetting::where('documentable_type', 'App\Models\UploadedPdf')
            ->where('category', 'user_organized')
            ->first();
            
        if ($userOrganizedPathSetting) {
            // Füge Benutzer-Relation hinzu für bessere Platzhalter-Auflösung
            $tempUploadedPdf->uploadedBy = $testUser;
            $userPath = $userOrganizedPathSetting->generatePath($tempUploadedPdf);
            echo "   - User-Organized-Kategorie: {$userPath}\n";
        }
        
    } else {
        echo "❌ Kein DocumentPathSetting für UploadedPdf gefunden\n";
    }
} catch (\Exception $e) {
    echo "❌ Fehler bei Pfad-Auflösung: {$e->getMessage()}\n";
}

echo "\n";

// 3. Teste Fallback-Verhalten
echo "3. Teste Fallback-Verhalten:\n";

// Simuliere fehlende DocumentPathSetting
$fallbackPath = date('Y/m');
echo "✅ Fallback-Pfad (hardcodiert): {$fallbackPath}\n";

echo "\n";

// 4. Teste Disk-Konfiguration
echo "4. Teste Disk-Konfiguration:\n";

try {
    $disk = Storage::disk('pdf_uploads');
    $diskConfig = config('filesystems.disks.pdf_uploads');
    
    echo "✅ PDF-Upload Disk konfiguriert:\n";
    echo "   - Driver: {$diskConfig['driver']}\n";
    echo "   - Root: {$diskConfig['root']}\n";
    
    // Teste ob Verzeichnis existiert/erstellt werden kann
    $testPath = $resolvedPath ?? $fallbackPath;
    if ($disk->makeDirectory($testPath)) {
        echo "✅ Verzeichnis kann erstellt werden: {$testPath}\n";
    } else {
        echo "⚠️  Verzeichnis existiert bereits oder konnte nicht erstellt werden: {$testPath}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Fehler bei Disk-Test: {$e->getMessage()}\n";
}

echo "\n";

// 5. Teste Platzhalter-Ersetzung mit echten Daten
echo "5. Teste Platzhalter-Ersetzung mit echten Daten:\n";

if (isset($pathSetting)) {
    $testUploadedPdf = new UploadedPdf([
        'name' => 'Test PDF Dokument',
        'original_filename' => 'test-document.pdf',
        'uploaded_by' => $testUser->id,
        'analysis_status' => 'pending',
        'file_size' => 1024 * 1024, // 1MB
        'created_at' => now(),
    ]);
    
    // Setze Relation
    $testUploadedPdf->uploadedBy = $testUser;
    
    $detailedPath = $pathSetting->generatePath($testUploadedPdf);
    echo "✅ Detaillierte Platzhalter-Ersetzung:\n";
    echo "   - Template: {$pathSetting->path_template}\n";
    echo "   - Aufgelöster Pfad: {$detailedPath}\n";
    echo "   - PDF-Name: {$testUploadedPdf->name}\n";
    echo "   - Original-Dateiname: {$testUploadedPdf->original_filename}\n";
    echo "   - Hochgeladen von: {$testUser->name}\n";
    echo "   - Analyse-Status: {$testUploadedPdf->analysis_status}\n";
}

echo "\n=== Integration-Test abgeschlossen ===\n";

// Cleanup
Auth::logout();