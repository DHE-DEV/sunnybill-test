<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Team;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Vollständiger Zugriffskontrolle Test ===\n\n";

// Test-Benutzer erstellen
echo "1. Test-Benutzer und Teams erstellen...\n";

// Teams erstellen falls nicht vorhanden
$superadminTeam = Team::firstOrCreate(['name' => 'Superadmin']);
$adminTeam = Team::firstOrCreate(['name' => 'Administrator']);
$userTeam = Team::firstOrCreate(['name' => 'User']);

// Test-Benutzer erstellen
$superadminUser = User::firstOrCreate(
    ['email' => 'superadmin@test.com'],
    ['name' => 'Super Admin', 'password' => bcrypt('password')]
);

$adminUser = User::firstOrCreate(
    ['email' => 'admin@test.com'],
    ['name' => 'Administrator', 'password' => bcrypt('password')]
);

$normalUser = User::firstOrCreate(
    ['email' => 'user@test.com'],
    ['name' => 'Normal User', 'password' => bcrypt('password')]
);

// Team-Zuweisungen
$superadminUser->teams()->sync([$superadminTeam->id]);
$adminUser->teams()->sync([$adminTeam->id]);
$normalUser->teams()->sync([$userTeam->id]);

echo "✓ Test-Benutzer erstellt\n\n";

// System-Ressourcen definieren
$systemResources = [
    'App\Filament\Resources\UserResource',
    'App\Filament\Resources\TeamResource',
    'App\Filament\Resources\TaxRateResource',
    'App\Filament\Resources\TaskTypeResource',
    'App\Filament\Resources\SupplierTypeResource',
    'App\Filament\Resources\SolarPlantStatusResource',
    'App\Filament\Resources\LexofficeLogResource',
    'App\Filament\Resources\DocumentPathSettingResource',
    'App\Filament\Resources\CompanySettingResource',
    'App\Filament\Resources\ArticleVersionResource',
    'App\Filament\Resources\ArticleResource',
];

// System-Pages definieren
$systemPages = [
    'App\Filament\Pages\TestDataManager',
    'App\Filament\Pages\UserManagement',
    'App\Filament\Pages\StorageSettings',
    'App\Filament\Pages\DebugAllocations',
];

// PDF-Analyse-System Ressourcen (nur Superadmin)
$pdfAnalysisResources = [
    'App\Filament\Resources\UploadedPdfResource',
    'App\Filament\Resources\PdfExtractionRuleResource',
    'App\Filament\Resources\ContractMatchingRuleResource',
];

echo "2. Vollständige Zugriffskontrolle testen...\n";

// Test für jede Benutzerrolle
$testUsers = [
    'Superadmin' => $superadminUser,
    'Administrator' => $adminUser,
    'Normal User' => $normalUser,
];

foreach ($testUsers as $roleName => $user) {
    echo "\n--- Testing {$roleName} ({$user->email}) ---\n";
    
    // Benutzer authentifizieren
    auth()->login($user);
    
    // System-Ressourcen testen
    echo "System-Ressourcen:\n";
    foreach ($systemResources as $resourceClass) {
        if (class_exists($resourceClass)) {
            $canView = $resourceClass::canViewAny();
            $status = $canView ? '✓' : '✗';
            $resourceName = class_basename($resourceClass);
            echo "  {$status} {$resourceName}\n";
            
            // Erwartete Berechtigung prüfen
            $shouldHaveAccess = in_array($roleName, ['Superadmin', 'Administrator']);
            if ($canView !== $shouldHaveAccess) {
                echo "    ⚠️  FEHLER: Erwartete Berechtigung: " . ($shouldHaveAccess ? 'JA' : 'NEIN') . ", Tatsächlich: " . ($canView ? 'JA' : 'NEIN') . "\n";
            }
        }
    }
    
    // System-Pages testen
    echo "System-Pages:\n";
    foreach ($systemPages as $pageClass) {
        if (class_exists($pageClass)) {
            $canAccess = $pageClass::canAccess();
            $status = $canAccess ? '✓' : '✗';
            $pageName = class_basename($pageClass);
            echo "  {$status} {$pageName}\n";
            
            // Erwartete Berechtigung prüfen
            $shouldHaveAccess = in_array($roleName, ['Superadmin', 'Administrator']);
            if ($canAccess !== $shouldHaveAccess) {
                echo "    ⚠️  FEHLER: Erwartete Berechtigung: " . ($shouldHaveAccess ? 'JA' : 'NEIN') . ", Tatsächlich: " . ($canAccess ? 'JA' : 'NEIN') . "\n";
            }
        }
    }
    
    // PDF-Analyse-System Ressourcen testen
    echo "PDF-Analyse-System:\n";
    foreach ($pdfAnalysisResources as $resourceClass) {
        if (class_exists($resourceClass)) {
            $canView = $resourceClass::canViewAny();
            $status = $canView ? '✓' : '✗';
            $resourceName = class_basename($resourceClass);
            echo "  {$status} {$resourceName}\n";
            
            // Erwartete Berechtigung prüfen (nur Superadmin)
            $shouldHaveAccess = ($roleName === 'Superadmin');
            if ($canView !== $shouldHaveAccess) {
                echo "    ⚠️  FEHLER: Erwartete Berechtigung: " . ($shouldHaveAccess ? 'JA' : 'NEIN') . ", Tatsächlich: " . ($canView ? 'JA' : 'NEIN') . "\n";
            }
        }
    }
}

echo "\n3. Navigation-Sichtbarkeit simulieren...\n";

foreach ($testUsers as $roleName => $user) {
    auth()->login($user);
    
    // System-Navigation sichtbar?
    $systemNavVisible = $user->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists();
    
    // PDF-Analyse-Navigation sichtbar?
    $pdfAnalysisNavVisible = $user->teams()->whereIn('name', ['Superadmin'])->exists();
    
    echo "{$roleName}:\n";
    echo "  System-Navigation: " . ($systemNavVisible ? '✓ Sichtbar' : '✗ Versteckt') . "\n";
    echo "  PDF-Analyse-Navigation: " . ($pdfAnalysisNavVisible ? '✓ Sichtbar' : '✗ Versteckt') . "\n";
}

echo "\n4. Realer Benutzer Test (dh@dhe.de)...\n";

$realUser = User::where('email', 'dh@dhe.de')->first();
if ($realUser) {
    auth()->login($realUser);
    
    $teams = $realUser->teams()->pluck('name')->toArray();
    echo "Benutzer: {$realUser->name} ({$realUser->email})\n";
    echo "Teams: " . implode(', ', $teams) . "\n";
    
    // System-Zugriff testen
    $systemAccess = $realUser->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists();
    echo "System-Zugriff: " . ($systemAccess ? '✓ JA' : '✗ NEIN') . "\n";
    
    // PDF-Analyse-Zugriff testen
    $pdfAccess = $realUser->teams()->whereIn('name', ['Superadmin'])->exists();
    echo "PDF-Analyse-Zugriff: " . ($pdfAccess ? '✓ JA' : '✗ NEIN') . "\n";
    
    // Beispiel-Ressource testen
    if (class_exists('App\Filament\Resources\UserResource')) {
        $canViewUsers = \App\Filament\Resources\UserResource::canViewAny();
        echo "UserResource Zugriff: " . ($canViewUsers ? '✓ JA' : '✗ NEIN') . "\n";
    }
    
    // Beispiel-Page testen
    if (class_exists('App\Filament\Pages\TestDataManager')) {
        $canAccessTestData = \App\Filament\Pages\TestDataManager::canAccess();
        echo "TestDataManager Zugriff: " . ($canAccessTestData ? '✓ JA' : '✗ NEIN') . "\n";
    }
} else {
    echo "❌ Benutzer dh@dhe.de nicht gefunden!\n";
}

// Logout
auth()->logout();

echo "\n=== Test abgeschlossen ===\n";
echo "Alle System-Ressourcen und Pages wurden auf rollenbasierte Zugriffskontrolle getestet.\n";
echo "- System-Menü (Ressourcen + Pages): Administrator + Superadmin Teams\n";
echo "- PDF-Analyse-System: Nur Superadmin Team\n";