<?php

require_once 'vendor/autoload.php';

use App\Models\Document;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debug: Dokument-Details ===\n\n";

try {
    // Hole alle Dokumente
    $documents = Document::all();
    
    echo "Alle Dokumente in der Datenbank:\n";
    foreach ($documents as $doc) {
        echo "ID: " . $doc->id . "\n";
        echo "Name: " . ($doc->name ?? 'NULL') . "\n";
        echo "Original Name: " . ($doc->original_name ?? 'NULL') . "\n";
        echo "Pfad: " . ($doc->path ?? 'NULL') . "\n";
        echo "Disk: " . ($doc->disk ?? 'NULL') . "\n";
        echo "MIME Type: " . ($doc->mime_type ?? 'NULL') . "\n";
        echo "Größe: " . ($doc->size ?? 'NULL') . "\n";
        echo "Kategorie: " . ($doc->category ?? 'NULL') . "\n";
        echo "Documentable Type: " . ($doc->documentable_type ?? 'NULL') . "\n";
        echo "Documentable ID: " . ($doc->documentable_id ?? 'NULL') . "\n";
        echo "Uploaded By: " . ($doc->uploaded_by ?? 'NULL') . "\n";
        echo "Created At: " . ($doc->created_at ?? 'NULL') . "\n";
        echo "Updated At: " . ($doc->updated_at ?? 'NULL') . "\n";
        echo "---\n";
    }
    
    // Prüfe spezifisch nach Dokumenten mit supplier_contracts-documents Pfad
    echo "\nDokumente mit 'supplier_contracts-documents' im Pfad:\n";
    $oldPathDocs = Document::where('path', 'like', '%supplier_contracts-documents%')->get();
    
    if ($oldPathDocs->isEmpty()) {
        echo "Keine Dokumente mit altem Pfad gefunden.\n";
    } else {
        foreach ($oldPathDocs as $doc) {
            echo "ID: " . $doc->id . " - Pfad: " . $doc->path . "\n";
        }
    }
    
    // Prüfe nach Dokumenten mit leerem oder NULL Pfad
    echo "\nDokumente mit leerem oder NULL Pfad:\n";
    $emptyPathDocs = Document::where(function($query) {
        $query->whereNull('path')->orWhere('path', '');
    })->get();
    
    foreach ($emptyPathDocs as $doc) {
        echo "ID: " . $doc->id . " - Name: " . ($doc->name ?? 'NULL') . " - Documentable: " . $doc->documentable_type . "\n";
    }
    
    echo "\n✅ Debug abgeschlossen!\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}