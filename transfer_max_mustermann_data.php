<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;

echo "=== MAX MUSTERMANN DATEN-ÜBERTRAGUNG ===\n\n";

// Finde beide Max Mustermann Kunden (jetzt haben beide Lexoffice IDs)
$maxMustermannCustomers = Customer::where('name', 'Max Mustermann')->get();

if ($maxMustermannCustomers->count() !== 2) {
    echo "❌ Erwartete 2 Max Mustermann Kunden, gefunden: {$maxMustermannCustomers->count()}\n";
    foreach ($maxMustermannCustomers as $customer) {
        echo "- ID: {$customer->id}, Lexoffice: " . ($customer->lexoffice_id ?: 'KEINE') . ", Erstellt: {$customer->created_at}\n";
    }
    exit;
}

// Sortiere nach Erstellungsdatum - älterer ist der ursprüngliche
$maxMustermannOld = $maxMustermannCustomers->sortBy('created_at')->first();
$maxMustermannNew = $maxMustermannCustomers->sortBy('created_at')->last();

if (!$maxMustermannOld || !$maxMustermannNew) {
    echo "❌ Einer der Max Mustermann Kunden wurde nicht gefunden.\n";
    exit;
}

echo "Übertrage Daten von:\n";
echo "- Alt: ID {$maxMustermannOld->id} (ohne Lexoffice)\n";
echo "- Neu: ID {$maxMustermannNew->id} (mit Lexoffice: {$maxMustermannNew->lexoffice_id})\n\n";

// Solar-Beteiligungen übertragen
$solarParticipations = $maxMustermannOld->solarParticipations;
echo "Solar-Beteiligungen zu übertragen: {$solarParticipations->count()}\n";

foreach ($solarParticipations as $participation) {
    echo "- Solar Plant: {$participation->solarPlant->name}, Anteil: {$participation->percentage}%\n";
    
    // Übertrage die Beteiligung zum neuen Kunden
    $participation->update(['customer_id' => $maxMustermannNew->id]);
}

// Andere wichtige Daten übertragen
$dataToTransfer = [];

if ($maxMustermannOld->customer_number && !$maxMustermannNew->customer_number) {
    $dataToTransfer['customer_number'] = $maxMustermannOld->customer_number;
}

if ($maxMustermannOld->notes && !$maxMustermannNew->notes) {
    $dataToTransfer['notes'] = $maxMustermannOld->notes;
}

// Adressdaten übertragen falls der neue Kunde keine hat
if (!$maxMustermannNew->street && $maxMustermannOld->street) {
    $dataToTransfer['street'] = $maxMustermannOld->street;
}

if (!$maxMustermannNew->postal_code && $maxMustermannOld->postal_code) {
    $dataToTransfer['postal_code'] = $maxMustermannOld->postal_code;
}

if (!$maxMustermannNew->city && $maxMustermannOld->city) {
    $dataToTransfer['city'] = $maxMustermannOld->city;
}

if (!$maxMustermannNew->phone && $maxMustermannOld->phone) {
    $dataToTransfer['phone'] = $maxMustermannOld->phone;
}

if (!$maxMustermannNew->email && $maxMustermannOld->email) {
    $dataToTransfer['email'] = $maxMustermannOld->email;
}

if (!empty($dataToTransfer)) {
    echo "\nÜbertrage zusätzliche Daten:\n";
    foreach ($dataToTransfer as $field => $value) {
        echo "- {$field}: {$value}\n";
    }
    $maxMustermannNew->update($dataToTransfer);
}

// Prüfe ob noch andere Beziehungen existieren
$remainingRelations = [
    'invoices' => $maxMustermannOld->invoices()->count(),
    'documents' => $maxMustermannOld->documents()->count(),
    'notes' => $maxMustermannOld->notes()->count(),
];

$hasRemainingRelations = array_sum($remainingRelations) > 0;

if ($hasRemainingRelations) {
    echo "\n⚠️  Noch verbleibende Beziehungen:\n";
    foreach ($remainingRelations as $relation => $count) {
        if ($count > 0) {
            echo "   - {$relation}: {$count}\n";
        }
    }
    echo "\n❌ ABBRUCH: Bitte übertragen Sie diese Daten manuell.\n";
} else {
    echo "\n✅ Alle wichtigen Daten übertragen.\n";
    echo "Lösche alten Max Mustermann Kunden...\n";
    
    // Lösche den alten Kunden
    $maxMustermannOld->delete();
    
    echo "✅ Übertragung und Bereinigung abgeschlossen!\n";
}

echo "\n=== ERGEBNIS ===\n";
$finalCustomer = Customer::find($maxMustermannNew->id);
echo "Finaler Max Mustermann Kunde:\n";
echo "- ID: {$finalCustomer->id}\n";
echo "- Name: {$finalCustomer->name}\n";
echo "- Lexoffice ID: {$finalCustomer->lexoffice_id}\n";
echo "- Kundennummer: " . ($finalCustomer->customer_number ?: 'KEINE') . "\n";
echo "- Solar-Beteiligungen: {$finalCustomer->solarParticipations()->count()}\n";
echo "- Email: " . ($finalCustomer->email ?: 'KEINE') . "\n";
echo "- Telefon: " . ($finalCustomer->phone ?: 'KEINE') . "\n";

echo "\n✅ Max Mustermann ist jetzt korrekt als ein einziger Kunde mit Lexoffice-Verknüpfung gespeichert!\n";
