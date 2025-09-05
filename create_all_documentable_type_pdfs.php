<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

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
    ['key' => 'direct_marketing_invoice', 'name' => 'Direktvermarktung Rechnung - Gutschrift'],
    ['key' => 'planing', 'name' => 'Protokoll'],
];

// Documentable types (excluding Customer which is already done)
$documentableTypes = [
    'Solaranlage' => 'App\\Models\\SolarPlant',
    'Aufgabe' => 'App\\Models\\Task', 
    'Rechnung' => 'App\\Models\\Invoice',
    'Lieferant' => 'App\\Models\\Supplier',
];

// Create directory if it doesn't exist
$pdfDir = 'public/testpdf';
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0755, true);
}

$totalSuccess = 0;
$totalFiles = count($documentableTypes) * count($documentTypes);

echo "Creating PDF files for all documentable types...\n";
echo "==================================================\n";
echo "Document types per entity: " . count($documentTypes) . "\n";
echo "Total entities: " . count($documentableTypes) . "\n";
echo "Total files to create: {$totalFiles}\n\n";

foreach ($documentableTypes as $entityGerman => $entityClass) {
    echo "Creating PDFs for: {$entityGerman} ({$entityClass})\n";
    echo str_repeat('-', 50) . "\n";
    
    $successCount = 0;
    
    foreach ($documentTypes as $docType) {
        // Sanitize filename by replacing problematic characters
        $sanitizedDocTypeName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $docType['name']);
        $filename = "{$entityGerman}_{$sanitizedDocTypeName}.pdf";
        $filepath = "{$pdfDir}/{$filename}";
        
        // Create a simple PDF content using basic PDF structure
        $pdfContent = "%PDF-1.4\n";
        $pdfContent .= "1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n\n";
        $pdfContent .= "2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n\n";
        $pdfContent .= "3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n/Resources <<\n/Font <<\n/F1 5 0 R\n>>\n>>\n>>\nendobj\n\n";
        $pdfContent .= "4 0 obj\n<<\n/Length 250\n>>\nstream\nBT\n/F1 12 Tf\n50 750 Td\n({$entityGerman} - {$docType['name']}) Tj\n0 -20 Td\n(Dokumenttyp: {$docType['key']}) Tj\n0 -20 Td\n(Erstellt: " . date('d.m.Y H:i:s') . ") Tj\n0 -40 Td\n(Dies ist eine Test-PDF-Datei) Tj\n0 -20 Td\n(fuer {$entityGerman}: {$docType['name']}) Tj\n0 -20 Td\n(Model: {$entityClass}) Tj\nET\nendstream\nendobj\n\n";
        $pdfContent .= "5 0 obj\n<<\n/Type /Font\n/Subtype /Type1\n/BaseFont /Helvetica\n>>\nendobj\n\n";
        $pdfContent .= "xref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000274 00000 n \n0000000576 00000 n \n";
        $pdfContent .= "trailer\n<<\n/Size 6\n/Root 1 0 R\n>>\nstartxref\n675\n%%EOF";
        
        // Write PDF file
        if (file_put_contents($filepath, $pdfContent)) {
            echo "✓ Created: {$filename}\n";
            $successCount++;
            $totalSuccess++;
        } else {
            echo "✗ Failed to create: {$filename}\n";
        }
    }
    
    echo "Completed {$entityGerman}: {$successCount}/" . count($documentTypes) . " files created\n\n";
}

echo "==================================================\n";
echo "Final Summary:\n";
echo "- Total files to create: {$totalFiles}\n";
echo "- PDFs created successfully: {$totalSuccess}\n";
echo "- PDFs saved in: {$pdfDir}/\n";

if ($totalSuccess === $totalFiles) {
    echo "\n✅ All PDF files created successfully!\n";
} else {
    echo "\n❌ Some PDF files failed to create.\n";
}

// List all PDF files in the directory
echo "\nAll PDF files in {$pdfDir}:\n";
$pdfFiles = glob("{$pdfDir}/*.pdf");
sort($pdfFiles);
foreach ($pdfFiles as $file) {
    echo "- " . basename($file) . "\n";
}
echo "\nTotal PDF files: " . count($pdfFiles) . "\n";
