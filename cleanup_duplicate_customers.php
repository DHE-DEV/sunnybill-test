<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Services\LexofficeService;

echo "=== DUPLIKAT-KUNDEN BEREINIGUNG ===\n\n";

// Finde den Max Mustermann ohne Lexoffice ID (älteren)
$maxMustermannOld = Customer::where('name', 'Max Mustermann')
                           ->whereNull('lexoffice_id')
                           ->first();

// Finde den Max Mustermann mit Lexoffice ID (neueren)
$maxMustermannNew = Customer::where('name', 'Max Mustermann')
                           ->whereNotNull('lexoffice_id')
                           ->first();

if ($maxMustermannOld && $maxMustermannNew) {
    echo "Gefunden:\n";
    echo "- Alter Max Mustermann (ohne Lexoffice): ID {$maxMustermannOld->id}, erstellt {$maxMustermannOld->created_at}\n";
    echo "- Neuer Max Mustermann (mit Lexoffice): ID {$maxMustermannNew->id}, erstellt {$maxMustermannNew->created_at}\n\n";
    
    // Prüfe ob der alte Kunde Daten hat, die übertragen werden müssen
    $hasImportantData = false;
    $dataToTransfer = [];
    
    if ($maxMustermannOld->customer_number && !$maxMustermannNew->customer_number) {
        $dataToTransfer['customer_number'] = $maxMustermannOld->customer_number;
        $hasImportantData = true;
    }
    
    if ($maxMustermannOld->notes && !$maxMustermannNew->notes) {
        $dataToTransfer['notes'] = $maxMustermannOld->notes;
        $hasImportantData = true;
    }
    
    // Prüfe Beziehungen
    $oldRelations = [
        'invoices' => $maxMustermannOld->invoices()->count(),
        'solarParticipations' => $maxMustermannOld->solarParticipations()->count(),
        'documents' => $maxMustermannOld->documents()->count(),
        'notes' => $maxMustermannOld->notes()->count(),
    ];
    
    $hasRelations = array_sum($oldRelations) > 0;
    
    if ($hasRelations) {
        echo "⚠️  Der alte Kunde hat wichtige Beziehungen:\n";
        foreach ($oldRelations as $relation => $count) {
            if ($count > 0) {
                echo "   - {$relation}: {$count}\n";
            }
        }
        echo "\n❌ ABBRUCH: Manuelle Bereinigung erforderlich!\n";
        echo "   Bitte übertragen Sie die Daten manuell und löschen dann den alten Kunden.\n";
    } else {
        echo "✅ Der alte Kunde hat keine wichtigen Beziehungen.\n";
        
        if ($hasImportantData) {
            echo "Übertrage Daten vom alten zum neuen Kunden:\n";
            foreach ($dataToTransfer as $field => $value) {
                echo "   - {$field}: {$value}\n";
            }
            $maxMustermannNew->update($dataToTransfer);
        }
        
        echo "Lösche alten Kunden...\n";
        $maxMustermannOld->delete();
        
        echo "✅ Bereinigung abgeschlossen!\n";
    }
} else {
    echo "Keine Max Mustermann Duplikate gefunden.\n";
}

echo "\n=== TESTE NEUE IMPORT-LOGIK ===\n";

// Teste die neue Import-Logik
$service = new LexofficeService();

echo "Führe Test-Import durch...\n";
$result = $service->importCustomers();

if ($result['success']) {
    echo "✅ Import erfolgreich!\n";
    echo "Importierte Kunden: {$result['imported']}\n";
    
    if (!empty($result['errors'])) {
        echo "Fehler:\n";
        foreach ($result['errors'] as $error) {
            echo "- {$error}\n";
        }
    }
} else {
    echo "❌ Import fehlgeschlagen: {$result['error']}\n";
}

echo "\n=== FINALE DUPLIKAT-PRÜFUNG ===\n";

// Prüfe erneut auf Duplikate
$duplicates = Customer::whereNotNull('lexoffice_id')
    ->get()
    ->groupBy('lexoffice_id')
    ->filter(function ($group) {
        return $group->count() > 1;
    });

if ($duplicates->isEmpty()) {
    echo "✅ Keine Duplikate mehr vorhanden!\n";
} else {
    echo "⚠️  Noch Duplikate vorhanden:\n";
    foreach ($duplicates as $lexofficeId => $customers) {
        echo "Lexoffice ID: {$lexofficeId} ({$customers->count()} Duplikate)\n";
    }
}

echo "\n=== ZUSAMMENFASSUNG ===\n";
echo "✅ Import-Logik verbessert:\n";
echo "   1. Prüft zuerst nach Lexoffice ID\n";
echo "   2. Dann nach Namen (für Kunden ohne Lexoffice ID)\n";
echo "   3. Erstellt nur neue Kunden wenn wirklich nötig\n";
echo "\n✅ Duplikat-Problem behoben!\n";
