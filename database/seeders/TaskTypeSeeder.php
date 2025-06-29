<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaskType;

class TaskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taskTypes = [
            [
                'name' => 'Allgemein',
                'description' => 'Allgemeine Aufgaben ohne spezifische Kategorie',
                'color' => '#6B7280',
                'icon' => 'heroicon-o-clipboard-document-list',
                'sort_order' => 1,
            ],
            [
                'name' => 'Kundenkommunikation',
                'description' => 'Aufgaben bezüglich Kundenkontakt und -betreuung',
                'color' => '#3B82F6',
                'icon' => 'heroicon-o-chat-bubble-left-right',
                'sort_order' => 2,
            ],
            [
                'name' => 'Projektplanung',
                'description' => 'Planung und Koordination von Solarprojekten',
                'color' => '#10B981',
                'icon' => 'heroicon-o-calendar-days',
                'sort_order' => 3,
            ],
            [
                'name' => 'Installation',
                'description' => 'Aufgaben rund um die Installation von Solaranlagen',
                'color' => '#F59E0B',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'sort_order' => 4,
            ],
            [
                'name' => 'Wartung',
                'description' => 'Wartungs- und Instandhaltungsaufgaben',
                'color' => '#8B5CF6',
                'icon' => 'heroicon-o-cog-6-tooth',
                'sort_order' => 5,
            ],
            [
                'name' => 'Abrechnung',
                'description' => 'Aufgaben bezüglich Rechnungsstellung und Abrechnung',
                'color' => '#EF4444',
                'icon' => 'heroicon-o-currency-euro',
                'sort_order' => 6,
            ],
            [
                'name' => 'Dokumentation',
                'description' => 'Erstellung und Pflege von Dokumenten',
                'color' => '#06B6D4',
                'icon' => 'heroicon-o-document-text',
                'sort_order' => 7,
            ],
            [
                'name' => 'Lieferanten',
                'description' => 'Aufgaben bezüglich Lieferantenmanagement',
                'color' => '#84CC16',
                'icon' => 'heroicon-o-truck',
                'sort_order' => 8,
            ],
            [
                'name' => 'Qualitätskontrolle',
                'description' => 'Prüfung und Qualitätssicherung',
                'color' => '#F97316',
                'icon' => 'heroicon-o-shield-check',
                'sort_order' => 9,
            ],
            [
                'name' => 'Nachbearbeitung',
                'description' => 'Follow-up Aufgaben nach Projektabschluss',
                'color' => '#EC4899',
                'icon' => 'heroicon-o-arrow-path',
                'sort_order' => 10,
            ],
        ];

        foreach ($taskTypes as $taskType) {
            TaskType::create($taskType);
        }
    }
}
