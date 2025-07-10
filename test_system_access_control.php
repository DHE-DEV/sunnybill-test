<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== System-Zugriffskontrolle Test ===\n\n";

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

echo "✓ Test-Benutzer erstellt\n";
echo "  - Superadmin: {$superadminUser->email}\n";
echo "  - Administrator: {$adminUser->email}\n";
echo "  - Normal User: {$normalUser->email}\n\n";

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

// PDF-Analyse-System Ressourcen (nur Superadmin)
$pdfAnalysisResources = [
    'App\Filament\Resources\UploadedPdfResource',
    'App\Filament\Resources\PdfExtractionRuleResource',
    'App\Filament\Resources\ContractMatchingRuleResource',
];

echo "2. System-Ressourcen Zugriffskontrolle testen...\n";

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

echo "\n3. Detaillierte Berechtigungen testen...\n";

// Detaillierte Berechtigungen für Superadmin testen
auth()->login($superadminUser);
echo "\n--- Detaillierte Superadmin-Berechtigungen ---\n";

foreach ($systemResources as $resourceClass) {
    if (class_exists($resourceClass)) {
        $resourceName = class_basename($resourceClass);
        echo "{$resourceName}:\n";
        
        $permissions = [
            'canViewAny' => $resourceClass::canViewAny(),
            'canCreate' => method_exists($resourceClass, 'canCreate') ? $resourceClass::canCreate() : true,
            'canEdit' => method_exists($resourceClass, 'canEdit') ? $resourceClass::canEdit(null) : true,
            'canDelete' => method_exists($resourceClass, 'canDelete') ? $resourceClass::canDelete(null) : true,
            'canDeleteAny' => method_exists($resourceClass, 'canDeleteAny') ? $resourceClass::canDeleteAny() : true,
        ];
        
        foreach ($permissions as $permission => $hasPermission) {
            $status = $hasPermission ? '✓' : '✗';
            echo "  {$status} {$permission}\n";
        }
        echo "\n";
    }
}

echo "\n4. Team-Zugehörigkeit prüfen...\n";

foreach ($testUsers as $roleName => $user) {
    $teams = $user->teams()->pluck('name')->toArray();
    echo "{$roleName}: " . implode(', ', $teams) . "\n";
}

echo "\n5. Navigation-Sichtbarkeit simulieren...\n";

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

// Logout
auth()->logout();

echo "\n=== Test abgeschlossen ===\n";
echo "Alle System-Ressourcen wurden auf rollenbasierte Zugriffskontrolle getestet.\n";
echo "- System-Menü: Administrator + Superadmin Teams\n";
echo "- PDF-Analyse-System: Nur Superadmin Team\n";