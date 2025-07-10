<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Team;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Manager Team Zugriff Test ===\n\n";

// Prüfe ob Manager Team existiert
$managerTeam = Team::where('name', 'Manager')->first();
if (!$managerTeam) {
    echo "❌ Manager Team nicht gefunden!\n";
    echo "Verfügbare Teams:\n";
    $teams = Team::all();
    foreach ($teams as $team) {
        echo "  - {$team->name} (ID: {$team->id})\n";
    }
    exit(1);
}

echo "✓ Manager Team gefunden (ID: {$managerTeam->id})\n\n";

// Suche nach einem Benutzer im Manager Team
$managerUser = $managerTeam->users()->first();

if (!$managerUser) {
    echo "❌ Kein Benutzer im Manager Team gefunden!\n";
    echo "Erstelle Test-Benutzer für Manager Team...\n";
    
    // Erstelle Test-Benutzer
    $managerUser = User::create([
        'name' => 'Test Manager',
        'email' => 'test.manager@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    
    // Weise Manager Team zu
    $managerUser->teams()->attach($managerTeam->id);
    
    echo "✓ Test-Benutzer erstellt: {$managerUser->name} ({$managerUser->email})\n\n";
} else {
    echo "✓ Manager-Benutzer gefunden: {$managerUser->name} ({$managerUser->email})\n\n";
}

// Authentifiziere als Manager-Benutzer
auth()->login($managerUser);

echo "Teste Zugriff als Manager-Benutzer:\n";

// Teste UploadedPdfResource Zugriff
$uploadedPdfAccess = \App\Filament\Resources\UploadedPdfResource::canViewAny();
$status = $uploadedPdfAccess ? '✓' : '✗';
echo "  {$status} UploadedPdfResource::canViewAny()\n";

// Teste andere PDF-Analyse-System Ressourcen (sollten weiterhin blockiert sein)
$pdfAnalysisResources = [
    'PdfExtractionRuleResource' => \App\Filament\Resources\PdfExtractionRuleResource::class,
    'ContractMatchingRuleResource' => \App\Filament\Resources\ContractMatchingRuleResource::class,
    'SupplierRecognitionPatternResource' => \App\Filament\Resources\SupplierRecognitionPatternResource::class,
];

echo "\nAndere PDF-Analyse-System Ressourcen (sollten blockiert bleiben):\n";
foreach ($pdfAnalysisResources as $name => $class) {
    if (class_exists($class)) {
        $canView = $class::canViewAny();
        $status = $canView ? '✓' : '✗';
        echo "  {$status} {$name}\n";
    }
}

// Teste System-Ressourcen (sollten blockiert bleiben)
echo "\nSystem-Ressourcen (sollten blockiert bleiben):\n";
$systemResources = [
    'UserResource' => \App\Filament\Resources\UserResource::class,
    'TeamResource' => \App\Filament\Resources\TeamResource::class,
];

foreach ($systemResources as $name => $class) {
    if (class_exists($class)) {
        $canView = $class::canViewAny();
        $status = $canView ? '✓' : '✗';
        echo "  {$status} {$name}\n";
    }
}

echo "\n=== Ergebnis ===\n";
if ($uploadedPdfAccess) {
    echo "✅ KORREKT: Manager-Benutzer hat Zugriff auf UploadedPdfResource\n";
    echo "✅ PDF-Analyse System Navigation wird für Manager-Benutzer angezeigt\n";
} else {
    echo "❌ FEHLER: Manager-Benutzer hat keinen Zugriff auf UploadedPdfResource\n";
}

// Logout
auth()->logout();

echo "\n=== Test abgeschlossen ===\n";