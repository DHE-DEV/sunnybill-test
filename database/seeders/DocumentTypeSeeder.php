<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentTypes = [
            [
                'name' => 'Planung',
                'key' => 'planning',
                'description' => 'Planungsdokumente für Projekte und Anlagen',
                'color' => 'primary',
                'icon' => 'heroicon-o-clipboard-document',
                'sort_order' => 10,
            ],
            [
                'name' => 'Genehmigungen',
                'key' => 'permits',
                'description' => 'Behördliche Genehmigungen und Bescheide',
                'color' => 'warning',
                'icon' => 'heroicon-o-academic-cap',
                'sort_order' => 20,
            ],
            [
                'name' => 'Installation',
                'key' => 'installation',
                'description' => 'Installationsdokumente und Protokolle',
                'color' => 'success',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'sort_order' => 30,
            ],
            [
                'name' => 'Wartung',
                'key' => 'maintenance',
                'description' => 'Wartungsprotokolle und Servicedokumente',
                'color' => 'info',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'sort_order' => 40,
            ],
            [
                'name' => 'Rechnungen',
                'key' => 'invoices',
                'description' => 'Rechnungen und Abrechnungsdokumente',
                'color' => 'danger',
                'icon' => 'heroicon-o-banknotes',
                'sort_order' => 50,
            ],
            [
                'name' => 'Zertifikate',
                'key' => 'certificates',
                'description' => 'Zertifikate und Bescheinigungen',
                'color' => 'secondary',
                'icon' => 'heroicon-o-academic-cap',
                'sort_order' => 60,
            ],
            [
                'name' => 'Verträge',
                'key' => 'contracts',
                'description' => 'Verträge und rechtliche Dokumente',
                'color' => 'primary',
                'icon' => 'heroicon-o-document-text',
                'sort_order' => 70,
            ],
            [
                'name' => 'Korrespondenz',
                'key' => 'correspondence',
                'description' => 'E-Mails, Briefe und sonstige Korrespondenz',
                'color' => 'info',
                'icon' => 'heroicon-o-envelope',
                'sort_order' => 80,
            ],
            [
                'name' => 'Technische Unterlagen',
                'key' => 'technical',
                'description' => 'Technische Dokumentationen und Datenblätter',
                'color' => 'secondary',
                'icon' => 'heroicon-o-document',
                'sort_order' => 90,
            ],
            [
                'name' => 'Fotos',
                'key' => 'photos',
                'description' => 'Fotos und Bildmaterial',
                'color' => 'success',
                'icon' => 'heroicon-o-photo',
                'sort_order' => 100,
            ],
            [
                'name' => 'Inbetriebnahme',
                'key' => 'commissioning',
                'description' => 'Inbetriebnahme-Dokumente und Protokolle',
                'color' => 'warning',
                'icon' => 'heroicon-o-play',
                'sort_order' => 110,
            ],
            [
                'name' => 'Rechtsdokumente',
                'key' => 'legal',
                'description' => 'Rechtliche Dokumente und Vereinbarungen',
                'color' => 'danger',
                'icon' => 'heroicon-o-scale',
                'sort_order' => 120,
            ],
            [
                'name' => 'Sonstiges',
                'key' => 'other',
                'description' => 'Sonstige Dokumente',
                'color' => 'gray',
                'icon' => 'heroicon-o-document',
                'sort_order' => 130,
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::updateOrCreate(
                ['key' => $documentType['key']],
                $documentType
            );
        }
    }
}
