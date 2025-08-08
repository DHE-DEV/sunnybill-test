<?php

require_once 'vendor/autoload.php';

use App\Models\SolarPlant;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Bereinige Solaranlagen ohne kWp-Leistungswerte ===\n\n";

try {
    // Finde alle Solaranlagen ohne oder mit 0 kWp Leistung
    $plantsWithoutCapacity = SolarPlant::where(function($query) {
        $query->whereNull('total_capacity_kw')
              ->orWhere('total_capacity_kw', 0)
              ->orWhere('total_capacity_kw', '0.00');
    })->get();

    echo "🔍 Gefundene Solaranlagen ohne kWp-Leistung:\n";
    if ($plantsWithoutCapacity->isEmpty()) {
        echo "   ✅ Keine Solaranlagen ohne kWp-Leistung gefunden.\n";
        echo "   💡 Alle Solaranlagen haben bereits korrekte Leistungswerte!\n\n";
        exit;
    }

    echo "   📊 Anzahl gefundener Anlagen: " . $plantsWithoutCapacity->count() . "\n\n";
    
    // Zeige Details der zu löschenden Anlagen
    echo "📋 DETAILS DER ZU LÖSCHENDEN ANLAGEN:\n";
    foreach ($plantsWithoutCapacity as $plant) {
        $capacityDisplay = is_null($plant->total_capacity_kw) ? 'NULL' : $plant->total_capacity_kw . ' kWp';
        $createdAt = $plant->created_at ? $plant->created_at->format('d.m.Y H:i') : 'Unbekannt';
        echo "   🗑️  ID: {$plant->id} | Name: " . ($plant->name ?: 'Ohne Name') . " | Leistung: {$capacityDisplay} | Erstellt: {$createdAt}\n";
    }

    echo "\n" . str_repeat('-', 80) . "\n";
    echo "⚠️  WARNUNG: Die oben aufgelisteten " . $plantsWithoutCapacity->count() . " Solaranlagen werden gelöscht!\n";
    echo "⚠️  Diese haben keine oder 0 kWp Leistungsangabe und sind wahrscheinlich Duplikate.\n";
    echo str_repeat('-', 80) . "\n\n";

    // Statistiken vor der Löschung
    $totalBefore = SolarPlant::count();
    $totalCapacityBefore = SolarPlant::sum('total_capacity_kw') ?: 0;
    
    echo "📊 STATISTIKEN VOR DER BEREINIGUNG:\n";
    echo "   • Solaranlagen gesamt: {$totalBefore}\n";
    echo "   • Gesamtkapazität: " . number_format($totalCapacityBefore, 1) . " kWp (" . number_format($totalCapacityBefore/1000, 2) . " MWp)\n";
    echo "   • Anlagen ohne Leistung: " . $plantsWithoutCapacity->count() . "\n\n";

    // Lösche die Anlagen ohne Kapazität
    echo "🗑️  Lösche Solaranlagen ohne kWp-Leistung...\n";
    $deletedCount = 0;
    foreach ($plantsWithoutCapacity as $plant) {
        $plantName = $plant->name ?: "ID {$plant->id}";
        try {
            $plant->delete();
            $deletedCount++;
            echo "   ✅ Gelöscht: {$plantName}\n";
        } catch (Exception $e) {
            echo "   ❌ Fehler beim Löschen von {$plantName}: " . $e->getMessage() . "\n";
        }
    }

    // Statistiken nach der Löschung
    $totalAfter = SolarPlant::count();
    $totalCapacityAfter = SolarPlant::sum('total_capacity_kw') ?: 0;
    
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "✅ BEREINIGUNG ABGESCHLOSSEN\n";
    echo str_repeat('=', 80) . "\n\n";
    
    echo "📊 STATISTIKEN NACH DER BEREINIGUNG:\n";
    echo "   • Solaranlagen gesamt: {$totalAfter}\n";
    echo "   • Gesamtkapazität: " . number_format($totalCapacityAfter, 1) . " kWp (" . number_format($totalCapacityAfter/1000, 2) . " MWp)\n";
    echo "   • Gelöschte Anlagen: {$deletedCount}\n";
    echo "   • Eingesparte Datensätze: " . ($totalBefore - $totalAfter) . "\n\n";

    // Zeige verbleibende Anlagen mit Kapazität
    $validPlants = SolarPlant::whereNotNull('total_capacity_kw')
                            ->where('total_capacity_kw', '>', 0)
                            ->orderBy('total_capacity_kw', 'desc')
                            ->get();

    if ($validPlants->isNotEmpty()) {
        echo "🌞 VERBLEIBENDE SOLARANLAGEN MIT KORREKTEN kWp-WERTEN:\n";
        foreach ($validPlants->take(10) as $plant) {
            $name = $plant->name ?: "Anlage ID {$plant->id}";
            echo "   ✅ {$name}: " . number_format($plant->total_capacity_kw, 1) . " kWp\n";
        }
        
        if ($validPlants->count() > 10) {
            echo "   📝 ... und " . ($validPlants->count() - 10) . " weitere Anlagen\n";
        }
        
        // Top 3 größte Anlagen
        $topPlants = $validPlants->take(3);
        echo "\n🏆 TOP 3 GRÖSSTE ANLAGEN:\n";
        foreach ($topPlants as $index => $plant) {
            $rank = $index + 1;
            $name = $plant->name ?: "Anlage ID {$plant->id}";
            $capacity = number_format($plant->total_capacity_kw, 1);
            echo "   {$rank}. {$name}: {$capacity} kWp\n";
        }
    }

    echo "\n✅ Datenbank erfolgreich bereinigt!\n";
    echo "💡 Alle verbleibenden Solaranlagen haben jetzt korrekte kWp-Leistungswerte.\n";
    echo "🔗 Sie können diese unter /admin/solar-plants einsehen.\n";

} catch (Exception $e) {
    echo "❌ Fehler bei der Bereinigung: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
