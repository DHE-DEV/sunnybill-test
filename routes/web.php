<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InfolistStateController;

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

// Login route for authentication redirects
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Über Uns Seite
Route::get('/ueber-uns', function () {
    return view('ueber-uns');
})->name('ueber-uns');

// Datenschutz Seite
Route::get('/datenschutz', function () {
    return view('datenschutz');
})->name('datenschutz');

// Impressum Seite
Route::get('/impressum', function () {
    return view('impressum');
})->name('impressum');

// Nutzungsbedingungen Seite
Route::get('/nutzungsbedingungen', function () {
    return view('nutzungsbedingungen');
})->name('nutzungsbedingungen');

// API Leads Dokumentation
Route::get('/api-leads', function () {
    return view('api-leads');
})->name('api-leads');

// Infolist State Management
Route::post('/api/infolist-state', [InfolistStateController::class, 'save'])
    ->name('infolist.state.save')
    ->middleware('auth');

// Document routes
Route::get('/documents/{document}/preview', [\App\Http\Controllers\DocumentController::class, 'preview'])
    ->name('documents.preview')
    ->middleware('auth');

Route::get('/documents/{document}/download', [\App\Http\Controllers\DocumentController::class, 'download'])
    ->name('documents.download')
    ->middleware('auth');

// Bulk PDF download route
Route::get('/admin/download-bulk-pdfs', [App\Http\Controllers\BulkPdfDownloadController::class, 'index'])
    ->name('admin.download-bulk-pdfs')
    ->middleware('auth');

// QR-Code print route for solar plant billing
Route::get('/admin/solar-plant-billing/{solarPlantBilling}/qr-code-print', [App\Http\Controllers\SolarPlantBillingController::class, 'printQrCode'])
    ->name('admin.solar-plant-billing.qr-code-print')
    ->middleware('auth');

// Remove conflicting route - let L5-Swagger handle its own routes
