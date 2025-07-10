<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "=== Debug User Access ===\n\n";

// Benutzer dh@dhe.de finden
$user = User::where('email', 'dh@dhe.de')->first();

if (!$user) {
    echo "❌ Benutzer dh@dhe.de nicht gefunden!\n";
    exit;
}

echo "✓ Benutzer gefunden: {$user->name} ({$user->email})\n\n";

// Team-Zugehörigkeiten prüfen
$teams = $user->teams()->get();
echo "Team-Zugehörigkeiten:\n";
foreach ($teams as $team) {
    echo "  - {$team->name} (ID: {$team->id})\n";
}

if ($teams->isEmpty()) {
    echo "  ❌ Keine Team-Zugehörigkeiten gefunden!\n";
}

echo "\n";

// Zugriffsprüfungen simulieren
echo "Zugriffsprüfungen:\n";

// System-Navigation prüfen
$systemAccess = $user->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists();
echo "System-Navigation: " . ($systemAccess ? '✓ Zugriff' : '✗ Kein Zugriff') . "\n";

// PDF-Analyse-Navigation prüfen
$pdfAnalysisAccess = $user->teams()->whereIn('name', ['Superadmin'])->exists();
echo "PDF-Analyse-Navigation: " . ($pdfAnalysisAccess ? '✓ Zugriff' : '✗ Kein Zugriff') . "\n";

echo "\n";

// Detaillierte Team-Abfrage
echo "Detaillierte Team-Abfrage:\n";
$adminTeams = $user->teams()->whereIn('name', ['Administrator', 'Superadmin'])->pluck('name')->toArray();
echo "Administrator/Superadmin Teams: " . (empty($adminTeams) ? 'Keine' : implode(', ', $adminTeams)) . "\n";

$superadminTeams = $user->teams()->whereIn('name', ['Superadmin'])->pluck('name')->toArray();
echo "Superadmin Teams: " . (empty($superadminTeams) ? 'Keine' : implode(', ', $superadminTeams)) . "\n";

echo "\n";

// Benutzer authentifizieren und Ressourcen-Zugriff testen
auth()->login($user);

echo "Ressourcen-Zugriff (authentifiziert als {$user->email}):\n";

// System-Ressourcen testen
$systemResources = [
    'App\Filament\Resources\UserResource',
    'App\Filament\Resources\TeamResource',
];

foreach ($systemResources as $resourceClass) {
    if (class_exists($resourceClass)) {
        $canView = $resourceClass::canViewAny();
        $status = $canView ? '✓' : '✗';
        $resourceName = class_basename($resourceClass);
        echo "  {$status} {$resourceName}\n";
    }
}

// PDF-Analyse-Ressourcen testen
$pdfResources = [
    'App\Filament\Resources\UploadedPdfResource',
    'App\Filament\Resources\PdfExtractionRuleResource',
];

foreach ($pdfResources as $resourceClass) {
    if (class_exists($resourceClass)) {
        $canView = $resourceClass::canViewAny();
        $status = $canView ? '✓' : '✗';
        $resourceName = class_basename($resourceClass);
        echo "  {$status} {$resourceName}\n";
    }
}

auth()->logout();

echo "\n=== Debug abgeschlossen ===\n";