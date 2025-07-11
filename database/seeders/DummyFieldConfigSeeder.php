<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DummyFieldConfig;

class DummyFieldConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Konfigurationen für alle Entitätstypen
        $entityConfigs = [
            'supplier_contract' => [
                'custom_field_1' => [
                    'field_label' => 'Zusätzliche Informationen',
                    'field_description' => 'Beliebige zusätzliche Informationen zum Vertrag',
                    'is_active' => true,
                    'column_span' => 2, // Volle Breite für längere Texte
                ],
                'custom_field_2' => [
                    'field_label' => 'Interne Notiz',
                    'field_description' => 'Interne Notizen für das Team',
                    'is_active' => true,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_3' => [
                    'field_label' => 'Referenz',
                    'field_description' => 'Externe Referenz oder Aktenzeichen',
                    'is_active' => false,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_4' => [
                    'field_label' => 'Besonderheiten',
                    'field_description' => 'Besondere Vereinbarungen oder Konditionen',
                    'is_active' => false,
                    'column_span' => 2, // Volle Breite für längere Texte
                ],
                'custom_field_5' => [
                    'field_label' => 'Kontaktperson',
                    'field_description' => 'Zuständige Kontaktperson beim Lieferanten',
                    'is_active' => false,
                    'column_span' => 1, // Halbe Breite
                ],
            ],
            'customer' => [
                'custom_field_1' => [
                    'field_label' => 'Kundenkategorie',
                    'field_description' => 'Spezielle Kategorie oder Klassifizierung des Kunden',
                    'is_active' => true,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_2' => [
                    'field_label' => 'Betreuer',
                    'field_description' => 'Zuständiger Betreuer oder Account Manager',
                    'is_active' => true,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_3' => [
                    'field_label' => 'Akquisequelle',
                    'field_description' => 'Wie wurde der Kunde akquiriert',
                    'is_active' => false,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_4' => [
                    'field_label' => 'Präferenzen',
                    'field_description' => 'Besondere Wünsche oder Präferenzen',
                    'is_active' => false,
                    'column_span' => 2, // Volle Breite für längere Texte
                ],
                'custom_field_5' => [
                    'field_label' => 'Notizen',
                    'field_description' => 'Allgemeine Notizen zum Kunden',
                    'is_active' => false,
                    'column_span' => 2, // Volle Breite für längere Texte
                ],
            ],
            'supplier' => [
                'custom_field_1' => [
                    'field_label' => 'Lieferantenkategorie',
                    'field_description' => 'Kategorie oder Klassifizierung des Lieferanten',
                    'is_active' => true,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_2' => [
                    'field_label' => 'Bewertung',
                    'field_description' => 'Interne Bewertung der Leistung',
                    'is_active' => true,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_3' => [
                    'field_label' => 'Zertifizierungen',
                    'field_description' => 'Relevante Zertifizierungen oder Standards',
                    'is_active' => false,
                    'column_span' => 2, // Volle Breite für längere Texte
                ],
                'custom_field_4' => [
                    'field_label' => 'Lieferzeit',
                    'field_description' => 'Typische Lieferzeiten',
                    'is_active' => false,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_5' => [
                    'field_label' => 'Besonderheiten',
                    'field_description' => 'Besondere Eigenschaften oder Konditionen',
                    'is_active' => false,
                    'column_span' => 2, // Volle Breite für längere Texte
                ],
            ],
            'solar_plant' => [
                'custom_field_1' => [
                    'field_label' => 'Projektphase',
                    'field_description' => 'Aktuelle Phase des Solaranlagen-Projekts',
                    'is_active' => true,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_2' => [
                    'field_label' => 'Wartungsintervall',
                    'field_description' => 'Geplante Wartungsintervalle',
                    'is_active' => true,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_3' => [
                    'field_label' => 'Versicherung',
                    'field_description' => 'Versicherungsdetails oder -nummer',
                    'is_active' => false,
                    'column_span' => 1, // Halbe Breite
                ],
                'custom_field_4' => [
                    'field_label' => 'Genehmigungen',
                    'field_description' => 'Status der behördlichen Genehmigungen',
                    'is_active' => false,
                    'column_span' => 2, // Volle Breite für längere Texte
                ],
                'custom_field_5' => [
                    'field_label' => 'Monitoring',
                    'field_description' => 'Monitoring-System oder -details',
                    'is_active' => false,
                    'column_span' => 2, // Volle Breite für längere Texte
                ],
            ],
        ];

        foreach ($entityConfigs as $entityType => $fields) {
            $sortOrder = 1;
            foreach ($fields as $fieldKey => $fieldData) {
                DummyFieldConfig::updateOrCreate(
                    [
                        'entity_type' => $entityType,
                        'field_key' => $fieldKey
                    ],
                    array_merge($fieldData, [
                        'entity_type' => $entityType,
                        'field_key' => $fieldKey,
                        'sort_order' => $sortOrder++,
                    ])
                );
            }
        }
    }
}
