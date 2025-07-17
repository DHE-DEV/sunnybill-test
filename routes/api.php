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
Route::middleware('auth:sanctum')->get('/users/search', function (Request $request) {
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
Route::middleware('auth:sanctum')->get('/users/all', function (Request $request) {
    $users = User::select('id', 'name', 'email')
        ->orderBy('name')
        ->get();
    
    return response()->json($users);
});
