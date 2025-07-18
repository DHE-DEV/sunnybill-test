<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Über uns Seite
Route::get('/ueber-uns', function () {
    return view('ueber-uns');
})->name('ueber-uns');

// Document Download Route
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
Route::get('/documents/{document}/view', [DocumentController::class, 'view'])->name('documents.view');

// OpenAPI Dokumentation
Route::get('/api-docs', function () {
    return view('api-docs.index');
});

Route::get('/api-docs/openapi.yaml', function () {
    $apiUrl = config('app.api_url', config('app.url'));
    
    $yaml = file_get_contents(public_path('api-docs/openapi.yaml'));
    
    // Ersetze die Server-URLs mit der dynamischen API_URL
    $yaml = str_replace([
        'https://sunnybill.de/api',
        'https://voltmaster.cloud/api',
        'https://prosoltec.voltmaster.cloud/api',
        'http://localhost:8000/api'
    ], [
        $apiUrl . '/api',
        $apiUrl . '/api',
        $apiUrl . '/api',
        config('app.url') . '/api'
    ], $yaml);
    
    // Ersetze auch die URLs in den Beispielen
    $yaml = str_replace([
        'https://sunnybill.de/api',
        'support@sunnybill.de',
        'SunnyBill',
        'sunnybill.de'
    ], [
        $apiUrl . '/api',
        config('mail.from.address', 'support@voltmaster.cloud'),
        'VoltMaster',
        'voltmaster.cloud'
    ], $yaml);
    
    return response($yaml, 200, [
        'Content-Type' => 'application/x-yaml'
    ]);
});
