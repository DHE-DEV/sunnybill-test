<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\DocumentType;

// Document types data from the previous script
$documentTypes = [
    ['key' => 'planning', 'name' => 'Planung'],
    ['key' => 'permits', 'name' => 'Genehmigung'],
    ['key' => 'installation', 'name' => 'Installation'],
    ['key' => 'maintenance', 'name' => 'Wartung'],
    ['key' => 'invoices', 'name' => 'Rechnung'],
    ['key' => 'certificates', 'name' => 'Zertifikat'],
    ['key' => 'contracts', 'name' => 'Vertrag'],
    ['key' => 'correspondence', 'name' => 'Korrespondenz'],
    ['key' => 'technical', 'name' => 'Technische Unterlagen'],
    ['key' => 'photos', 'name' => 'Fotos'],
    ['key' => 'abr_marktpraemie', 'name' => 'Marktprämie Abrechnung'],
    ['key' => 'abr_direktvermittlung', 'name' => 'Direktvermittlung Abrechnung'],
    ['key' => 'test_protocol', 'name' => 'Prüfprotokoll'],
    ['key' => 'ordering_material', 'name' => 'Materialbestellung'],
    ['key' => 'delivery_note', 'name' => 'Lieferschein'],
    ['key' => 'commissioning', 'name' => 'Inbetriebnahme'],
    ['key' => 'legal_document', 'name' => 'Rechtsdokument'],
    ['key' => 'formulare', 'name' => 'Formulare'],
    ['key' => 'information', 'name' => 'Information'],
    ['key' => 'direct_marketing_invoice', 'name' => 'Direktvermarktung Rechnung / Gutschrift'],
    ['key' => 'planing', 'name' => 'Protokoll'],
];

// Create directory if it doesn't exist
$pdfDir = 'public/testpdf';
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0755, true);
}

echo "Creating PDF files for Customer document types...\n";
echo "==================================================\n\n";

$successCount = 0;

foreach ($documentTypes as $docType) {
    $filename = "Kunde_{$docType['name']}.pdf";
    $filepath = "{$pdfDir}/{$filename}";
    
    // Create a simple PDF content using basic PDF structure
    $pdfContent = "%PDF-1.4\n";
    $pdfContent .= "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n\n";
    $pdfContent .= "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n\n";
    $pdfContent .= "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n>>\n>>\n>>\nendobj\n\n";
    $pdfContent .= "4 0 obj\n<<\n/Length 200\n>>\nstream\nBT\n/F1 12 Tf\n50 750 Td\n(Kunde - {$docType['name']}) Tj\n0 -20 Td\n(Dokumenttyp: {$docType['key']}) Tj\n0 -20 Td\n(Erstellt: " . date('d.m.Y H:i:s') . ") Tj\n0 -40 Td\n(Dies ist eine Test-PDF-Datei) Tj\n0 -20 Td\n(fuer den Dokumenttyp: {$docType['name']}) Tj\nET\nendstream\nendobj\n\n";
    $pdfContent .= "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n\n";
    $pdfContent .= "xref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000274 00000 n \n0000000526 00000 n \n";
    $pdfContent .= "trailer\n<<\n/Size 6\n/Root 1 0 R\n>>\nstartxref\n625\n%%EOF";
    
    // Write PDF file
    if (file_put_contents($filepath, $pdfContent)) {
        echo "✓ Created: {$filename}\n";
        $successCount++;
    } else {
        echo "✗ Failed to create: {$filename}\n";
    }
}

echo "\n==================================================\n";
echo "Summary:\n";
echo "- Total document types: " . count($documentTypes) . "\n";
echo "- PDFs created successfully: {$successCount}\n";
echo "- PDFs saved in: {$pdfDir}/\n";

if ($successCount === count($documentTypes)) {
    echo "\n✅ All PDF files created successfully!\n";
} else {
    echo "\n❌ Some PDF files failed to create.\n";
}
