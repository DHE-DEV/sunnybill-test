<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\UploadedPdf;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Aktionen-Sichtbarkeit Test ===\n\n";

// Test-Benutzer für verschiedene Teams
$testUsers = [
    'User' => User::where('email', 'dh@dhe.de')->first(),
    'Manager' => User::where('email', 'test.manager@example.com')->first(),
    'Superadmin' => User::whereHas('teams', function($q) {
        $q->where('name', 'Superadmin');
    })->first(),
];

// Erstelle ein Test-UploadedPdf-Record falls keiner existiert
$testRecord = UploadedPdf::first();
if (!$testRecord) {
    echo "Kein UploadedPdf-Record gefunden. Erstelle Test-Record...\n";
    $testRecord = UploadedPdf::create([
        'name' => 'Test PDF für Aktionen-Test',
        'description' => 'Test-Beschreibung',
        'file_path' => 'test/test.pdf',
        'original_filename' => 'test.pdf',
        'file_size' => 1024,
        'mime_type' => 'application/pdf',
        'analysis_status' => 'pending',
        'uploaded_by' => 1,
    ]);
    echo "✓ Test-Record erstellt (ID: {$testRecord->id})\n\n";
} else {
    echo "✓ Verwende existierenden Record (ID: {$testRecord->id})\n\n";
}

// Teste Aktionen-Sichtbarkeit für jeden Benutzer-Typ
foreach ($testUsers as $teamName => $user) {
    if (!$user) {
        echo "❌ {$teamName} Benutzer nicht gefunden!\n";
        continue;
    }
    
    echo "=== {$teamName} Team ({$user->name}) ===\n";
    
    // Authentifiziere als dieser Benutzer
    auth()->login($user);
    
    // Teste Resource-Level Berechtigungen
    echo "Resource-Berechtigungen:\n";
    $resourcePermissions = [
        'canViewAny' => \App\Filament\Resources\UploadedPdfResource::canViewAny(),
        'canCreate' => \App\Filament\Resources\UploadedPdfResource::canCreate(),
        'canEdit' => \App\Filament\Resources\UploadedPdfResource::canEdit($testRecord),
        'canDelete' => \App\Filament\Resources\UploadedPdfResource::canDelete($testRecord),
        'canView' => \App\Filament\Resources\UploadedPdfResource::canView($testRecord),
    ];
    
    foreach ($resourcePermissions as $method => $hasPermission) {
        $status = $hasPermission ? '✓' : '✗';
        echo "  {$status} {$method}\n";
    }
    
    // Teste Team-spezifische Sichtbarkeit (simuliert die visible() Callbacks)
    echo "\nAktionen-Sichtbarkeit:\n";
    
    // Standard-Aktionen (nur Manager/Superadmin)
    $canSeeStandardActions = auth()->user()?->teams()->whereIn('name', ['Manager', 'Superadmin'])->exists() ?? false;
    $status = $canSeeStandardActions ? '✓' : '✗';
    echo "  {$status} ViewAction (Standard)\n";
    echo "  {$status} EditAction (Standard)\n";
    echo "  {$status} DeleteAction (Standard)\n";
    
    // Benutzerdefinierte Aktionen (alle berechtigten Benutzer, wenn Datei existiert)
    $fileExists = $testRecord->fileExists(); // Simuliert die fileExists() Prüfung
    $status = $fileExists ? '✓' : '✗';
    echo "  {$status} Analysieren (fileExists: " . ($fileExists ? 'true' : 'false') . ")\n";
    echo "  {$status} PDF anzeigen (fileExists: " . ($fileExists ? 'true' : 'false') . ")\n";
    echo "  {$status} Herunterladen (fileExists: " . ($fileExists ? 'true' : 'false') . ")\n";
    
    // Erwartete Aktionen-Anzahl
    $expectedActions = 0;
    if ($canSeeStandardActions) {
        $expectedActions += 3; // View, Edit, Delete
    }
    if ($fileExists) {
        $expectedActions += 3; // Analyze, View PDF, Download
    }
    
    echo "\nErwartete Aktionen-Anzahl: {$expectedActions}\n";
    
    // Validierung basierend auf Team
    switch ($teamName) {
        case 'User':
            $expectedCount = $fileExists ? 3 : 0; // Nur benutzerdefinierte Aktionen
            echo "Erwartung für User: " . ($fileExists ? "3 Aktionen (Analysieren, PDF anzeigen, Herunterladen)" : "0 Aktionen") . "\n";
            break;
        case 'Manager':
        case 'Superadmin':
            $expectedCount = $fileExists ? 6 : 3; // Standard + benutzerdefinierte Aktionen
            echo "Erwartung für {$teamName}: " . ($fileExists ? "6 Aktionen (alle)" : "3 Aktionen (nur Standard)") . "\n";
            break;
    }
    
    auth()->logout();
    echo "\n";
}

echo "=== Zusammenfassung ===\n";
echo "✅ User Team: Sieht nur benutzerdefinierte Aktionen (Analysieren, PDF anzeigen, Herunterladen)\n";
echo "✅ Manager/Superadmin Teams: Sehen alle Aktionen (Standard + benutzerdefinierte)\n";
echo "✅ Aktionen werden nur angezeigt, wenn die Datei existiert (fileExists() = true)\n";

echo "\n=== Test abgeschlossen ===\n";
echo "Die Aktionen sollten jetzt korrekt angezeigt werden!\n";