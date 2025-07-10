<?php

require_once 'vendor/autoload.php';

use App\Models\User;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Finaler Zugriffskontrolle Test ===\n\n";

// Realer Benutzer Test (dh@dhe.de)
$realUser = User::where('email', 'dh@dhe.de')->first();
if ($realUser) {
    auth()->login($realUser);
    
    $teams = $realUser->teams()->pluck('name')->toArray();
    echo "Benutzer: {$realUser->name} ({$realUser->email})\n";
    echo "Teams: " . implode(', ', $teams) . "\n\n";
    
    // Alle PDF-Analyse-System Ressourcen testen
    $pdfAnalysisResources = [
        'App\Filament\Resources\UploadedPdfResource',
        'App\Filament\Resources\PdfExtractionRuleResource',
        'App\Filament\Resources\ContractMatchingRuleResource',
        'App\Filament\Resources\SupplierRecognitionPatternResource',
    ];
    
    echo "PDF-Analyse-System Ressourcen:\n";
    $anyPdfAccess = false;
    foreach ($pdfAnalysisResources as $resourceClass) {
        if (class_exists($resourceClass)) {
            $canView = $resourceClass::canViewAny();
            $status = $canView ? '✓' : '✗';
            $resourceName = class_basename($resourceClass);
            echo "  {$status} {$resourceName}\n";
            
            if ($canView) {
                $anyPdfAccess = true;
            }
        }
    }
    
    echo "\nErgebnis PDF-Analyse-System Navigation:\n";
    if ($anyPdfAccess) {
        echo "❌ FEHLER: PDF-Analyse-System Navigation wird angezeigt (mindestens eine Ressource ist zugänglich)\n";
    } else {
        echo "✅ KORREKT: PDF-Analyse-System Navigation wird versteckt (keine Ressource zugänglich)\n";
    }
    
    // System-Ressourcen testen
    $systemResources = [
        'App\Filament\Resources\UserResource',
        'App\Filament\Resources\TeamResource',
    ];
    
    echo "\nSystem-Ressourcen (Beispiel):\n";
    $anySystemAccess = false;
    foreach ($systemResources as $resourceClass) {
        if (class_exists($resourceClass)) {
            $canView = $resourceClass::canViewAny();
            $status = $canView ? '✓' : '✗';
            $resourceName = class_basename($resourceClass);
            echo "  {$status} {$resourceName}\n";
            
            if ($canView) {
                $anySystemAccess = true;
            }
        }
    }
    
    // System-Pages testen
    $systemPages = [
        'App\Filament\Pages\TestDataManager',
        'App\Filament\Pages\UserManagement',
    ];
    
    echo "\nSystem-Pages (Beispiel):\n";
    $anyPageAccess = false;
    foreach ($systemPages as $pageClass) {
        if (class_exists($pageClass)) {
            $canAccess = $pageClass::canAccess();
            $status = $canAccess ? '✓' : '✗';
            $pageName = class_basename($pageClass);
            echo "  {$status} {$pageName}\n";
            
            if ($canAccess) {
                $anyPageAccess = true;
            }
        }
    }
    
    echo "\nErgebnis System Navigation:\n";
    if ($anySystemAccess || $anyPageAccess) {
        echo "❌ FEHLER: System Navigation wird angezeigt (mindestens eine Ressource/Page ist zugänglich)\n";
    } else {
        echo "✅ KORREKT: System Navigation wird versteckt (keine Ressource/Page zugänglich)\n";
    }
    
} else {
    echo "❌ Benutzer dh@dhe.de nicht gefunden!\n";
}

// Logout
auth()->logout();

echo "\n=== Finaler Test abgeschlossen ===\n";
echo "Wenn beide Navigation Groups als 'KORREKT' markiert sind, ist die Zugriffskontrolle vollständig implementiert.\n";