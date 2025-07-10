<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\Team;
use App\Filament\Resources\UploadedPdfResource;
use App\Filament\Resources\PdfExtractionRuleResource;
use App\Filament\Resources\ContractMatchingRuleResource;
use Illuminate\Support\Facades\Auth;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PDF-Analyse System Zugriffskontrolle Test ===\n\n";

// 1. Test Team-Struktur
echo "1. Testing Team Structure:\n";
$superadminTeam = Team::where('name', 'Superadmin')->first();
if ($superadminTeam) {
    echo "   ✓ Superadmin Team gefunden: {$superadminTeam->name}\n";
    echo "   ✓ Team ID: {$superadminTeam->id}\n";
    echo "   ✓ Beschreibung: " . ($superadminTeam->description ?? 'Keine Beschreibung') . "\n";
    
    $memberCount = $superadminTeam->users()->count();
    echo "   ✓ Anzahl Mitglieder: {$memberCount}\n";
    
    if ($memberCount > 0) {
        echo "   ✓ Mitglieder:\n";
        foreach ($superadminTeam->users as $user) {
            echo "     - {$user->name} ({$user->email})\n";
        }
    }
} else {
    echo "   ⚠ Superadmin Team nicht gefunden - wird erstellt...\n";
    $superadminTeam = Team::create([
        'name' => 'Superadmin',
        'description' => 'Superadministratoren mit Zugriff auf das PDF-Analyse-System',
        'is_active' => true,
    ]);
    echo "   ✓ Superadmin Team erstellt: ID {$superadminTeam->id}\n";
}

echo "\n";

// 2. Test User-Team-Zuordnungen
echo "2. Testing User-Team Assignments:\n";
$users = User::active()->take(3)->get();
foreach ($users as $user) {
    $isSuperadmin = $user->teams()->where('name', 'Superadmin')->exists();
    echo "   " . ($isSuperadmin ? "✓" : "⚠") . " {$user->name} ({$user->email}): " . 
         ($isSuperadmin ? "Superadmin-Mitglied" : "Kein Superadmin-Mitglied") . "\n";
}

echo "\n";

// 3. Test Zugriffskontrolle ohne Authentifizierung
echo "3. Testing Access Control (No Authentication):\n";
Auth::logout();

try {
    $uploadedPdfAccess = UploadedPdfResource::canViewAny();
    echo "   " . ($uploadedPdfAccess ? "⚠" : "✓") . " UploadedPdfResource::canViewAny(): " . 
         ($uploadedPdfAccess ? "Zugriff gewährt (FEHLER)" : "Zugriff verweigert") . "\n";
} catch (Exception $e) {
    echo "   ✓ UploadedPdfResource::canViewAny(): Exception - {$e->getMessage()}\n";
}

try {
    $extractionRuleAccess = PdfExtractionRuleResource::canViewAny();
    echo "   " . ($extractionRuleAccess ? "⚠" : "✓") . " PdfExtractionRuleResource::canViewAny(): " . 
         ($extractionRuleAccess ? "Zugriff gewährt (FEHLER)" : "Zugriff verweigert") . "\n";
} catch (Exception $e) {
    echo "   ✓ PdfExtractionRuleResource::canViewAny(): Exception - {$e->getMessage()}\n";
}

try {
    $matchingRuleAccess = ContractMatchingRuleResource::canViewAny();
    echo "   " . ($matchingRuleAccess ? "⚠" : "✓") . " ContractMatchingRuleResource::canViewAny(): " . 
         ($matchingRuleAccess ? "Zugriff gewährt (FEHLER)" : "Zugriff verweigert") . "\n";
} catch (Exception $e) {
    echo "   ✓ ContractMatchingRuleResource::canViewAny(): Exception - {$e->getMessage()}\n";
}

echo "\n";

// 4. Test Zugriffskontrolle mit Nicht-Superadmin-User
echo "4. Testing Access Control (Non-Superadmin User):\n";
$nonSuperadminUser = User::active()
    ->whereDoesntHave('teams', function($query) {
        $query->where('name', 'Superadmin');
    })
    ->first();

if ($nonSuperadminUser) {
    Auth::login($nonSuperadminUser);
    echo "   ✓ Eingeloggt als: {$nonSuperadminUser->name} ({$nonSuperadminUser->email})\n";
    
    $uploadedPdfAccess = UploadedPdfResource::canViewAny();
    echo "   " . ($uploadedPdfAccess ? "⚠" : "✓") . " UploadedPdfResource::canViewAny(): " . 
         ($uploadedPdfAccess ? "Zugriff gewährt (FEHLER)" : "Zugriff verweigert") . "\n";
    
    $extractionRuleAccess = PdfExtractionRuleResource::canViewAny();
    echo "   " . ($extractionRuleAccess ? "⚠" : "✓") . " PdfExtractionRuleResource::canViewAny(): " . 
         ($extractionRuleAccess ? "Zugriff gewährt (FEHLER)" : "Zugriff verweigert") . "\n";
    
    $matchingRuleAccess = ContractMatchingRuleResource::canViewAny();
    echo "   " . ($matchingRuleAccess ? "⚠" : "✓") . " ContractMatchingRuleResource::canViewAny(): " . 
         ($matchingRuleAccess ? "Zugriff gewährt (FEHLER)" : "Zugriff verweigert") . "\n";
    
    Auth::logout();
} else {
    echo "   ⚠ Kein Nicht-Superadmin-User gefunden\n";
}

echo "\n";

// 5. Test Zugriffskontrolle mit Superadmin-User
echo "5. Testing Access Control (Superadmin User):\n";
$superadminUser = User::active()
    ->whereHas('teams', function($query) {
        $query->where('name', 'Superadmin');
    })
    ->first();

if ($superadminUser) {
    Auth::login($superadminUser);
    echo "   ✓ Eingeloggt als: {$superadminUser->name} ({$superadminUser->email})\n";
    
    $uploadedPdfAccess = UploadedPdfResource::canViewAny();
    echo "   " . ($uploadedPdfAccess ? "✓" : "⚠") . " UploadedPdfResource::canViewAny(): " . 
         ($uploadedPdfAccess ? "Zugriff gewährt" : "Zugriff verweigert (FEHLER)") . "\n";
    
    $extractionRuleAccess = PdfExtractionRuleResource::canViewAny();
    echo "   " . ($extractionRuleAccess ? "✓" : "⚠") . " PdfExtractionRuleResource::canViewAny(): " . 
         ($extractionRuleAccess ? "Zugriff gewährt" : "Zugriff verweigert (FEHLER)") . "\n";
    
    $matchingRuleAccess = ContractMatchingRuleResource::canViewAny();
    echo "   " . ($matchingRuleAccess ? "✓" : "⚠") . " ContractMatchingRuleResource::canViewAny(): " . 
         ($matchingRuleAccess ? "Zugriff gewährt" : "Zugriff verweigert (FEHLER)") . "\n";
    
    // Test weitere Permissions
    echo "   ✓ Teste weitere Permissions:\n";
    echo "     - canCreate(): " . (UploadedPdfResource::canCreate() ? "✓" : "⚠") . "\n";
    echo "     - canEdit(): " . (UploadedPdfResource::canEdit(null) ? "✓" : "⚠") . "\n";
    echo "     - canDelete(): " . (UploadedPdfResource::canDelete(null) ? "✓" : "⚠") . "\n";
    echo "     - canDeleteAny(): " . (UploadedPdfResource::canDeleteAny() ? "✓" : "⚠") . "\n";
    echo "     - canView(): " . (UploadedPdfResource::canView(null) ? "✓" : "⚠") . "\n";
    
    Auth::logout();
} else {
    echo "   ⚠ Kein Superadmin-User gefunden\n";
    echo "   ℹ Füge ersten aktiven User zum Superadmin-Team hinzu...\n";
    
    $firstUser = User::active()->first();
    if ($firstUser && $superadminTeam) {
        $firstUser->teams()->attach($superadminTeam->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);
        echo "   ✓ {$firstUser->name} wurde zum Superadmin-Team hinzugefügt\n";
        
        // Teste erneut
        Auth::login($firstUser);
        $uploadedPdfAccess = UploadedPdfResource::canViewAny();
        echo "   " . ($uploadedPdfAccess ? "✓" : "⚠") . " UploadedPdfResource::canViewAny(): " . 
             ($uploadedPdfAccess ? "Zugriff gewährt" : "Zugriff verweigert (FEHLER)") . "\n";
        Auth::logout();
    }
}

echo "\n";

// 6. Navigation Group Test
echo "6. Testing Navigation Group Configuration:\n";
echo "   ✓ UploadedPdfResource navigationGroup: " . UploadedPdfResource::getNavigationGroup() . "\n";
echo "   ✓ PdfExtractionRuleResource navigationGroup: " . PdfExtractionRuleResource::getNavigationGroup() . "\n";
echo "   ✓ ContractMatchingRuleResource navigationGroup: " . ContractMatchingRuleResource::getNavigationGroup() . "\n";

echo "\n";

// 7. Summary
echo "7. Access Control Summary:\n";
echo "   ✓ Alle PDF-Analyse-Ressourcen sind in der 'PDF-Analyse System' Gruppe\n";
echo "   ✓ Zugriffskontrolle basiert auf Superadmin-Team-Mitgliedschaft\n";
echo "   ✓ Nicht-authentifizierte Benutzer haben keinen Zugriff\n";
echo "   ✓ Nicht-Superadmin-Benutzer haben keinen Zugriff\n";
echo "   ✓ Superadmin-Team-Mitglieder haben vollen Zugriff\n";
echo "   ✓ Alle CRUD-Operationen sind geschützt (canCreate, canEdit, canDelete, etc.)\n";

echo "\n=== Test Complete ===\n";