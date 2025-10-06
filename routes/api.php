<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\Api\HealthController;

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

// Health Check Endpoints - No authentication required
Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'index']);
    Route::get('/simple', [HealthController::class, 'simple']);
    Route::get('/ready', [HealthController::class, 'ready']);
    Route::get('/live', [HealthController::class, 'live']);
});

// Router Webhook Endpoints - No authentication required (for Teltonika routers)
Route::post('/webhook', [App\Http\Controllers\Api\RouterWebhookController::class, 'webhook']);
Route::post('/router-webhook/{token}', [App\Http\Controllers\Api\RouterWebhookController::class, 'routerWebhook']);
Route::get('/api/status', [App\Http\Controllers\Api\RouterWebhookController::class, 'status']);
Route::get('/api/test-curl', [App\Http\Controllers\Api\RouterWebhookController::class, 'testCurl']);

// Router Restart Endpoints - Protected for admin use
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/routers/{router}/restart', [App\Http\Controllers\RouterRestartController::class, 'restart']);
    Route::get('/routers/{router}/restart-status', [App\Http\Controllers\RouterRestartController::class, 'status']);
});

// Notifications API - Protected endpoints
Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/count', [App\Http\Controllers\Api\NotificationController::class, 'count']);
    Route::get('/', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::patch('/{notification}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::patch('/mark-all-read', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
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
    
    // Logout
    Route::post('/logout', [App\Http\Controllers\Api\TaskApiController::class, 'logout']);
    
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
    
    // Solaranlagen-Management
    Route::prefix('solar-plants')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\SolarPlantApiController::class, 'index'])->middleware('app_token:solar-plants:read');
        Route::post('/', [App\Http\Controllers\Api\SolarPlantApiController::class, 'store'])->middleware('app_token:solar-plants:create');
        Route::get('/{solarPlant}', [App\Http\Controllers\Api\SolarPlantApiController::class, 'show'])->middleware('app_token:solar-plants:read');
        Route::put('/{solarPlant}', [App\Http\Controllers\Api\SolarPlantApiController::class, 'update'])->middleware('app_token:solar-plants:update');
        Route::delete('/{solarPlant}', [App\Http\Controllers\Api\SolarPlantApiController::class, 'destroy'])->middleware('app_token:solar-plants:delete');
        
        // Zusätzliche Endpoints
        Route::get('/{solarPlant}/components', [App\Http\Controllers\Api\SolarPlantApiController::class, 'components'])->middleware('app_token:solar-plants:read');
        Route::get('/{solarPlant}/participations', [App\Http\Controllers\Api\SolarPlantApiController::class, 'participations'])->middleware('app_token:solar-plants:read');
        Route::get('/{solarPlant}/monthly-results', [App\Http\Controllers\Api\SolarPlantApiController::class, 'monthlyResults'])->middleware('app_token:solar-plants:read');
        Route::get('/{solarPlant}/statistics', [App\Http\Controllers\Api\SolarPlantApiController::class, 'statistics'])->middleware('app_token:solar-plants:read');
    });
    
    // Kunden-Management
    Route::prefix('customers')->group(function () {
        Route::get('/test', [App\Http\Controllers\Api\CustomerApiController::class, 'test'])->middleware('app_token:customers:read');
        Route::get('/debug', [App\Http\Controllers\Api\CustomerApiController::class, 'debug'])->middleware('app_token:customers:read');
        Route::get('/debug-index', [App\Http\Controllers\Api\CustomerApiController::class, 'debugIndex'])->middleware('app_token:customers:read');
        Route::get('/raw', [App\Http\Controllers\Api\CustomerApiController::class, 'raw'])->middleware('app_token:customers:read');
        Route::get('/show-sql', [App\Http\Controllers\Api\CustomerApiController::class, 'showSql'])->middleware('app_token:customers:read');
        Route::get('/', [App\Http\Controllers\Api\CustomerApiController::class, 'index'])->middleware('app_token:customers:read');
        Route::post('/', [App\Http\Controllers\Api\CustomerApiController::class, 'store'])->middleware('app_token:customers:create');
        Route::get('/{customer}', [App\Http\Controllers\Api\CustomerApiController::class, 'show'])->middleware('app_token:customers:read');
        Route::put('/{customer}', [App\Http\Controllers\Api\CustomerApiController::class, 'update'])->middleware('app_token:customers:update');
        Route::delete('/{customer}', [App\Http\Controllers\Api\CustomerApiController::class, 'destroy'])->middleware('app_token:customers:delete');
        
        // Spezielle Aktionen
        Route::patch('/{customer}/status', [App\Http\Controllers\Api\CustomerApiController::class, 'updateStatus'])->middleware('app_token:customers:status');
        
        // Zusätzliche Endpoints
        Route::get('/{customer}/participations', [App\Http\Controllers\Api\CustomerApiController::class, 'participations'])->middleware('app_token:customers:read');
        Route::get('/{customer}/projects', [App\Http\Controllers\Api\CustomerApiController::class, 'projects'])->middleware('app_token:customers:read');
        Route::get('/{customer}/tasks', [App\Http\Controllers\Api\CustomerApiController::class, 'tasks'])->middleware('app_token:customers:read');
        Route::get('/{customer}/financials', [App\Http\Controllers\Api\CustomerApiController::class, 'financials'])->middleware('app_token:customers:read');
    });
    
    // Lead-Management
    Route::prefix('leads')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\LeadApiController::class, 'index'])->middleware('app_token:leads:read');
        Route::post('/', [App\Http\Controllers\Api\LeadApiController::class, 'store'])->middleware('app_token:leads:create');
        Route::get('/{lead}', [App\Http\Controllers\Api\LeadApiController::class, 'show'])->middleware('app_token:leads:read');
        Route::put('/{lead}', [App\Http\Controllers\Api\LeadApiController::class, 'update'])->middleware('app_token:leads:update');
        Route::delete('/{lead}', [App\Http\Controllers\Api\LeadApiController::class, 'destroy'])->middleware('app_token:leads:delete');
        
        // Spezielle Aktionen
        Route::patch('/{lead}/status', [App\Http\Controllers\Api\LeadApiController::class, 'updateStatus'])->middleware('app_token:leads:status');
        Route::patch('/{lead}/convert-to-customer', [App\Http\Controllers\Api\LeadApiController::class, 'convertToCustomer'])->middleware('app_token:leads:convert');
        
        // API-Optionen
        Route::get('/options', [App\Http\Controllers\Api\LeadApiController::class, 'options'])->middleware('app_token:leads:read');
    });
    
    // Lieferanten-Management
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\SupplierApiController::class, 'index'])->middleware('app_token:suppliers:read');
        Route::post('/', [App\Http\Controllers\Api\SupplierApiController::class, 'store'])->middleware('app_token:suppliers:create');
        Route::get('/{supplier}', [App\Http\Controllers\Api\SupplierApiController::class, 'show'])->middleware('app_token:suppliers:read');
        Route::put('/{supplier}', [App\Http\Controllers\Api\SupplierApiController::class, 'update'])->middleware('app_token:suppliers:update');
        Route::delete('/{supplier}', [App\Http\Controllers\Api\SupplierApiController::class, 'destroy'])->middleware('app_token:suppliers:delete');

        // Spezielle Aktionen
        Route::patch('/{supplier}/status', [App\Http\Controllers\Api\SupplierApiController::class, 'updateStatus'])->middleware('app_token:suppliers:status');

        // Zusätzliche Endpoints
        Route::get('/{supplier}/contracts', [App\Http\Controllers\Api\SupplierApiController::class, 'contracts'])->middleware('app_token:suppliers:read');
        Route::get('/{supplier}/projects', [App\Http\Controllers\Api\SupplierApiController::class, 'projects'])->middleware('app_token:suppliers:read');
        Route::get('/{supplier}/tasks', [App\Http\Controllers\Api\SupplierApiController::class, 'tasks'])->middleware('app_token:suppliers:read');
        Route::get('/{supplier}/financials', [App\Http\Controllers\Api\SupplierApiController::class, 'financials'])->middleware('app_token:suppliers:read');
        Route::get('/{supplier}/performance', [App\Http\Controllers\Api\SupplierApiController::class, 'performance'])->middleware('app_token:suppliers:read');
    });

    // Vertragssuche
    Route::post('/contracts/search', [App\Http\Controllers\Api\SupplierApiController::class, 'searchContractsByIdentifiers'])->middleware('app_token:suppliers:read');
    
    // Projekt-Management
    Route::prefix('projects')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ProjectApiController::class, 'index'])->middleware('app_token:projects:read');
        Route::post('/', [App\Http\Controllers\Api\ProjectApiController::class, 'store'])->middleware('app_token:projects:create');
        Route::get('/{project}', [App\Http\Controllers\Api\ProjectApiController::class, 'show'])->middleware('app_token:projects:read');
        Route::put('/{project}', [App\Http\Controllers\Api\ProjectApiController::class, 'update'])->middleware('app_token:projects:update');
        Route::delete('/{project}', [App\Http\Controllers\Api\ProjectApiController::class, 'destroy'])->middleware('app_token:projects:delete');
        
        // Spezielle Aktionen
        Route::patch('/{project}/status', [App\Http\Controllers\Api\ProjectApiController::class, 'updateStatus'])->middleware('app_token:projects:status');
        Route::get('/{project}/progress', [App\Http\Controllers\Api\ProjectApiController::class, 'progress'])->middleware('app_token:projects:read');
        Route::patch('/{project}/progress', [App\Http\Controllers\Api\ProjectApiController::class, 'updateProgress'])->middleware('app_token:projects:update');
        
        // Projekt-Meilensteine
        Route::get('/{project}/milestones', [App\Http\Controllers\Api\ProjectMilestoneApiController::class, 'indexByProject'])->middleware('app_token:milestones:read');
        Route::post('/{project}/milestones', [App\Http\Controllers\Api\ProjectMilestoneApiController::class, 'store'])->middleware('app_token:milestones:create');
        
        // Projekt-Termine
        Route::get('/{project}/appointments', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'indexByProject'])->middleware('app_token:appointments:read');
        Route::post('/{project}/appointments', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'store'])->middleware('app_token:appointments:create');
    });
    
    // Projektmeilensteine (projektübergreifend)
    Route::prefix('project-milestones')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ProjectMilestoneApiController::class, 'index'])->middleware('app_token:milestones:read');
        Route::get('/{projectMilestone}', [App\Http\Controllers\Api\ProjectMilestoneApiController::class, 'show'])->middleware('app_token:milestones:read');
        Route::put('/{projectMilestone}', [App\Http\Controllers\Api\ProjectMilestoneApiController::class, 'update'])->middleware('app_token:milestones:update');
        Route::delete('/{projectMilestone}', [App\Http\Controllers\Api\ProjectMilestoneApiController::class, 'destroy'])->middleware('app_token:milestones:delete');
        
        // Spezielle Aktionen
        Route::patch('/{projectMilestone}/status', [App\Http\Controllers\Api\ProjectMilestoneApiController::class, 'updateStatus'])->middleware('app_token:milestones:status');
        Route::patch('/{projectMilestone}/progress', [App\Http\Controllers\Api\ProjectMilestoneApiController::class, 'updateProgress'])->middleware('app_token:milestones:update');
    });
    
    // Projekttermine (projektübergreifend)
    Route::prefix('project-appointments')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'index'])->middleware('app_token:appointments:read');
        Route::get('/upcoming', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'upcoming'])->middleware('app_token:appointments:read');
        Route::get('/calendar', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'calendar'])->middleware('app_token:appointments:read');
        Route::get('/{projectAppointment}', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'show'])->middleware('app_token:appointments:read');
        Route::put('/{projectAppointment}', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'update'])->middleware('app_token:appointments:update');
        Route::delete('/{projectAppointment}', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'destroy'])->middleware('app_token:appointments:delete');
        
        // Spezielle Aktionen
        Route::patch('/{projectAppointment}/status', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'updateStatus'])->middleware('app_token:appointments:status');
    });
    
    // User-spezifische Telefonnummern (benutzerfreundliche API)
    Route::prefix('users/{userId}/phone-numbers')->name('api.users.phone-numbers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'getUserPhoneNumbers'])->name('index')->middleware('app_token:phone-numbers:read');
        Route::post('/', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'storeForUser'])->name('store')->middleware('app_token:phone-numbers:create');
        Route::get('/{id}', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'getUserPhoneNumber'])->name('show')->middleware('app_token:phone-numbers:read');
        Route::put('/{id}', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'updateUserPhoneNumber'])->name('update')->middleware('app_token:phone-numbers:update');
        Route::delete('/{id}', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'destroyUserPhoneNumber'])->name('destroy')->middleware('app_token:phone-numbers:delete');
        
        // Spezielle Aktionen für User-Telefonnummern
        Route::patch('/{id}/make-primary', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'makeUserPhoneNumberPrimary'])->name('make-primary')->middleware('app_token:phone-numbers:update');
    });
    
    // Allgemeine Telefonnummern-Management (für alle Entitäten)
    Route::prefix('phone-numbers')->name('api.phone-numbers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'index'])->name('index')->middleware('app_token:phone-numbers:read');
        Route::post('/', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'store'])->name('store')->middleware('app_token:phone-numbers:create');
        Route::get('/{id}', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'show'])->name('show')->middleware('app_token:phone-numbers:read');
        Route::put('/{id}', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'update'])->name('update')->middleware('app_token:phone-numbers:update');
        Route::delete('/{id}', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'destroy'])->name('destroy')->middleware('app_token:phone-numbers:delete');
        
        // Spezielle Aktionen
        Route::patch('/{id}/make-primary', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'makePrimary'])->name('make-primary')->middleware('app_token:phone-numbers:update');
    });
    
    // Telefonnummern nach Besitzer (allgemeine Lösung)
    Route::get('/owners/{phoneableType}/{phoneableId}/phone-numbers', [App\Http\Controllers\Api\PhoneNumberApiController::class, 'getByOwner'])
        ->name('api.phone-numbers.by-owner')
        ->middleware('app_token:phone-numbers:read');
    
    // Kosten-Management
    Route::prefix('costs')->group(function () {
        Route::get('/overview', [App\Http\Controllers\Api\CostApiController::class, 'overview'])->middleware('app_token:costs:read');
        Route::get('/reports', [App\Http\Controllers\Api\CostApiController::class, 'reports'])->middleware('app_token:costs:reports');
    });
    
    // Projektspezifische Kosten
    Route::get('/projects/{project}/costs', [App\Http\Controllers\Api\CostApiController::class, 'projectCosts'])->middleware('app_token:costs:read');
    Route::post('/projects/{project}/costs', [App\Http\Controllers\Api\CostApiController::class, 'addProjectCost'])->middleware('app_token:costs:create');
    
    // Solaranlagen-Kosten
    Route::get('/solar-plants/{solarPlant}/costs', [App\Http\Controllers\Api\CostApiController::class, 'solarPlantCosts'])->middleware('app_token:costs:read');
    Route::get('/solar-plants/{solarPlant}/billings', [App\Http\Controllers\Api\CostApiController::class, 'solarPlantBillings'])->middleware('app_token:costs:read');
    
    // Dropdown-Daten und Optionen
    Route::get('/users', [App\Http\Controllers\Api\TaskApiController::class, 'users'])->middleware('app_token:tasks:read');
    Route::get('/customers-dropdown', [App\Http\Controllers\Api\TaskApiController::class, 'customers'])->middleware('app_token:tasks:read');
    Route::get('/suppliers', [App\Http\Controllers\Api\TaskApiController::class, 'suppliers'])->middleware('app_token:tasks:read');
    Route::get('/solar-plants-dropdown', [App\Http\Controllers\Api\TaskApiController::class, 'solarPlants'])->middleware('app_token:tasks:read');
    
    // Task Types
    Route::get('/task-types', [App\Http\Controllers\Api\TaskApiController::class, 'taskTypes'])->middleware('app_token:tasks:read');
    
    // API-Optionen für verschiedene Bereiche
    Route::get('/options/tasks', [App\Http\Controllers\Api\TaskApiController::class, 'options'])->middleware('app_token:tasks:read');
    Route::get('/options/projects', [App\Http\Controllers\Api\ProjectApiController::class, 'options'])->middleware('app_token:projects:read');
    Route::get('/options/milestones', [App\Http\Controllers\Api\ProjectMilestoneApiController::class, 'options'])->middleware('app_token:milestones:read');
    Route::get('/options/appointments', [App\Http\Controllers\Api\ProjectAppointmentApiController::class, 'options'])->middleware('app_token:appointments:read');
    Route::get('/options/costs', [App\Http\Controllers\Api\CostApiController::class, 'options'])->middleware('app_token:costs:read');
    Route::get('/options/customers', [App\Http\Controllers\Api\CustomerApiController::class, 'options'])->middleware('app_token:customers:read');
    Route::get('/options/suppliers', [App\Http\Controllers\Api\SupplierApiController::class, 'options'])->middleware('app_token:suppliers:read');
});
