<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SolarPlant;
use App\Models\SolarPlantMilestone;

class SolarPlantMilestoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Alle Solaranlagen abrufen
        $plants = SolarPlant::all();

        foreach ($plants as $plant) {
            // Nur Meilensteine erstellen, wenn noch keine vorhanden sind
            if ($plant->milestones()->count() > 0) {
                continue;
            }

            // Projekttermine basierend auf Anlagenstatus erstellen
            switch ($plant->status) {
                case 'active':
                    $this->createCompletedProjectMilestones($plant);
                    break;
                case 'under_construction':
                    $this->createInProgressProjectMilestones($plant);
                    break;
                case 'in_planning':
                    $this->createPlannedProjectMilestones($plant);
                    break;
                case 'awaiting_commissioning':
                    $this->createAwaitingCommissioningMilestones($plant);
                    break;
                default:
                    $this->createGenericProjectMilestones($plant);
                    break;
            }
        }
    }

    /**
     * Erstelle abgeschlossene Projekttermine für aktive Anlagen
     */
    private function createCompletedProjectMilestones(SolarPlant $plant): void
    {
        $baseDate = $plant->installation_date ?? now()->subMonths(6);
        
        $milestones = [
            [
                'title' => 'Projektplanung abgeschlossen',
                'description' => 'Detailplanung und Auslegung der Solaranlage fertiggestellt',
                'planned_date' => $baseDate->copy()->subMonths(4),
                'actual_date' => $baseDate->copy()->subMonths(4)->addDays(2),
                'status' => 'completed',
                'sort_order' => 1,
            ],
            [
                'title' => 'Baugenehmigung erhalten',
                'description' => 'Offizielle Genehmigung von den Behörden erhalten',
                'planned_date' => $baseDate->copy()->subMonths(3),
                'actual_date' => $baseDate->copy()->subMonths(3)->addDays(5),
                'status' => 'completed',
                'sort_order' => 2,
            ],
            [
                'title' => 'Material bestellt',
                'description' => 'Alle Komponenten (Module, Wechselrichter, Montagesystem) bestellt',
                'planned_date' => $baseDate->copy()->subMonths(2)->addWeeks(2),
                'actual_date' => $baseDate->copy()->subMonths(2)->addWeeks(2)->addDays(1),
                'status' => 'completed',
                'sort_order' => 3,
            ],
            [
                'title' => 'Gerüst aufgebaut',
                'description' => 'Arbeitsgerüst für sichere Installation errichtet',
                'planned_date' => $baseDate->copy()->subWeeks(2),
                'actual_date' => $baseDate->copy()->subWeeks(2),
                'status' => 'completed',
                'sort_order' => 4,
            ],
            [
                'title' => 'Module installiert',
                'description' => 'Alle Solarmodule montiert und verkabelt',
                'planned_date' => $baseDate->copy()->subWeeks(1),
                'actual_date' => $baseDate->copy()->subDays(3),
                'status' => 'completed',
                'sort_order' => 5,
            ],
            [
                'title' => 'Wechselrichter angeschlossen',
                'description' => 'Wechselrichter installiert und konfiguriert',
                'planned_date' => $baseDate->copy(),
                'actual_date' => $baseDate->copy()->addDays(1),
                'status' => 'completed',
                'sort_order' => 6,
            ],
            [
                'title' => 'Netzanschluss hergestellt',
                'description' => 'Anschluss an das öffentliche Stromnetz durch Netzbetreiber',
                'planned_date' => $baseDate->copy()->addWeeks(1),
                'actual_date' => $baseDate->copy()->addWeeks(1)->addDays(2),
                'status' => 'completed',
                'sort_order' => 7,
            ],
            [
                'title' => 'Inbetriebnahme',
                'description' => 'Offizielle Inbetriebnahme und erste Stromerzeugung',
                'planned_date' => $plant->commissioning_date ?? $baseDate->copy()->addWeeks(2),
                'actual_date' => $plant->commissioning_date ?? $baseDate->copy()->addWeeks(2),
                'status' => 'completed',
                'sort_order' => 8,
            ],
        ];

        foreach ($milestones as $milestone) {
            $plant->milestones()->create($milestone);
        }
    }

    /**
     * Erstelle Projekttermine für Anlagen im Bau
     */
    private function createInProgressProjectMilestones(SolarPlant $plant): void
    {
        $baseDate = now();
        
        $milestones = [
            [
                'title' => 'Projektplanung abgeschlossen',
                'description' => 'Detailplanung und Auslegung der ' . ($plant->total_capacity_kw >= 1000 ? '2 MW ' : '') . 'Solaranlage fertiggestellt',
                'planned_date' => $baseDate->copy()->subMonths(3),
                'actual_date' => $baseDate->copy()->subMonths(3)->addDays(1),
                'status' => 'completed',
                'sort_order' => 1,
            ],
            [
                'title' => 'Baugenehmigung erhalten',
                'description' => 'Genehmigung für ' . ($plant->total_capacity_kw >= 1000 ? '2 MW Großanlage' : 'Solaranlage') . ' von Behörden erhalten',
                'planned_date' => $baseDate->copy()->subMonths(2),
                'actual_date' => $baseDate->copy()->subMonths(2)->addDays(5),
                'status' => 'completed',
                'sort_order' => 2,
            ],
            [
                'title' => 'Material bestellt',
                'description' => 'Bestellung von ' . ($plant->total_capacity_kw >= 1000 ? '400 Huawei Modulen und 8 Wechselrichtern (2 MW)' : 'allen Komponenten'),
                'planned_date' => $baseDate->copy()->subMonths(1)->addWeeks(2),
                'actual_date' => $baseDate->copy()->subMonths(1)->addWeeks(2)->addDays(3),
                'status' => 'completed',
                'sort_order' => 3,
            ],
            [
                'title' => 'Fundamente/Unterkonstruktion',
                'description' => $plant->total_capacity_kw >= 1000 ? 'Fundamente für Freiflächenanlage gegossen' : 'Montagesystem auf Dach installiert',
                'planned_date' => $baseDate->copy()->subWeeks(3),
                'actual_date' => $baseDate->copy()->subWeeks(2),
                'status' => 'completed',
                'sort_order' => 4,
            ],
            [
                'title' => 'Module montieren',
                'description' => 'Installation der ' . ($plant->total_capacity_kw >= 1000 ? '400 Solarmodule (2 MW)' : $plant->panel_count . ' Solarmodule'),
                'planned_date' => $baseDate->copy()->addWeeks(1),
                'actual_date' => null,
                'status' => 'in_progress',
                'sort_order' => 5,
            ],
            [
                'title' => 'Wechselrichter anschließen',
                'description' => 'Installation der ' . ($plant->total_capacity_kw >= 1000 ? '8 Huawei Wechselrichter (2 MW)' : $plant->inverter_count . ' Wechselrichter'),
                'planned_date' => $baseDate->copy()->addWeeks(3),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 6,
            ],
            [
                'title' => 'Netzanschluss',
                'description' => $plant->total_capacity_kw >= 1000 ? 'Anschluss an Mittelspannungsnetz für 2 MW' : 'Netzanschluss durch Netzbetreiber',
                'planned_date' => $baseDate->copy()->addMonths(1),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 7,
            ],
            [
                'title' => 'Inbetriebnahme',
                'description' => 'Offizielle Inbetriebnahme und Freischaltung',
                'planned_date' => $plant->planned_commissioning_date ?? $baseDate->copy()->addMonths(1)->addWeeks(1),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 8,
            ],
        ];

        foreach ($milestones as $milestone) {
            $plant->milestones()->create($milestone);
        }
    }

    /**
     * Erstelle Projekttermine für Anlagen in Planung
     */
    private function createPlannedProjectMilestones(SolarPlant $plant): void
    {
        $baseDate = now();
        
        $milestones = [
            [
                'title' => 'Standortanalyse',
                'description' => 'Detaillierte Analyse des Standorts und Potentialberechnung',
                'planned_date' => $baseDate->copy()->addWeeks(1),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 1,
            ],
            [
                'title' => 'Statikprüfung',
                'description' => $plant->total_capacity_kw >= 1000 ? 'Prüfung der Hallendächer für 2 MW Solaranlage' : 'Statische Prüfung der Dachkonstruktion',
                'planned_date' => $baseDate->copy()->addWeeks(2),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 2,
            ],
            [
                'title' => 'Detailplanung',
                'description' => 'Erstellung der Ausführungsplanung und technischen Dokumentation',
                'planned_date' => $baseDate->copy()->addWeeks(4),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 3,
            ],
            [
                'title' => 'Baugenehmigung beantragen',
                'description' => $plant->total_capacity_kw >= 1000 ? 'Antrag für 2 MW Gewerbeanlage bei Behörden' : 'Einreichung der Bauunterlagen',
                'planned_date' => $baseDate->copy()->addMonths(1),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 4,
            ],
            [
                'title' => 'Material bestellen',
                'description' => $plant->total_capacity_kw >= 1000 ? 'Bestellung von 3670 Huawei Modulen und 40 Wechselrichtern' : 'Bestellung aller Komponenten',
                'planned_date' => $baseDate->copy()->addMonths(2),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 5,
            ],
            [
                'title' => 'Installation beginnen',
                'description' => $plant->total_capacity_kw >= 1000 ? 'Start der Montagearbeiten für 2 MW Anlage' : 'Beginn der Installationsarbeiten',
                'planned_date' => $plant->planned_installation_date ?? $baseDate->copy()->addMonths(4),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 6,
            ],
            [
                'title' => 'Inbetriebnahme',
                'description' => 'Geplante Inbetriebnahme der Anlage',
                'planned_date' => $plant->planned_commissioning_date ?? $baseDate->copy()->addMonths(5),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 7,
            ],
        ];

        foreach ($milestones as $milestone) {
            $plant->milestones()->create($milestone);
        }
    }

    /**
     * Erstelle Projekttermine für Anlagen vor Inbetriebnahme
     */
    private function createAwaitingCommissioningMilestones(SolarPlant $plant): void
    {
        $baseDate = $plant->installation_date ?? now()->subWeeks(1);
        
        $milestones = [
            [
                'title' => 'Installation abgeschlossen',
                'description' => 'Alle ' . $plant->panel_count . ' Module und ' . $plant->inverter_count . ' Wechselrichter installiert',
                'planned_date' => $baseDate->copy()->subWeeks(1),
                'actual_date' => $baseDate->copy(),
                'status' => 'completed',
                'sort_order' => 1,
            ],
            [
                'title' => 'Technische Abnahme',
                'description' => 'Technische Prüfung durch Sachverständigen',
                'planned_date' => $baseDate->copy()->addDays(3),
                'actual_date' => $baseDate->copy()->addDays(5),
                'status' => 'completed',
                'sort_order' => 2,
            ],
            [
                'title' => 'Netzanschluss-Prüfung',
                'description' => 'Abnahme des Netzanschlusses durch Netzbetreiber',
                'planned_date' => now()->addDays(2),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 3,
            ],
            [
                'title' => 'Inbetriebnahme',
                'description' => 'Offizielle Inbetriebnahme und Freischaltung',
                'planned_date' => $plant->planned_commissioning_date ?? now()->addWeeks(1),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 4,
            ],
            [
                'title' => 'Monitoring aktivieren',
                'description' => 'Aktivierung des Überwachungssystems',
                'planned_date' => now()->addWeeks(1)->addDays(1),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 5,
            ],
            [
                'title' => 'Erste Wartung',
                'description' => 'Planmäßige Wartung nach 3 Monaten Betrieb',
                'planned_date' => now()->addMonths(3),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 6,
            ],
        ];

        foreach ($milestones as $milestone) {
            $plant->milestones()->create($milestone);
        }
    }

    /**
     * Erstelle generische Projekttermine
     */
    private function createGenericProjectMilestones(SolarPlant $plant): void
    {
        $baseDate = now();
        
        $milestones = [
            [
                'title' => 'Projektstart',
                'description' => 'Offizieller Projektbeginn und Kick-off Meeting',
                'planned_date' => $baseDate->copy()->subMonths(1),
                'actual_date' => $baseDate->copy()->subMonths(1),
                'status' => 'completed',
                'sort_order' => 1,
            ],
            [
                'title' => 'Planung abgeschlossen',
                'description' => 'Detailplanung und Genehmigungsunterlagen fertig',
                'planned_date' => $baseDate->copy()->subWeeks(2),
                'actual_date' => $baseDate->copy()->subWeeks(2)->addDays(3),
                'status' => 'completed',
                'sort_order' => 2,
            ],
            [
                'title' => 'Installation',
                'description' => 'Montage aller Komponenten',
                'planned_date' => $baseDate->copy()->addWeeks(2),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 3,
            ],
            [
                'title' => 'Inbetriebnahme',
                'description' => 'Inbetriebnahme der Solaranlage',
                'planned_date' => $baseDate->copy()->addMonths(1),
                'actual_date' => null,
                'status' => 'planned',
                'sort_order' => 4,
            ],
        ];

        foreach ($milestones as $milestone) {
            $plant->milestones()->create($milestone);
        }
    }
}