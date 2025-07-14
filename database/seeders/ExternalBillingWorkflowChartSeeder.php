<?php

namespace Database\Seeders;

use App\Models\MermaidChart;
use Illuminate\Database\Seeder;

class ExternalBillingWorkflowChartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Chart erstellen oder aktualisieren
        MermaidChart::updateOrCreate(
            ['name' => 'Workflow für externe Abrechnungen'],
            [
                'description' => 'Zeigt den standardisierten Prozess für die Erfassung von externen Abrechnungen (Rechnungen/Gutschriften).',
                'template' => MermaidChart::getDefaultExternalBillingWorkflowTemplate(),
                'chart_type' => 'external_billing_workflow',
                'is_active' => true,
            ]
        );
    }
}
