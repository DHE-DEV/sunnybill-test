<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Benutzer-Suche für @mentions
Route::get('/users/search', function (Request $request) {
    $query = $request->get('q', '');
    
    if (empty($query)) {
        return response()->json([]);
    }
    
    $users = User::where('name', 'LIKE', "%{$query}%")
        ->select('id', 'name', 'email')
        ->orderBy('name')
        ->limit(10)
        ->get();
    
    return response()->json($users);
});

// Alle Benutzer für @mentions
Route::get('/users/all', function (Request $request) {
    $users = User::select('id', 'name', 'email')
        ->orderBy('name')
        ->get();
    
    return response()->json($users);
});

// App-Token API Routes
Route::prefix('app')->middleware('app_token')->group(function () {
    // Profil-Informationen
    Route::get('/profile', [App\Http\Controllers\Api\TaskApiController::class, 'profile']);
    
    // Aufgaben-Management
    Route::prefix('tasks')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\TaskApiController::class, 'index'])->middleware('app_token:tasks:read');
        Route::post('/', [App\Http\Controllers\Api\TaskApiController::class, 'store'])->middleware('app_token:tasks:create');
        Route::get('/{task}', [App\Http\Controllers\Api\TaskApiController::class, 'show'])->middleware('app_token:tasks:read');
        Route::put('/{task}', [App\Http\Controllers\Api\TaskApiController::class, 'update'])->middleware('app_token:tasks:update');
        Route::delete('/{task}', [App\Http\Controllers\Api\TaskApiController::class, 'destroy'])->middleware('app_token:tasks:delete');
        
        // Spezielle Aktionen
        Route::patch('/{task}/status', [App\Http\Controllers\Api\TaskApiController::class, 'updateStatus'])->middleware('app_token:tasks:status');
        Route::patch('/{task}/assign', [App\Http\Controllers\Api\TaskApiController::class, 'assign'])->middleware('app_token:tasks:assign');
        Route::patch('/{task}/time', [App\Http\Controllers\Api\TaskApiController::class, 'updateTime'])->middleware('app_token:tasks:time');
        
        // Unteraufgaben
        Route::get('/{task}/subtasks', [App\Http\Controllers\Api\TaskApiController::class, 'subtasks'])->middleware('app_token:tasks:read');
    });
    
    // Dropdown-Daten
    Route::get('/users', [App\Http\Controllers\Api\TaskApiController::class, 'users'])->middleware('app_token:tasks:read');
    Route::get('/customers', [App\Http\Controllers\Api\TaskApiController::class, 'customers'])->middleware('app_token:tasks:read');
    Route::get('/suppliers', [App\Http\Controllers\Api\TaskApiController::class, 'suppliers'])->middleware('app_token:tasks:read');
    Route::get('/solar-plants', [App\Http\Controllers\Api\TaskApiController::class, 'solarPlants'])->middleware('app_token:tasks:read');
    Route::get('/options', [App\Http\Controllers\Api\TaskApiController::class, 'options'])->middleware('app_token:tasks:read');
});
