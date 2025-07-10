<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\UploadedPdf;
use Illuminate\Support\Facades\Storage;

echo "=== PDF-Pfade Korrektur ===\n\n";

$pdfs = UploadedPdf::all();
echo "Gefundene PDFs: " . $pdfs->count() . "\n\n";

foreach ($pdfs as $pdf) {
    $oldPath = $pdf->file_path;
    
    // Entferne doppeltes "pdf-uploads/" am Anfang
    $newPath = preg_replace('/^pdf-uploads\//', '', $oldPath);
    
    echo "PDF ID {$pdf->id}:\n";
    echo "  Alt: {$oldPath}\n";
    echo "  Neu: {$newPath}\n";
    
    // Prüfe ob die Datei mit dem neuen Pfad existiert
    $existsNew = Storage::disk('pdf_uploads')->exists($newPath);
    echo "  Neue Datei existiert: " . ($existsNew ? "✅ JA" : "❌ NEIN") . "\n";
    
    if ($existsNew) {
        echo "  ✅ Pfad wird aktualisiert\n";
        $pdf->update(['file_path' => $newPath]);
    } else {
        echo "  ⚠️  Datei nicht gefunden - Pfad wird NICHT aktualisiert\n";
    }
    
    echo str_repeat("-", 40) . "\n";
}

echo "\n=== Korrektur abgeschlossen ===\n";

// Teste die Aktionen-Sichtbarkeit nach der Korrektur
echo "\n=== Test nach Korrektur ===\n";
$pdfs = UploadedPdf::all();
foreach ($pdfs as $pdf) {
    $exists = $pdf->fileExists();
    echo "PDF ID {$pdf->id}: Datei existiert = " . ($exists ? "✅ JA" : "❌ NEIN") . "\n";
}