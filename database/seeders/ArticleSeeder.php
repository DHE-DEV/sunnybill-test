<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'name' => 'Webentwicklung (Stunde)',
                'description' => 'Professionelle Webentwicklung mit modernen Technologien',
                'price' => 85.000000,
                'tax_rate' => 0.19,
                'unit' => 'Stunde',
            ],
            [
                'name' => 'Beratung (Stunde)',
                'description' => 'IT-Beratung und Strategieentwicklung',
                'price' => 120.000000,
                'tax_rate' => 0.19,
                'unit' => 'Stunde',
            ],
            [
                'name' => 'Design (Stunde)',
                'description' => 'UI/UX Design und Grafikdesign',
                'price' => 75.000000,
                'tax_rate' => 0.19,
                'unit' => 'Stunde',
            ],
            [
                'name' => 'Projektmanagement (Tag)',
                'description' => 'Professionelles Projektmanagement',
                'price' => 650.000000,
                'tax_rate' => 0.19,
                'unit' => 'Tag',
            ],
            [
                'name' => 'Server-Setup',
                'description' => 'Einrichtung und Konfiguration von Servern',
                'price' => 450.000000,
                'tax_rate' => 0.19,
                'unit' => 'Stück',
            ],
            [
                'name' => 'Wartung (Monat)',
                'description' => 'Monatliche Wartung und Support',
                'price' => 199.990000,
                'tax_rate' => 0.19,
                'unit' => 'Monat',
            ],
            [
                'name' => 'Schulung (Tag)',
                'description' => 'Mitarbeiterschulung und Workshops',
                'price' => 890.000000,
                'tax_rate' => 0.19,
                'unit' => 'Tag',
            ],
            [
                'name' => 'Lizenz Premium Software',
                'description' => 'Jahreslizenz für Premium-Software',
                'price' => 1299.990000,
                'tax_rate' => 0.19,
                'unit' => 'Stück',
            ],
            [
                'name' => 'Hosting (Monat)',
                'description' => 'Professionelles Webhosting',
                'price' => 29.990000,
                'tax_rate' => 0.19,
                'unit' => 'Monat',
            ],
            [
                'name' => 'SSL-Zertifikat',
                'description' => 'Wildcard SSL-Zertifikat für ein Jahr',
                'price' => 89.000000,
                'tax_rate' => 0.19,
                'unit' => 'Stück',
            ],
            [
                'name' => 'Datenbank-Optimierung',
                'description' => 'Performance-Optimierung von Datenbanken',
                'price' => 350.000000,
                'tax_rate' => 0.19,
                'unit' => 'Stück',
            ],
            [
                'name' => 'SEO-Analyse',
                'description' => 'Umfassende Suchmaschinenoptimierung',
                'price' => 299.000000,
                'tax_rate' => 0.19,
                'unit' => 'Stück',
            ],
            // Artikel mit verschiedenen Steuersätzen für ZugFERD-Tests
            [
                'name' => 'Solarmodul 450W',
                'description' => 'Testdaten für Solarmodul 450W',
                'price' => 299.990000,
                'tax_rate' => 0.00, // 0% Steuersatz
                'unit' => 'Stück',
            ],
            [
                'name' => 'Fachbuch IT',
                'description' => 'Fachbuch über moderne IT-Technologien',
                'price' => 49.990000,
                'tax_rate' => 0.07, // 7% ermäßigter Steuersatz
                'unit' => 'Stück',
            ],
            [
                'name' => 'Hardware-Komponente',
                'description' => 'Verschiedene Hardware-Komponenten',
                'price' => 199.990000,
                'tax_rate' => 0.19, // 19% Standardsatz
                'unit' => 'Stück',
            ],
        ];

        foreach ($articles as $articleData) {
            Article::create($articleData);
        }
    }
}
