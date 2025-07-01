<?php

namespace App\Filament\Widgets;

use App\Models\SolarPlant;
use Filament\Widgets\ChartWidget;

class SolarPlantCapacityChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Anlagenkapazität nach Größe';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Kategorisiere Anlagen nach Kapazität
        $smallPlants = SolarPlant::where('total_capacity_kw', '<=', 10)->count(); // <= 10 kW
        $mediumPlants = SolarPlant::whereBetween('total_capacity_kw', [10.01, 50])->count(); // 10-50 kW
        $largePlants = SolarPlant::whereBetween('total_capacity_kw', [50.01, 100])->count(); // 50-100 kW
        $xlargePlants = SolarPlant::where('total_capacity_kw', '>', 100)->count(); // > 100 kW

        return [
            'datasets' => [
                [
                    'data' => [$smallPlants, $mediumPlants, $largePlants, $xlargePlants],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',   // Grün für kleine Anlagen
                        'rgba(59, 130, 246, 0.8)',  // Blau für mittlere Anlagen
                        'rgba(245, 158, 11, 0.8)',  // Gelb für große Anlagen
                        'rgba(239, 68, 68, 0.8)',   // Rot für sehr große Anlagen
                    ],
                    'borderColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Klein (≤10 kWp)', 'Mittel (10-50 kWp)', 'Groß (50-100 kWp)', 'Sehr groß (>100 kWp)'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}