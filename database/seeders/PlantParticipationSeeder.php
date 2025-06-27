<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PlantParticipation;
use App\Models\Customer;
use App\Models\SolarPlant;

class PlantParticipationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Alle Kunden und Solaranlagen abrufen
        $customers = Customer::all();
        $solarPlants = SolarPlant::all();

        if ($customers->isEmpty() || $solarPlants->isEmpty()) {
            $this->command->warn('Keine Kunden oder Solaranlagen gefunden. Bitte zuerst CustomerSeeder und SolarPlantSeeder ausführen.');
            return;
        }

        $this->command->info('Erstelle Solar-Beteiligungen...');

        // Für jede Solaranlage verschiedene Beteiligungen erstellen
        foreach ($solarPlants as $plant) {
            $remainingPercentage = 100.0;
            $participantCount = rand(2, 4); // 2-4 Beteiligte pro Anlage
            
            // Zufällige Kunden für diese Anlage auswählen
            $selectedCustomers = $customers->random($participantCount);
            
            foreach ($selectedCustomers as $index => $customer) {
                // Für den letzten Teilnehmer den Rest vergeben
                if ($index === $participantCount - 1) {
                    $percentage = $remainingPercentage;
                } else {
                    // Zufällige Beteiligung zwischen 10% und 40%
                    $maxPercentage = min(40.0, $remainingPercentage - (($participantCount - $index - 1) * 10));
                    $percentage = rand(1000, (int)($maxPercentage * 100)) / 100; // 2 Dezimalstellen
                }
                
                $remainingPercentage -= $percentage;
                
                PlantParticipation::create([
                    'customer_id' => $customer->id,
                    'solar_plant_id' => $plant->id,
                    'percentage' => $percentage,
                ]);
                
                $this->command->info("✓ {$customer->display_name}: {$percentage}% an {$plant->name}");
                
                // Wenn 100% erreicht, stoppen
                if ($remainingPercentage <= 0) {
                    break;
                }
            }
        }

        $totalParticipations = PlantParticipation::count();
        $this->command->info("✅ {$totalParticipations} Solar-Beteiligungen erfolgreich erstellt!");
    }
}