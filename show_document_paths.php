<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DocumentPathSetting;

echo "\n=== Konfigurierte DocumentPathSettings ===\n\n";

$settings = DocumentPathSetting::all();

if ($settings->isEmpty()) {
    echo "Keine DocumentPathSettings gefunden.\n";
} else {
    foreach ($settings as $setting) {
        $type = class_basename($setting->documentable_type);
        echo "Typ: {$type}\n";
        echo "Pfad-Template: {$setting->path_template}\n";
        echo "Aktiv: " . ($setting->is_active ? 'Ja' : 'Nein') . "\n";
        echo "Erstellt: {$setting->created_at}\n";
        echo "---\n";
    }
    
    echo "\nGesamt: {$settings->count()} Einstellungen\n";
}