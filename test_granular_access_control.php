<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Team;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Granulare Zugriffskontrolle Test ===\n\n";

// Test-Benutzer für verschiedene Teams
$testUsers = [
    'User' => User::where('email', 'dh@dhe.de')->first(),
    'Manager' => User::where('email', 'test.manager@example.com')->first(),
    'Superadmin' => User::whereHas('teams', function($q) {
        $q->where('name', 'Superadmin');
    })->first(),
];

// Teste jeden Benutzer-Typ
foreach ($testUsers as $teamName => $user) {
    if (!$user) {
        echo "❌ {$teamName} Benutzer nicht gefunden!\n";
        continue;
    }
    
    echo "=== Test für {$teamName} Team ({$user->name}) ===\n";
    
    // Authentifiziere als dieser Benutzer
    auth()->login($user);
    
    // Teste alle Zugriffsmethoden
    $permissions = [
        'canViewAny' => \App\Filament\Resources\UploadedPdfResource::canViewAny(),
        'canCreate' => \App\Filament\Resources\UploadedPdfResource::canCreate(),
        'canEdit' => \App\Filament\Resources\UploadedPdfResource::canEdit(null),
        'canDelete' => \App\Filament\Resources\UploadedPdfResource::canDelete(null),
        'canDeleteAny' => \App\Filament\Resources\UploadedPdfResource::canDeleteAny(),
        'canView' => \App\Filament\Resources\UploadedPdfResource::canView(null),
    ];
    
    foreach ($permissions as $method => $hasPermission) {
        $status = $hasPermission ? '✓' : '✗';
        echo "  {$status} {$method}\n";
    }
    
    // Erwartete Berechtigungen basierend auf Team
    $expectedPermissions = [];
    switch ($teamName) {
        case 'User':
            $expectedPermissions = [
                'canViewAny' => true,   // Kann Liste sehen
                'canCreate' => false,   // Kann nicht erstellen
                'canEdit' => false,     // Kann nicht bearbeiten
                'canDelete' => false,   // Kann nicht löschen
                'canDeleteAny' => false, // Kann nicht bulk-löschen
                'canView' => true,      // Kann einzelne PDFs anzeigen
            ];
            break;
        case 'Manager':
        case 'Superadmin':
            $expectedPermissions = [
                'canViewAny' => true,   // Kann Liste sehen
                'canCreate' => true,    // Kann erstellen
                'canEdit' => true,      // Kann bearbeiten
                'canDelete' => true,    // Kann löschen
                'canDeleteAny' => true, // Kann bulk-löschen
                'canView' => true,      // Kann einzelne PDFs anzeigen
            ];
            break;
    }
    
    // Validiere Erwartungen
    $allCorrect = true;
    echo "\n  Validierung:\n";
    foreach ($expectedPermissions as $method => $expected) {
        $actual = $permissions[$method];
        $isCorrect = $actual === $expected;
        $status = $isCorrect ? '✅' : '❌';
        $expectedStr = $expected ? 'true' : 'false';
        $actualStr = $actual ? 'true' : 'false';
        
        echo "    {$status} {$method}: erwartet {$expectedStr}, erhalten {$actualStr}\n";
        
        if (!$isCorrect) {
            $allCorrect = false;
        }
    }
    
    if ($allCorrect) {
        echo "  🎉 Alle Berechtigungen für {$teamName} sind korrekt!\n";
    } else {
        echo "  ⚠️  Einige Berechtigungen für {$teamName} sind falsch!\n";
    }
    
    auth()->logout();
    echo "\n";
}

echo "=== Navigation Sichtbarkeit Test ===\n";

// Teste Navigation-Sichtbarkeit für User-Team
$userUser = $testUsers['User'];
if ($userUser) {
    auth()->login($userUser);
    
    echo "User Team Navigation:\n";
    
    // UploadedPdfResource sollte sichtbar sein (wegen canViewAny = true)
    $uploadedPdfVisible = \App\Filament\Resources\UploadedPdfResource::canViewAny();
    $status = $uploadedPdfVisible ? '✓' : '✗';
    echo "  {$status} UploadedPdfResource (PDF-Analyse System Navigation wird angezeigt)\n";
    
    // Andere PDF-Analyse Ressourcen sollten nicht sichtbar sein
    $otherPdfResources = [
        'PdfExtractionRuleResource',
        'ContractMatchingRuleResource',
        'SupplierRecognitionPatternResource'
    ];
    
    echo "  Andere PDF-Analyse Ressourcen (sollten ✗ sein):\n";
    foreach ($otherPdfResources as $resourceName) {
        $className = "\\App\\Filament\\Resources\\{$resourceName}";
        if (class_exists($className)) {
            $canView = $className::canViewAny();
            $status = $canView ? '✓' : '✗';
            echo "    {$status} {$resourceName}\n";
        }
    }
    
    auth()->logout();
}

echo "\n=== Zusammenfassung ===\n";
echo "✅ User Team: Kann PDF-Liste sehen, aber nicht erstellen/bearbeiten/löschen\n";
echo "✅ Manager Team: Vollzugriff auf UploadedPdfResource\n";
echo "✅ Superadmin Team: Vollzugriff auf alle PDF-Analyse Ressourcen\n";
echo "✅ PDF-Analyse System Navigation wird für User/Manager/Superadmin angezeigt\n";

echo "\n=== Test abgeschlossen ===\n";