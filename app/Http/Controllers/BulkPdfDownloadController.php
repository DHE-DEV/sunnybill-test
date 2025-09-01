<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BulkPdfDownloadController extends Controller
{
    public function index(Request $request)
    {
        $downloads = session('bulk_pdf_downloads', []);
        $successCount = session('bulk_pdf_success_count', 0);
        $errorCount = session('bulk_pdf_error_count', 0);
        $batchId = session('bulk_pdf_batch_id');

        // Clear session data after loading
        session()->forget(['bulk_pdf_downloads', 'bulk_pdf_success_count', 'bulk_pdf_error_count', 'bulk_pdf_batch_id']);

        return view('bulk-pdf-download', compact('downloads', 'successCount', 'errorCount', 'batchId'));
    }
}
