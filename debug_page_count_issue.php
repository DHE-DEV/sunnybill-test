<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\SolarPlantBillingPdfService;
use App\Models\SolarPlantBilling;
use App\Models\CompanySetting;

// Load Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "=== Debug Page Count Issue (Off by 1) ===\n\n";
    
    $billingId = '0198bc55-6c51-70c9-8613-e7cce37832ff';
    
    $billing = SolarPlantBilling::find($billingId);
    $companySetting = CompanySetting::first();
    
    // Create debug version to test exact page counting
    $pdfService = new class extends SolarPlantBillingPdfService {
        public function debugExactPageCount(SolarPlantBilling $billing, CompanySetting $companySetting): array
        {
            // Generate PDF first pass
            $data = $this->preparePdfData($billing, $companySetting);
            $data['totalPages'] = 0;
            
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.solar-plant-billing', $data);
            $this->configurePdf($pdf);
            
            $tempPdfContent = $pdf->output();
            
            echo "📄 PDF Content Length: " . strlen($tempPdfContent) . " bytes\n\n";
            
            // Method 1: Count /Type /Page objects
            $pageTypeCount = substr_count($tempPdfContent, '/Type /Page');
            echo "Method 1 - substr_count('/Type /Page'): {$pageTypeCount}\n";
            
            // Method 2: More specific page object counting
            $pageObjCount = preg_match_all('/\d+\s+0\s+obj\s*<<[^>]*\/Type\s*\/Page[^>]*>>/', $tempPdfContent, $matches);
            echo "Method 2 - Page object regex: {$pageObjCount}\n";
            
            // Method 3: Count pages in Kids array
            if (preg_match('/\/Kids\s*\[([^\]]*)\]/', $tempPdfContent, $kidsMatch)) {
                $kidsContent = $kidsMatch[1];
                $pageRefs = preg_match_all('/(\d+)\s+0\s+R/', $kidsContent, $refMatches);
                echo "Method 3 - Kids array references: {$pageRefs}\n";
                echo "Kids content: " . trim($kidsContent) . "\n";
            }
            
            // Method 4: Check Pages object Count
            if (preg_match('/\/Type\s*\/Pages[^}]*\/Count\s*(\d+)/', $tempPdfContent, $countMatch)) {
                $pagesCount = (int) $countMatch[1];
                echo "Method 4 - Pages /Count: {$pagesCount}\n";
            }
            
            // Let's examine the actual structure around /Type /Page
            echo "\n=== All /Type /Page occurrences ===\n";
            $offset = 0;
            $occurrence = 1;
            while (($pos = strpos($tempPdfContent, '/Type /Page', $offset)) !== false) {
                $start = max(0, $pos - 50);
                $end = min(strlen($tempPdfContent), $pos + 100);
                $context = substr($tempPdfContent, $start, $end - $start);
                echo "Occurrence {$occurrence}: " . str_replace(["\n", "\r"], [" ", ""], $context) . "\n";
                $offset = $pos + 1;
                $occurrence++;
            }
            
            // Test the corrected counting method
            $correctedCount = $this->extractCorrectedPageCount($tempPdfContent);
            echo "\n🎯 Corrected page count: {$correctedCount}\n";
            
            return [
                'substr_count' => $pageTypeCount,
                'page_obj_regex' => $pageObjCount,
                'kids_refs' => $pageRefs ?? 0,
                'pages_count' => $pagesCount ?? 0,
                'corrected' => $correctedCount
            ];
        }
        
        private function extractCorrectedPageCount(string $pdfContent): int
        {
            // Use the Kids array method which should be most accurate
            if (preg_match('/\/Type\s*\/Pages[^}]*\/Count\s*(\d+)/', $pdfContent, $matches)) {
                return (int) $matches[1];
            }
            
            // Fallback: count Kids array entries
            if (preg_match('/\/Kids\s*\[([^\]]*)\]/', $pdfContent, $kidsMatch)) {
                $kidsContent = $kidsMatch[1];
                $pageRefs = preg_match_all('/(\d+)\s+0\s+R/', $kidsContent, $refMatches);
                if ($pageRefs > 0) {
                    return $pageRefs;
                }
            }
            
            // Last resort: subtract 1 from Type /Page count (may include root page)
            $typePageCount = substr_count($pdfContent, '/Type /Page');
            return max(1, $typePageCount - 1);
        }
        
        public function preparePdfData($billing, $companySetting): array
        {
            return parent::preparePdfData($billing, $companySetting);
        }
        
        public function configurePdf($pdf): void
        {
            parent::configurePdf($pdf);
        }
    };
    
    $debugResults = $pdfService->debugExactPageCount($billing, $companySetting);
    
    echo "\n=== Summary ===\n";
    foreach ($debugResults as $method => $count) {
        echo "{$method}: {$count}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
