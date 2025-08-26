<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Updating Existing Solar Plant Billing Hint Text\n";
echo "===============================================\n\n";

try {
    // Hole alle bestehenden Abrechnungen
    $billings = App\Models\SolarPlantBilling::all();
    
    if ($billings->isEmpty()) {
        echo "❌ Keine Abrechnungen gefunden.\n";
        exit;
    }
    
    echo "📋 Gefundene Abrechnungen: " . $billings->count() . "\n\n";
    
    $updatedCount = 0;
    $errorCount = 0;
    
    foreach ($billings as $billing) {
        echo "🔄 Verarbeite Abrechnung: {$billing->invoice_number}\n";
        
        try {
            // Berechne die Kosten neu mit der aktualisierten Fallback-Logik
            $costData = App\Models\SolarPlantBilling::calculateCostsForCustomer(
                $billing->solar_plant_id,
                $billing->customer_id,
                $billing->billing_year,
                $billing->billing_month,
                $billing->participation_percentage
            );
            
            // Aktualisiere nur die Breakdown-Daten, nicht die Beträge
            $billing->update([
                'cost_breakdown' => $costData['cost_breakdown'],
                'credit_breakdown' => $costData['credit_breakdown'],
            ]);
            
            // Zähle Artikel mit Hinweisen
            $costHints = 0;
            $creditHints = 0;
            
            foreach ($costData['cost_breakdown'] as $cost) {
                foreach ($cost['articles'] as $article) {
                    if (!empty($article['detailed_description'])) {
                        $costHints++;
                    }
                }
            }
            
            foreach ($costData['credit_breakdown'] as $credit) {
                foreach ($credit['articles'] as $article) {
                    if (!empty($article['detailed_description'])) {
                        $creditHints++;
                    }
                }
            }
            
            echo "   ✅ Aktualisiert! Hinweise: Kosten=$costHints, Gutschriften=$creditHints\n";
            $updatedCount++;
            
        } catch (Exception $e) {
            echo "   ❌ Fehler: " . $e->getMessage() . "\n";
            $errorCount++;
        }
        
        echo "\n";
    }
    
    echo "===============================================\n";
    echo "ZUSAMMENFASSUNG:\n";
    echo "✅ Erfolgreich aktualisiert: $updatedCount Abrechnungen\n";
    echo "❌ Fehler bei: $errorCount Abrechnungen\n\n";
    
    if ($updatedCount > 0) {
        echo "🎉 Die Hinweistexte sollten jetzt in allen PDFs angezeigt werden!\n\n";
        echo "📝 WICHTIG: Testen Sie die PDF-Generierung für die Abrechnung AB-2025-0173\n";
        echo "    oder eine andere betroffene Abrechnung.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Kritischer Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
