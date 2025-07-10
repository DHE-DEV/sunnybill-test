<?php

require_once __DIR__ . '/vendor/autoload.php';

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DocumentPathSetting;
use App\Models\UploadedPdf;
use Illuminate\Support\Facades\Auth;

echo "=== Test: UploadedPdf DocumentPathSetting Integration ===\n\n";

// 1. Prüfe ob UploadedPdf DocumentPathSettings existieren
echo "1. Prüfe DocumentPathSettings für UploadedPdf:\n";
$uploadedPdfSettings = DocumentPathSetting::where('documentable_type', 'App\Models\UploadedPdf')->get();

if ($uploadedPdfSettings->count() > 0) {
    echo "✅ Gefunden: {$uploadedPdfSettings->count()} DocumentPathSettings für UploadedPdf\n";
    foreach ($uploadedPdfSettings as $setting) {
        echo "   - ID: {$setting->id}, Kategorie: " . ($setting->category ?? 'null') . ", Template: {$setting->path_template}\n";
    }
} else {
    echo "❌ Keine DocumentPathSettings für UploadedPdf gefunden\n";
}

echo "\n";

// 2. Teste Pfad-Auflösung
echo "2. Teste Pfad-Auflösung:\n";
$defaultSetting = DocumentPathSetting::where('documentable_type', 'App\Models\UploadedPdf')
    ->whereNull('category')
    ->first();

if ($defaultSetting) {
    echo "✅ Standard-Setting gefunden: {$defaultSetting->path_template}\n";
    
    // Erstelle Test-UploadedPdf
    $testUploadedPdf = new UploadedPdf([
        'name' => 'Test PDF',
        'uploaded_by' => 1, // Angenommen User ID 1 existiert
        'created_at' => now(),
    ]);
    
    try {
        $resolvedPath = $defaultSetting->generatePath($testUploadedPdf);
        echo "✅ Pfad erfolgreich aufgelöst: {$resolvedPath}\n";
    } catch (Exception $e) {
        echo "❌ Fehler bei Pfad-Auflösung: {$e->getMessage()}\n";
    }
} else {
    echo "❌ Kein Standard-Setting gefunden\n";
}

echo "\n";

// 3. Teste verfügbare Platzhalter
echo "3. Teste verfügbare Platzhalter für UploadedPdf:\n";
$placeholders = DocumentPathSetting::getAvailablePlaceholders('App\Models\UploadedPdf');
if (!empty($placeholders)) {
    echo "✅ Verfügbare Platzhalter:\n";
    foreach ($placeholders as $placeholder) {
        echo "   - {$placeholder}\n";
    }
} else {
    echo "❌ Keine Platzhalter verfügbar\n";
}

echo "\n=== Test abgeschlossen ===\n";