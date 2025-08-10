<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class TaskApiController extends Controller
{
    /**
     * Liste alle Aufgaben
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['assignedTo', 'owner', 'customer', 'supplier', 'solarPlant', 'parentTask', 'subtasks', 'notes.user']);
        
        // Ressourcen-Beschränkungen basierend auf App-Token anwenden
        $this->applyTokenResourceFilters($query, $request);
        
        // Filter anwenden
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }
        
        if ($request->filled('owner_id')) {
            $query->where('owner_id', $request->owner_id);
        }
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        if ($request->filled('solar_plant_id')) {
            $query->where('solar_plant_id', $request->solar_plant_id);
        }
        
        if ($request->filled('parent_task_id')) {
            $query->where('parent_task_id', $request->parent_task_id);
        }
        
        // Nur Hauptaufgaben
        if ($request->boolean('main_tasks_only')) {
            $query->whereNull('parent_task_id');
        }
        
        // Suche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Paginierung
        $perPage = min($request->get('per_page', 15), 100);
        $tasks = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $tasks->items(),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ]
        ]);
    }
    
    /**
     * Zeige eine spezifische Aufgabe
     */
    public function show(Request $request, Task $task): JsonResponse
    {
        // Prüfe Token-Zugriff auf diese spezifische Aufgabe
        if ($request->app_token && !$request->app_token->canAccessTask($task)) {
            return response()->json([
                'success' => false,
                'message' => 'Zugriff auf diese Aufgabe verweigert'
            ], 403);
        }
        
        $task->load(['assignedTo', 'owner', 'customer', 'supplier', 'solarPlant', 'parentTask', 'subtasks', 'notes.user']);
        
        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }
    
    /**
     * Erstelle eine neue Aufgabe
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'nullable|string|max:255', // Optional - wird automatisch gesetzt
            'task_type_id' => 'required|integer|exists:task_types,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:open,in_progress,waiting_external,waiting_internal,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'owner_id' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'solar_plant_id' => 'nullable|exists:solar_plants,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|date_format:H:i',
            'estimated_minutes' => 'nullable|integer|min:0',
            'actual_minutes' => 'nullable|integer|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        
        // Automatisch task_type aus task_type_id ermitteln, falls nicht übermittelt
        if (empty($data['task_type']) && isset($data['task_type_id'])) {
            $taskType = \App\Models\TaskType::find($data['task_type_id']);
            if ($taskType) {
                $data['task_type'] = $taskType->name;
            }
        }
        
        // Setze Standard-Owner wenn nicht angegeben
        if (!isset($data['owner_id'])) {
            $data['owner_id'] = Auth::id();
        }
        
        $task = Task::create($data);
        $task->load(['assignedTo', 'owner', 'customer', 'supplier', 'solarPlant', 'parentTask']);
        
        return response()->json([
            'success' => true,
            'message' => 'Aufgabe erfolgreich erstellt',
            'data' => $task
        ], 201);
    }
    
    /**
     * Aktualisiere eine Aufgabe
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'sometimes|required|string|max:255',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'status' => 'sometimes|required|in:open,in_progress,waiting_external,waiting_internal,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'owner_id' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'solar_plant_id' => 'nullable|exists:solar_plants,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'due_date' => 'nullable|date',
            'due_time' => 'nullable|date_format:H:i',
            'estimated_minutes' => 'nullable|integer|min:0',
            'actual_minutes' => 'nullable|integer|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $task->update($validator->validated());
        $task->load(['assignedTo', 'owner', 'customer', 'supplier', 'solarPlant', 'parentTask']);
        
        return response()->json([
            'success' => true,
            'message' => 'Aufgabe erfolgreich aktualisiert',
            'data' => $task
        ]);
    }
    
    /**
     * Lösche eine Aufgabe
     */
    public function destroy(Task $task): JsonResponse
    {
        $task->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Aufgabe erfolgreich gelöscht'
        ]);
    }
    
    /**
     * Ändere den Status einer Aufgabe
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:open,in_progress,waiting_external,waiting_internal,completed,cancelled',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $task->update(['status' => $request->status]);
        
        return response()->json([
            'success' => true,
            'message' => 'Status erfolgreich geändert',
            'data' => $task
        ]);
    }
    
    /**
     * Weise eine Aufgabe zu
     */
    public function assign(Request $request, Task $task): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $task->update(['assigned_to' => $request->assigned_to]);
        $task->load(['assignedTo']);
        
        return response()->json([
            'success' => true,
            'message' => 'Aufgabe erfolgreich zugewiesen',
            'data' => $task
        ]);
    }
    
    /**
     * Aktualisiere Zeiten
     */
    public function updateTime(Request $request, Task $task): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'estimated_minutes' => 'nullable|integer|min:0',
            'actual_minutes' => 'nullable|integer|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $task->update($validator->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Zeiten erfolgreich aktualisiert',
            'data' => $task
        ]);
    }
    
    /**
     * Hole Unteraufgaben
     */
    public function subtasks(Task $task): JsonResponse
    {
        $subtasks = $task->subtasks()->with(['assignedTo', 'owner', 'customer', 'supplier', 'solarPlant'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $subtasks
        ]);
    }
    
    /**
     * Hole Benutzer für Dropdown
     */
    public function users(): JsonResponse
    {
        $users = User::active()->select(
            'id', 
            'name', 
            'email', 
            'phone', 
            'department', 
            'notes', 
            'last_login_at',
            'is_active',
            'email_verified_at',
            'password_change_required'
        )->get();
        
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
    
    /**
     * Hole Kunden für Dropdown
     */
    public function customers(Request $request): JsonResponse
    {
        $query = Customer::select('id', 'name', 'company_name');
        
        // Token-basierte Einschränkungen anwenden
        if ($request->app_token) {
            $allowedIds = $request->app_token->getAllowedResourceIds('customers');
            if ($allowedIds !== null) {
                $query->whereIn('id', $allowedIds);
            }
        }
        
        $customers = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }
    
    /**
     * Hole Lieferanten für Dropdown
     */
    public function suppliers(Request $request): JsonResponse
    {
        $query = Supplier::select('id', 'name', 'company_name');
        
        // Token-basierte Einschränkungen anwenden
        if ($request->app_token) {
            $allowedIds = $request->app_token->getAllowedResourceIds('suppliers');
            if ($allowedIds !== null) {
                $query->whereIn('id', $allowedIds);
            }
        }
        
        $suppliers = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }
    
    /**
     * Hole Solaranlagen für Dropdown
     */
    public function solarPlants(Request $request): JsonResponse
    {
        $query = SolarPlant::select('id', 'name', 'location');
        
        // Token-basierte Einschränkungen anwenden
        if ($request->app_token) {
            $allowedIds = $request->app_token->getAllowedResourceIds('solar_plants');
            if ($allowedIds !== null) {
                $query->whereIn('id', $allowedIds);
            }
        }
        
        $solarPlants = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $solarPlants
        ]);
    }
    
    /**
     * Hole verfügbare Optionen
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'priorities' => [
                    'low' => 'Niedrig',
                    'medium' => 'Mittel',
                    'high' => 'Hoch',
                    'urgent' => 'Dringend'
                ],
                'statuses' => [
                    'open' => 'Offen',
                    'in_progress' => 'In Bearbeitung',
                    'waiting_external' => 'Warte auf Extern',
                    'waiting_internal' => 'Warte auf Intern',
                    'completed' => 'Abgeschlossen',
                    'cancelled' => 'Abgebrochen'
                ],
                'task_types' => Task::getAvailableTaskTypes()
            ]
        ]);
    }
    
    /**
     * Hole alle verfügbaren Task Types
     */
    public function taskTypes(): JsonResponse
    {
        $taskTypes = \App\Models\TaskType::select('id', 'name', 'is_active')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $taskTypes
        ]);
    }
    
    /**
     * Hole Profil-Informationen
     */
    public function profile(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'role_label' => $user->role_label,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'app_token' => $request->app_token->only(['name', 'app_type', 'expires_at', 'abilities']),
            ]
        ]);
    }
    
    /**
     * Benutzer abmelden (Token invalidieren)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // App-Token löschen/invalidieren
            if ($request->app_token) {
                $request->app_token->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Erfolgreich abgemeldet'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abmelden: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Wende Token-basierte Ressourcen-Filter auf eine Query an
     */
    private function applyTokenResourceFilters($query, Request $request): void
    {
        if (!$request->app_token) {
            return;
        }
        
        $token = $request->app_token;
        
        // Kunden-Beschränkungen
        $allowedCustomers = $token->getAllowedResourceIds('customers');
        if ($allowedCustomers !== null) {
            $query->where(function($q) use ($allowedCustomers) {
                $q->whereIn('customer_id', $allowedCustomers)
                  ->orWhereNull('customer_id');
            });
        }
        
        // Lieferanten-Beschränkungen
        $allowedSuppliers = $token->getAllowedResourceIds('suppliers');
        if ($allowedSuppliers !== null) {
            $query->where(function($q) use ($allowedSuppliers) {
                $q->whereIn('supplier_id', $allowedSuppliers)
                  ->orWhereNull('supplier_id');
            });
        }
        
        // Solaranlagen-Beschränkungen
        $allowedSolarPlants = $token->getAllowedResourceIds('solar_plants');
        if ($allowedSolarPlants !== null) {
            $query->where(function($q) use ($allowedSolarPlants) {
                $q->whereIn('solar_plant_id', $allowedSolarPlants)
                  ->orWhereNull('solar_plant_id');
            });
        }
        
        // Projekt-Beschränkungen (falls vorhanden)
        $allowedProjects = $token->getAllowedResourceIds('projects');
        if ($allowedProjects !== null) {
            $query->where(function($q) use ($allowedProjects) {
                $q->whereIn('project_id', $allowedProjects)
                  ->orWhereNull('project_id');
            });
        }
    }
}
