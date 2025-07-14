<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Document;
use App\Models\DocumentType;

return new class extends Migration
{
    public function up(): void
    {
        // Mapping von alten Kategorien zu DocumentType Keys
        $categoryMapping = [
            'planning' => 'planning',
            'permits' => 'permits',
            'installation' => 'installation',
            'maintenance' => 'maintenance',
            'invoices' => 'invoices',
            'certificates' => 'certificates',
            'contracts' => 'contracts',
            'correspondence' => 'correspondence',
            'technical' => 'technical',
            'photos' => 'photos',
        ];

        // Lade alle DocumentTypes
        $documentTypes = DocumentType::all()->keyBy('key');

        // Aktualisiere alle Dokumente mit bestehenden Kategorien
        foreach ($categoryMapping as $oldCategory => $documentTypeKey) {
            if (isset($documentTypes[$documentTypeKey])) {
                Document::where('category', $oldCategory)
                    ->whereNull('document_type_id')
                    ->update(['document_type_id' => $documentTypes[$documentTypeKey]->id]);
            }
        }

        // Für Dokumente ohne Kategorie, setze einen Standard-DocumentType (falls vorhanden)
        $defaultDocumentType = $documentTypes->get('technical'); // Technische Unterlagen als Standard
        if ($defaultDocumentType) {
            Document::whereNull('category')
                ->whereNull('document_type_id')
                ->update(['document_type_id' => $defaultDocumentType->id]);
        }
    }

    public function down(): void
    {
        // Entferne alle document_type_id Verknüpfungen
        Document::whereNotNull('document_type_id')
            ->update(['document_type_id' => null]);
    }
};
