<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('storage_settings', function (Blueprint $table) {
            $table->json('storage_paths')->nullable()->after('storage_config');
        });

        // Standard-Pfade für bestehende Einstellungen hinzufügen
        $this->addDefaultStoragePaths();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storage_settings', function (Blueprint $table) {
            $table->dropColumn('storage_paths');
        });
    }

    /**
     * Standard-Speicherpfade hinzufügen
     */
    private function addDefaultStoragePaths(): void
    {
        $defaultPaths = [
            'suppliers' => [
                'name' => 'Lieferanten-Dokumente',
                'description' => 'Dokumente für Lieferanten mit strukturierter Organisation',
                'pattern' => 'documents/{supplier_number}-{supplier_name}/{document_number}-{document_name}',
                'placeholders' => [
                    'supplier_number' => 'Lieferantennummer',
                    'supplier_name' => 'Lieferantenname (bereinigt)',
                    'document_number' => 'Dokumentnummer',
                    'document_name' => 'Dokumentname (bereinigt)',
                    'category' => 'Dokumentkategorie',
                    'date' => 'Aktuelles Datum (YYYY-MM-DD)',
                    'timestamp' => 'Unix-Timestamp'
                ],
                'example' => 'documents/LF001-Mustermann-GmbH/DOC001-Vertrag-2024.pdf',
                'active' => true
            ],
            'contracts' => [
                'name' => 'Vertrags-Dokumente',
                'description' => 'Strukturierte Ablage für Vertragsdokumente',
                'pattern' => 'documents/contracts/{date}/{category}/{document_name}',
                'placeholders' => [
                    'date' => 'Aktuelles Datum (YYYY-MM-DD)',
                    'category' => 'Dokumentkategorie',
                    'document_name' => 'Dokumentname (bereinigt)',
                    'document_number' => 'Dokumentnummer'
                ],
                'example' => 'documents/contracts/2024-07-01/supplier-contracts/Vertrag-Mustermann.pdf',
                'active' => true
            ],
            'general' => [
                'name' => 'Allgemeine Dokumente',
                'description' => 'Standard-Ablage für allgemeine Dokumente',
                'pattern' => 'documents/{category}/{document_name}',
                'placeholders' => [
                    'category' => 'Dokumentkategorie',
                    'document_name' => 'Dokumentname (bereinigt)',
                    'date' => 'Aktuelles Datum (YYYY-MM-DD)'
                ],
                'example' => 'documents/invoices/Rechnung-2024-001.pdf',
                'active' => true
            ]
        ];

        // Für alle bestehenden Storage-Einstellungen die Standard-Pfade hinzufügen
        DB::table('storage_settings')->update([
            'storage_paths' => json_encode($defaultPaths)
        ]);
    }
};
