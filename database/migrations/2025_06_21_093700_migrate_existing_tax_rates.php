<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\TaxRate;
use App\Models\Article;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Erstelle Standard-Steuersätze
        $taxRates = [
            [
                'name' => 'Steuerfrei',
                'rate' => 0.0000,
                'valid_from' => now(),
                'is_active' => true,
                'is_default' => false,
                'description' => 'Steuerfreie Artikel (0%)',
            ],
            [
                'name' => 'Ermäßigt',
                'rate' => 0.0700,
                'valid_from' => now(),
                'is_active' => true,
                'is_default' => false,
                'description' => 'Ermäßigter Steuersatz (7%)',
            ],
            [
                'name' => 'Standard',
                'rate' => 0.1900,
                'valid_from' => now(),
                'is_active' => true,
                'is_default' => true,
                'description' => 'Standard Steuersatz (19%)',
            ],
        ];

        $createdTaxRates = [];
        
        foreach ($taxRates as $taxRateData) {
            $taxRate = TaxRate::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => $taxRateData['name'],
                'rate' => $taxRateData['rate'],
                'valid_from' => $taxRateData['valid_from'],
                'valid_until' => null,
                'is_active' => $taxRateData['is_active'],
                'is_default' => $taxRateData['is_default'],
                'description' => $taxRateData['description'],
            ]);
            
            // Mappe sowohl Dezimal- als auch Prozentwerte
            $createdTaxRates[$taxRateData['rate']] = $taxRate->id;
            $createdTaxRates[$taxRateData['rate'] * 100] = $taxRate->id; // Für Prozent-Mapping
        }

        // Migriere bestehende Artikel
        if (Schema::hasColumn('articles', 'tax_rate')) {
            $articles = DB::table('articles')->get();
            
            foreach ($articles as $article) {
                $taxRateValue = (float) $article->tax_rate;
                $taxRateId = null;
                
                // Mappe alte Steuersätze auf neue IDs
                switch ($taxRateValue) {
                    case 0.00:
                        $taxRateId = $createdTaxRates[0.0000];
                        break;
                    case 0.07:
                        $taxRateId = $createdTaxRates[0.0700];
                        break;
                    case 0.19:
                        $taxRateId = $createdTaxRates[0.1900];
                        break;
                    default:
                        // Für unbekannte Steuersätze, erstelle einen neuen
                        $customTaxRate = TaxRate::create([
                            'id' => \Illuminate\Support\Str::uuid(),
                            'name' => 'Benutzerdefiniert ' . ($taxRateValue * 100) . '%',
                            'rate' => $taxRateValue, // Speichere als Dezimalwert
                            'valid_from' => now(),
                            'valid_until' => null,
                            'is_active' => true,
                            'is_default' => false,
                            'description' => 'Migrierter benutzerdefinierter Steuersatz',
                        ]);
                        $taxRateId = $customTaxRate->id;
                        break;
                }
                
                // Aktualisiere den Artikel mit der neuen tax_rate_id
                DB::table('articles')
                    ->where('id', $article->id)
                    ->update(['tax_rate_id' => $taxRateId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Setze tax_rate_id auf null für alle Artikel
        DB::table('articles')->update(['tax_rate_id' => null]);
        
        // Lösche alle TaxRates und TaxRateVersions
        DB::table('tax_rate_versions')->delete();
        DB::table('tax_rates')->delete();
    }
};