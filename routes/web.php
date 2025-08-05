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

// API Documentation JSON Route
Route::get('/docs', function () {
    $jsonPath = public_path('api-docs/api-docs.json');
    if (!file_exists($jsonPath)) {
        abort(404, 'API Documentation not found');
    }
    
    return response()->file($jsonPath, [
        'Content-Type' => 'application/json',
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET',
        'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Authorization'
    ]);
})->name('api.docs.json');
