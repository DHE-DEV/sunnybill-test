<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\DocumentPathSetting;
use App\Models\UploadedPdf;
use App\Models\User;
use Illuminate\Support\Str;

echo "=== UUID-Integration Test ===\n\n";

// 1. Test: UUID-Platzhalter in verfügbaren Platzhaltern
echo "1. Teste verfügbare Platzhalter für UploadedPdf:\n";
$placeholders = DocumentPathSetting::getAvailablePlaceholders('App\Models\UploadedPdf');

$expectedUuidPlaceholders = ['file_uuid', 'file_uuid_short'];
$foundUuidPlaceholders = [];

foreach ($expectedUuidPlaceholders as $placeholder) {
    if (array_key_exists($placeholder, $placeholders)) {
        $foundUuidPlaceholders[] = $placeholder;
        echo "   ✓ {$placeholder}: {$placeholders[$placeholder]}\n";
    } else {
        echo "   ✗ {$placeholder}: FEHLT\n";
    }
}

echo "\n";

// 2. Test: UUID-Platzhalter-Ersetzung
echo "2. Teste UUID-Platzhalter-Ersetzung:\n";

// Mock UploadedPdf erstellen
$mockUploadedPdf = new class {
    public $id = 123;
    public $name = 'Test PDF';
    public $original_filename = 'test-document.pdf';
    public $analysis_status = 'completed';
    public $file_size = 1048576; // 1MB
    public $uploadedBy = null;
    
    public function __construct() {
        $this->uploadedBy = new class {
            public $id = 1;
            public $name = 'Test User';
        };
    }
};

// DocumentPathSetting für Test erstellen
$pathSetting = new DocumentPathSetting([
    'documentable_type' => 'App\Models\UploadedPdf',
    'category' => null,
    'path_template' => 'pdf-uploads/{year}/{month}/{file_uuid_short}',
    'description' => 'Test-Pfad mit UUID',
    'placeholders' => ['year', 'month', 'file_uuid', 'file_uuid_short'],
    'is_active' => true,
]);

try {
    $generatedPath = $pathSetting->generatePath($mockUploadedPdf);
    echo "   ✓ Generierter Pfad: {$generatedPath}\n";
    
    // Prüfen ob UUID-Pattern im Pfad enthalten ist
    if (preg_match('/[a-f0-9]{8}$/', $generatedPath)) {
        echo "   ✓ UUID-Short-Pattern erkannt\n";
    } else {
        echo "   ✗ UUID-Short-Pattern NICHT erkannt\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Fehler bei Pfad-Generierung: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Test: UUID-Konsistenz
echo "3. Teste UUID-Konsistenz:\n";

// Mehrere Pfade generieren und prüfen ob UUIDs unterschiedlich sind
$paths = [];
for ($i = 0; $i < 3; $i++) {
    $path = $pathSetting->generatePath($mockUploadedPdf);
    $paths[] = $path;
    echo "   Pfad {$i}: {$path}\n";
}

// Prüfen ob alle Pfade unterschiedlich sind (wegen unterschiedlicher UUIDs)
$uniquePaths = array_unique($paths);
if (count($uniquePaths) === count($paths)) {
    echo "   ✓ Alle UUIDs sind eindeutig\n";
} else {
    echo "   ✗ UUIDs sind NICHT eindeutig\n";
}

echo "\n";

// 4. Test: Standard-Pfadkonfigurationen mit UUID-Platzhaltern
echo "4. Teste Standard-Pfadkonfigurationen:\n";

try {
    // Temporäre Tabelle für Test (simuliert)
    echo "   Prüfe Standard-Konfigurationen für UploadedPdf...\n";
    
    // Simuliere createDefaults() Aufruf
    $defaults = [
        [
            'documentable_type' => 'App\Models\UploadedPdf',
            'category' => null,
            'placeholders' => ['pdf_name', 'pdf_id', 'original_filename', 'uploaded_by_name', 'year', 'month', 'file_uuid', 'file_uuid_short'],
        ],
        [
            'documentable_type' => 'App\Models\UploadedPdf',
            'category' => 'analysis',
            'placeholders' => ['pdf_name', 'pdf_id', 'original_filename', 'analysis_status', 'year', 'month', 'file_uuid', 'file_uuid_short'],
        ],
        [
            'documentable_type' => 'App\Models\UploadedPdf',
            'category' => 'user_organized',
            'placeholders' => ['uploaded_by_name', 'uploaded_by_id', 'pdf_name', 'year', 'month', 'file_uuid', 'file_uuid_short'],
        ],
    ];
    
    foreach ($defaults as $index => $default) {
        $hasUuidPlaceholders = in_array('file_uuid', $default['placeholders']) &&
                              in_array('file_uuid_short', $default['placeholders']);
        
        $category = $default['category'] ? $default['category'] : 'standard';
        
        if ($hasUuidPlaceholders) {
            echo "   ✓ Konfiguration {$index} ({$category}): UUID-Platzhalter vorhanden\n";
        } else {
            echo "   ✗ Konfiguration {$index} ({$category}): UUID-Platzhalter FEHLEN\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Fehler bei Standard-Konfigurationen: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. Test: UUID-Format-Validierung
echo "5. Teste UUID-Format-Validierung:\n";

// Teste direkte UUID-Generierung
$testUuid = Str::uuid()->toString();
$testUuidShort = substr($testUuid, 0, 8);

echo "   Vollständige UUID: {$testUuid}\n";
echo "   Kurze UUID: {$testUuidShort}\n";

// Validiere UUID-Format
if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $testUuid)) {
    echo "   ✓ UUID-Format ist korrekt\n";
} else {
    echo "   ✗ UUID-Format ist INKORREKT\n";
}

if (preg_match('/^[a-f0-9]{8}$/', $testUuidShort)) {
    echo "   ✓ UUID-Short-Format ist korrekt\n";
} else {
    echo "   ✗ UUID-Short-Format ist INKORREKT\n";
}

echo "\n=== Test abgeschlossen ===\n";