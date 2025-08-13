<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\SupplierContractBilling;
use App\Models\SupplierContractBillingArticle;
use App\Models\SolarPlantBilling;
use App\Models\Article;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verwende eine existierende Solaranlagen-Abrechnung mit Artikeln
$solarPlantBillingId = '0198a53c-5ea5-72b2-b5d8-84499b0ba992';
$solarPlantBilling = SolarPlantBilling::find($solarPlantBillingId);

if (!$solarPlantBilling) {
    echo "Solaranlagen-Abrechnung nicht gefunden.\n";
    exit(1);
}

echo "Gefundene Solaranlagen-Abrechnung: {$solarPlantBilling->invoice_number}\n";
echo "Abrechnungsmonat: {$solarPlantBilling->billing_month}/{$solarPlantBilling->billing_year}\n\n";

// Hole die Vertragsabrechnungen aus dem credit_breakdown
$creditBreakdown = $solarPlantBilling->credit_breakdown ?? [];
$updatedAny = false;

foreach ($creditBreakdown as $index => $credit) {
    if (isset($credit['contract_billing_id'])) {
        $contractBilling = SupplierContractBilling::with('articles.article')->find($credit['contract_billing_id']);
        
        if ($contractBilling) {
            echo "Verarbeite Vertragsabrechnung: {$contractBilling->billing_number}\n";
            echo "Lieferant: {$credit['supplier_name']}\n";
            
            // Prüfe ob Artikel vorhanden sind
            if ($contractBilling->articles->isEmpty()) {
                echo "  Keine Artikel vorhanden - erstelle Beispielartikel...\n";
                
                // Hole die erste tax_rate_id oder null
                $taxRateId = \DB::table('tax_rates')->first()?->id;
                
                // Erstelle einen Artikel im System wenn noch keiner existiert
                $article = Article::firstOrCreate(
                    ['name' => 'Marktprämie PV'],
                    [
                        'description' => 'Marktprämie für Photovoltaik-Anlagen',
                        'unit' => 'kWh',
                        'price' => 0.0856,
                        'tax_rate_id' => $taxRateId,
                        'supplier_id' => $contractBilling->supplier_id,
                    ]
                );
                
                // Füge Artikel zur Vertragsabrechnung hinzu
                $billingArticle = SupplierContractBillingArticle::create([
                    'supplier_contract_billing_id' => $contractBilling->id,
                    'article_id' => $article->id,
                    'description' => 'Marktprämie PV April 2025',
                    'quantity' => 15000.456,
                    'unit_price' => 0.0856,
                    'total_price' => 1284.04,
                    'detailed_description' => "Die Marktprämie ist eine staatliche Förderung im Rahmen des EEG (Erneuerbare-Energien-Gesetz). Sie gleicht die Differenz zwischen dem anzulegenden Wert (feste EEG-Vergütung) und dem durchschnittlichen monatlichen Börsenpreis aus. Dadurch erhalten Sie eine stabile Vergütung für Ihren eingespeisten Strom, unabhängig von Schwankungen am Strommarkt. Die Marktprämie wird monatlich auf Basis der tatsächlich eingespeisten Energiemenge berechnet und ausgezahlt.",
                ]);
                
                echo "  - Artikel 'Marktprämie PV' mit detaillierter Beschreibung erstellt\n";
                $updatedAny = true;
                
                // Erstelle zweiten Artikel wenn es Next Kraftwerke ist
                if (str_contains($credit['supplier_name'], 'Next Kraftwerke')) {
                    $article2 = Article::firstOrCreate(
                        ['name' => 'Direktvermarktungserlös'],
                        [
                            'description' => 'Erlös aus der Direktvermarktung',
                            'unit' => 'kWh',
                            'price' => 0.0450,
                            'tax_rate_id' => $taxRateId,
                            'supplier_id' => $contractBilling->supplier_id,
                        ]
                    );
                    
                    $billingArticle2 = SupplierContractBillingArticle::create([
                        'supplier_contract_billing_id' => $contractBilling->id,
                        'article_id' => $article2->id,
                        'description' => 'Direktvermarktungserlös April 2025',
                        'quantity' => 15000.456,
                        'unit_price' => 0.0450,
                        'total_price' => 675.02,
                        'detailed_description' => "Der Direktvermarktungserlös ist der Betrag, den Sie durch den Verkauf Ihres Stroms an der Strombörse erzielen. Ihr Strom wird von unserem Direktvermarkter gebündelt mit anderen Anlagen vermarktet und zu optimalen Zeiten an der Börse verkauft. Der Erlös richtet sich nach den aktuellen Börsenpreisen und kann je nach Marktlage variieren. Durch professionelle Vermarktung werden dabei bessere Preise erzielt als beim Eigenverkauf.",
                    ]);
                    
                    echo "  - Artikel 'Direktvermarktungserlös' mit detaillierter Beschreibung erstellt\n";
                }
            } else {
                // Artikel existieren bereits - füge nur detaillierte Beschreibungen hinzu
                foreach ($contractBilling->articles as $article) {
                    if (empty($article->detailed_description)) {
                        $detailedDescription = null;
                        
                        if (str_contains(strtolower($article->description ?? ''), 'marktprämie')) {
                            $detailedDescription = "Die Marktprämie ist eine staatliche Förderung im Rahmen des EEG (Erneuerbare-Energien-Gesetz). Sie gleicht die Differenz zwischen dem anzulegenden Wert (feste EEG-Vergütung) und dem durchschnittlichen monatlichen Börsenpreis aus. Dadurch erhalten Sie eine stabile Vergütung für Ihren eingespeisten Strom, unabhängig von Schwankungen am Strommarkt.";
                        } elseif (str_contains(strtolower($article->description ?? ''), 'direktvermarktung')) {
                            $detailedDescription = "Bei der Direktvermarktung wird der erzeugte Strom Ihrer Solaranlage direkt an der Strombörse verkauft. Sie erhalten dafür den aktuellen Marktpreis plus die Marktprämie. Dieses Modell ermöglicht höhere Erlöse als die feste EEG-Vergütung und ist für Anlagen über 100 kWp verpflichtend.";
                        } elseif (str_contains(strtolower($article->description ?? ''), 'einspeis')) {
                            $detailedDescription = "Die Einspeisevergütung ist der Betrag, den Sie für die Einspeisung Ihres Solarstroms ins öffentliche Netz erhalten. Die Höhe richtet sich nach dem EEG-Tarif zum Zeitpunkt der Inbetriebnahme Ihrer Anlage und ist für 20 Jahre garantiert.";
                        } else {
                            // Generische Beschreibung
                            $detailedDescription = "Diese Position bezieht sich auf Ihre anteiligen Erlöse aus der Stromproduktion der Solaranlage im Abrechnungszeitraum. Die Berechnung erfolgt auf Basis Ihres Beteiligungsanteils und der tatsächlich produzierten Energiemenge.";
                        }
                        
                        if ($detailedDescription) {
                            $article->detailed_description = $detailedDescription;
                            $article->save();
                            echo "  - Artikel '{$article->description}': Detaillierte Beschreibung hinzugefügt\n";
                            $updatedAny = true;
                        }
                    } else {
                        echo "  - Artikel '{$article->description}': Hat bereits eine detaillierte Beschreibung\n";
                    }
                }
            }
            
            echo "\n";
        }
    }
}

if ($updatedAny) {
    // Aktualisiere die Solaranlagen-Abrechnung
    echo "Aktualisiere Solaranlagen-Abrechnung...\n";
    
    // Berechne die Kosten neu
    $costData = SolarPlantBilling::calculateCostsForCustomer(
        $solarPlantBilling->solar_plant_id,
        $solarPlantBilling->customer_id,
        $solarPlantBilling->billing_year,
        $solarPlantBilling->billing_month,
        $solarPlantBilling->participation_percentage
    );
    
    // Aktualisiere die Abrechnung mit den neuen Daten
    $solarPlantBilling->update([
        'credit_breakdown' => $costData['credit_breakdown'],
        'cost_breakdown' => $costData['cost_breakdown'],
    ]);
    
    echo "Solaranlagen-Abrechnung erfolgreich aktualisiert.\n";
    echo "\nDie detaillierten Beschreibungen wurden hinzugefügt und sollten nun im PDF unter 'Erklärung der Artikel' erscheinen.\n";
} else {
    echo "\nKeine Änderungen vorgenommen - alle Artikel haben bereits detaillierte Beschreibungen.\n";
}

echo "\nÖffnen Sie die Abrechnung im Admin-Panel und generieren Sie das PDF erneut:\n";
echo "URL: https://sunnybill-test.test/admin/solar-plant-billings/{$solarPlantBillingId}\n";
