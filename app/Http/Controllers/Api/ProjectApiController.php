<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class ProjectApiController extends Controller
{
    /**
     * Liste alle Projekte
     */
    public function index(Request $request): JsonResponse
    {
        $query = Project::with([
            'projectManager',
            'customer',
            'supplier',
            'solarPlant',
            'creator',
            'milestones',
            'appointments',
            'tasks'
        ]);
        
        // Filter anwenden
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        
        if ($request->filled('project_manager_id')) {
            $query->where('project_manager_id', $request->project_manager_id);
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
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Datumsfilter
        if ($request->filled('start_date_from')) {
            $query->where('start_date', '>=', $request->start_date_from);
        }
        
        if ($request->filled('start_date_to')) {
            $query->where('start_date', '<=', $request->start_date_to);
        }
        
        if ($request->filled('planned_end_date_from')) {
            $query->where('planned_end_date', '>=', $request->planned_end_date_from);
        }
        
        if ($request->filled('planned_end_date_to')) {
            $query->where('planned_end_date', '<=', $request->planned_end_date_to);
        }
        
        // Budget-Filter
        if ($request->filled('min_budget')) {
            $query->where('budget', '>=', $request->min_budget);
        }
        
        if ($request->filled('max_budget')) {
            $query->where('budget', '<=', $request->max_budget);
        }
        
        // Fortschrittsfilter
        if ($request->filled('min_progress')) {
            $query->where('progress_percentage', '>=', $request->min_progress);
        }
        
        if ($request->filled('max_progress')) {
            $query->where('progress_percentage', '<=', $request->max_progress);
        }
        
        // Nur überfällige Projekte
        if ($request->boolean('overdue_only')) {
            $query->whereNotNull('planned_end_date')
                  ->where('planned_end_date', '<', now())
                  ->where('status', '!=', 'completed');
        }
        
        // Suche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('project_number', 'like', "%{$search}%");
            });
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Paginierung
        $perPage = min($request->get('per_page', 15), 100);
        $projects = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $projects->items(),
            'pagination' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ]
        ]);
    }
    
    /**
     * Zeige ein spezifisches Projekt
     */
    public function show(Project $project): JsonResponse
    {
        $project->load([
            'projectManager',
            'customer',
            'supplier',
            'solarPlant',
            'creator',
            'milestones.responsibleUser',
            'appointments.creator',
            'tasks.assignedTo',
            'documents'
        ]);
        
        return response()->json([
            'success' => true,
            'data' => array_merge($project->toArray(), [
                'computed_fields' => [
                    'is_overdue' => $project->is_overdue,
                    'days_remaining' => $project->days_remaining,
                    'status_color' => $project->status_color,
                    'priority_color' => $project->priority_color,
                    'open_milestones' => $project->open_milestones,
                    'completed_milestones' => $project->completed_milestones,
                    'upcoming_appointments' => $project->upcoming_appointments,
                ]
            ])
        ]);
    }
    
    /**
     * Erstelle ein neues Projekt
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|max:255',
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'start_date' => 'required|date',
            'planned_end_date' => 'nullable|date|after:start_date',
            'actual_end_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'actual_costs' => 'nullable|numeric|min:0',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'solar_plant_id' => 'nullable|exists:solar_plants,id',
            'project_manager_id' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
            'is_active' => 'boolean',
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
        
        $project = Project::create($data);
        $project->load(['projectManager', 'customer', 'supplier', 'solarPlant', 'creator']);
        
        return response()->json([
            'success' => true,
            'message' => 'Projekt erfolgreich erstellt',
            'data' => $project
        ], 201);
    }
    
    /**
     * Aktualisiere ein Projekt
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'start_date' => 'sometimes|required|date',
            'planned_end_date' => 'nullable|date|after:start_date',
            'actual_end_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'actual_costs' => 'nullable|numeric|min:0',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'solar_plant_id' => 'nullable|exists:solar_plants,id',
            'project_manager_id' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $project->update($validator->validated());
        $project->load(['projectManager', 'customer', 'supplier', 'solarPlant', 'creator']);
        
        return response()->json([
            'success' => true,
            'message' => 'Projekt erfolgreich aktualisiert',
            'data' => $project
        ]);
    }
    
    /**
     * Lösche ein Projekt
     */
    public function destroy(Project $project): JsonResponse
    {
        $project->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Projekt erfolgreich gelöscht'
        ]);
    }
    
    /**
     * Ändere den Status eines Projekts
     */
    public function updateStatus(Request $request, Project $project): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:planning,active,on_hold,completed,cancelled',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Wenn Projekt als abgeschlossen markiert wird, setze actual_end_date
        $updateData = ['status' => $request->status];
        if ($request->status === 'completed' && !$project->actual_end_date) {
            $updateData['actual_end_date'] = now();
            $updateData['progress_percentage'] = 100;
        }
        
        $project->update($updateData);
        
        return response()->json([
            'success' => true,
            'message' => 'Projektstatus erfolgreich geändert',
            'data' => $project
        ]);
    }
    
    /**
     * Hole Projektfortschritt mit Meilensteinen
     */
    public function progress(Project $project): JsonResponse
    {
        $project->load([
            'milestones.responsibleUser',
            'appointments' => function($query) {
                $query->where('start_datetime', '>=', now())->orderBy('start_datetime')->limit(5);
            }
        ]);
        
        $totalMilestones = $project->milestones->count();
        $completedMilestones = $project->milestones->where('status', 'completed')->count();
        $milestonesProgress = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;
        
        $overdueMilestones = $project->milestones->filter(function($milestone) {
            return $milestone->is_overdue;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'project' => $project->only(['id', 'name', 'status', 'progress_percentage', 'start_date', 'planned_end_date', 'actual_end_date']),
                'progress' => [
                    'overall_percentage' => $project->progress_percentage,
                    'milestones_percentage' => $milestonesProgress,
                    'is_overdue' => $project->is_overdue,
                    'days_remaining' => $project->days_remaining,
                ],
                'milestones' => [
                    'total' => $totalMilestones,
                    'completed' => $completedMilestones,
                    'pending' => $project->milestones->where('status', 'pending')->count(),
                    'in_progress' => $project->milestones->where('status', 'in_progress')->count(),
                    'overdue' => $overdueMilestones->count(),
                    'list' => $project->milestones,
                    'overdue_list' => $overdueMilestones->values(),
                ],
                'upcoming_appointments' => $project->appointments,
            ]
        ]);
    }
    
    /**
     * Aktualisiere Projektfortschritt
     */
    public function updateProgress(Request $request, Project $project): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'progress_percentage' => 'required|integer|min:0|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $project->update(['progress_percentage' => $request->progress_percentage]);
        
        return response()->json([
            'success' => true,
            'message' => 'Projektfortschritt erfolgreich aktualisiert',
            'data' => $project
        ]);
    }
    
    /**
     * Hole verfügbare Optionen für Projekte
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => [
                    'planning' => 'Planung',
                    'active' => 'Aktiv',
                    'on_hold' => 'Pausiert',
                    'completed' => 'Abgeschlossen',
                    'cancelled' => 'Abgebrochen'
                ],
                'priorities' => [
                    'low' => 'Niedrig',
                    'medium' => 'Mittel',
                    'high' => 'Hoch',
                    'urgent' => 'Dringend'
                ],
                'types' => [
                    'solar_installation' => 'Solaranlagen-Installation',
                    'maintenance' => 'Wartung',
                    'expansion' => 'Erweiterung',
                    'consulting' => 'Beratung',
                    'planning' => 'Planung',
                    'other' => 'Sonstiges'
                ]
            ]
        ]);
    }
}
