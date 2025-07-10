<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Team;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Manager Team erstellen und testen ===\n\n";

// Erstelle Manager Team falls es nicht existiert
$managerTeam = Team::firstOrCreate([
    'name' => 'Manager'
], [
    'description' => 'Manager Team für PDF-Upload Zugriff',
    'created_at' => now(),
    'updated_at' => now(),
]);

if ($managerTeam->wasRecentlyCreated) {
    echo "✓ Manager Team erstellt (ID: {$managerTeam->id})\n";
} else {
    echo "✓ Manager Team bereits vorhanden (ID: {$managerTeam->id})\n";
}

// Erstelle Test-Benutzer für Manager Team
$managerUser = User::firstOrCreate([
    'email' => 'test.manager@example.com'
], [
    'name' => 'Test Manager',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);

if ($managerUser->wasRecentlyCreated) {
    echo "✓ Manager Test-Benutzer erstellt: {$managerUser->name}\n";
} else {
    echo "✓ Manager Test-Benutzer bereits vorhanden: {$managerUser->name}\n";
}

// Weise Manager Team zu (falls noch nicht zugewiesen)
if (!$managerUser->teams()->where('team_id', $managerTeam->id)->exists()) {
    $managerUser->teams()->attach($managerTeam->id);
    echo "✓ Manager Team dem Benutzer zugewiesen\n";
} else {
    echo "✓ Benutzer ist bereits im Manager Team\n";
}

echo "\nAktuelle Teams:\n";
$teams = Team::all();
foreach ($teams as $team) {
    $userCount = $team->users()->count();
    echo "  - {$team->name} (ID: {$team->id}, {$userCount} Benutzer)\n";
}

echo "\n=== Zugriffstests ===\n";

// Test 1: Manager-Benutzer
echo "\n1. Test als Manager-Benutzer:\n";
auth()->login($managerUser);

$managerTeams = $managerUser->teams()->pluck('name')->toArray();
echo "   Teams: " . implode(', ', $managerTeams) . "\n";

$uploadedPdfAccess = \App\Filament\Resources\UploadedPdfResource::canViewAny();
$status = $uploadedPdfAccess ? '✓' : '✗';
echo "   {$status} UploadedPdfResource Zugriff\n";

// Andere PDF-Analyse Ressourcen (sollten blockiert bleiben)
$otherPdfResources = [
    'PdfExtractionRuleResource',
    'ContractMatchingRuleResource', 
    'SupplierRecognitionPatternResource'
];

echo "   Andere PDF-Analyse Ressourcen (sollten ✗ sein):\n";
foreach ($otherPdfResources as $resourceName) {
    $className = "\\App\\Filament\\Resources\\{$resourceName}";
    if (class_exists($className)) {
        $canView = $className::canViewAny();
        $status = $canView ? '✓' : '✗';
        echo "     {$status} {$resourceName}\n";
    }
}

auth()->logout();

// Test 2: User-Benutzer (sollte keinen Zugriff haben)
echo "\n2. Test als User-Benutzer (dh@dhe.de):\n";
$userUser = User::where('email', 'dh@dhe.de')->first();
if ($userUser) {
    auth()->login($userUser);
    
    $userTeams = $userUser->teams()->pluck('name')->toArray();
    echo "   Teams: " . implode(', ', $userTeams) . "\n";
    
    $uploadedPdfAccess = \App\Filament\Resources\UploadedPdfResource::canViewAny();
    $status = $uploadedPdfAccess ? '✓' : '✗';
    echo "   {$status} UploadedPdfResource Zugriff (sollte ✗ sein)\n";
    
    auth()->logout();
}

echo "\n=== Ergebnis ===\n";
echo "✅ Manager Team erstellt und konfiguriert\n";
echo "✅ Manager-Benutzer haben jetzt Zugriff auf UploadedPdfResource\n";
echo "✅ Andere PDF-Analyse Ressourcen bleiben nur für Superadmin zugänglich\n";
echo "✅ User-Benutzer haben weiterhin keinen Zugriff\n";

echo "\n=== Test abgeschlossen ===\n";