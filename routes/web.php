<?php

use Illuminate\Support\Facades\Route;
use App\Models\InvoiceVersion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return view('welcome');
});

// Route für die mobile App
Route::get('/app/{any?}', function () {
    return view('mobile-app');
})->where('any', '.*')->name('mobile.app');

// Route für die einfache mobile App (ohne Template-String-Probleme)
Route::get('/app-simple', function () {
    return view('mobile-app-simple');
})->name('mobile.app.simple');

// Route für PDF-Download von Rechnungsversionen
Route::get('/invoice-version/{invoiceVersion}/pdf', function (InvoiceVersion $invoiceVersion) {
    // Erstelle eine temporäre Rechnung basierend auf den gespeicherten Daten
    $invoiceData = $invoiceVersion->invoice_data;
    $customerData = $invoiceVersion->customer_data;
    $itemsData = $invoiceVersion->items_data;
    
    // Erstelle ein Objekt mit den notwendigen Daten für die PDF-Generierung
    $record = (object) [
        'id' => $invoiceData['id'],
        'invoice_number' => $invoiceData['invoice_number'],
        'status' => $invoiceData['status'],
        'total' => $invoiceData['total'],
        'due_date' => $invoiceData['due_date'] ? \Carbon\Carbon::parse($invoiceData['due_date']) : null,
        'created_at' => $invoiceData['created_at'] ? \Carbon\Carbon::parse($invoiceData['created_at']) : null,
        'customer' => $customerData ? (object) $customerData : null,
        'items' => collect($itemsData)->map(function ($item) {
            // Bestimme Nachkommastellen basierend auf Artikel-Daten
            $decimalPlaces = $item['article_data']['decimal_places'] ?? 2;
            $totalDecimalPlaces = $item['article_data']['total_decimal_places'] ?? 2;
            
            return (object) [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
                'total' => $item['total'],
                'formatted_unit_price' => number_format($item['unit_price'], $decimalPlaces, ',', '.') . ' €',
                'formatted_total' => number_format($item['total'], $totalDecimalPlaces, ',', '.') . ' €',
                // Artikel-Daten aus den gespeicherten Daten
                'article' => $item['article_data'] ? (object) $item['article_data'] : null,
                'decimal_places' => $decimalPlaces,
                'total_decimal_places' => $totalDecimalPlaces,
            ];
        }),
        'tax_amount' => collect($itemsData)->sum(function ($item) {
            return $item['total'] * $item['tax_rate'] / (1 + $item['tax_rate']);
        }),
        'net_amount' => $invoiceData['total'] - collect($itemsData)->sum(function ($item) {
            return $item['total'] * $item['tax_rate'] / (1 + $item['tax_rate']);
        }),
        'formatted_total' => number_format($invoiceData['total'], 2, ',', '.') . ' €',
        'formatted_tax_amount' => number_format(collect($itemsData)->sum(function ($item) {
            return $item['total'] * $item['tax_rate'] / (1 + $item['tax_rate']);
        }), 2, ',', '.') . ' €',
        'formatted_net_amount' => number_format($invoiceData['total'] - collect($itemsData)->sum(function ($item) {
            return $item['total'] * $item['tax_rate'] / (1 + $item['tax_rate']);
        }), 2, ',', '.') . ' €',
    ];
    
    $pdf = Pdf::loadView('invoices.pdf', compact('record'));
    
    return Response::streamDownload(
        fn () => print($pdf->output()),
        "rechnung-{$record->invoice_number}-version-{$invoiceVersion->version_number}.pdf"
    );
})->name('invoice.pdf.version');
