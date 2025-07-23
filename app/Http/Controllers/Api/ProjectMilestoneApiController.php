<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class ProjectMilestoneApiController extends Controller
{
    /**
     * Liste alle Meilensteine eines Projekts
     */
    public function indexByProject(Request $request, Project $project): JsonResponse
    {
        $query = $project->milestones()->with(['responsibleUser', 'project']);
        
        // Filter anwenden
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('responsible_user_id')) {
            $query->where('responsible_user_id', $request->responsible_user_id);
        }
        
        if ($request->filled('is_critical_path')) {
            $query->where('is_critical_path', $request->boolean('is_critical_path'));
        }
        
        // Datumsfilter
        if ($request->filled('planned_date_from')) {
            $query->where('planned_date', '>=', $request->planned_date_from);
        }
        
        if ($request->filled('planned_date_to')) {
            $query->where('planned_date', '<=', $request->planned_date_to);
        }
        
        // Nur überfällige Meilensteine
        if ($request->boolean('overdue_only')) {
            $query->whereDate('planned_date', '<', now()->toDateString())
                  ->where('status', '!=', 'completed');
        }
        
        // Nur heute fällige Meilensteine
        if ($request->boolean('due_today')) {
            $query->whereDate('planned_date', now()->toDateString())
                  ->whereIn('status', ['pending', 'in_progress']);
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
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Sekundäre Sortierung nach geplanten Datum
        if ($sortBy !== 'planned_date') {
            $query->orderBy('planned_date', 'asc');
        }
        
        $milestones = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $milestones,
            'project' => $project->only(['id', 'name', 'project_number', 'status'])
        ]);
    }
    
    /**
     * Zeige einen spezifischen Meilenstein
     */
    public function show(ProjectMilestone $projectMilestone): JsonResponse
    {
        $projectMilestone->load(['project', 'responsibleUser']);
        
        return response()->json([
            'success' => true,
            'data' => array_merge($projectMilestone->toArray(), [
                'computed_fields' => [
                    'is_overdue' => $projectMilestone->is_overdue,
                    'days_remaining' => $projectMilestone->days_remaining,
                    'status_color' => $projectMilestone->status_color,
                    'type_label' => $projectMilestone->type_label,
                    'progress_percentage' => $projectMilestone->progress_percentage,
                ]
            ])
        ]);
    }
    
    /**
     * Erstelle einen neuen Meilenstein
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:planning,approval,implementation,testing,delivery,payment,review',
            'planned_date' => 'required|date',
            'actual_date' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,delayed,cancelled',
            'responsible_user_id' => 'nullable|exists:users,id',
            'dependencies' => 'nullable|array',
            'completion_percentage' => 'nullable|integer|min:0|max:100',
            'is_critical_path' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $validator->validated();
        $data['project_id'] = $project->id;
        
        // Automatische Sortierung wenn nicht angegeben
        if (!isset($data['sort_order'])) {
            $maxSortOrder = $project->milestones()->max('sort_order') ?? 0;
            $data['sort_order'] = $maxSortOrder + 10;
        }
        
        $milestone = ProjectMilestone::create($data);
        $milestone->load(['project', 'responsibleUser']);
        
        return response()->json([
            'success' => true,
            'message' => 'Meilenstein erfolgreich erstellt',
            'data' => $milestone
        ], 201);
    }
    
    /**
     * Aktualisiere einen Meilenstein
     */
    public function update(Request $request, ProjectMilestone $projectMilestone): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:planning,approval,implementation,testing,delivery,payment,review',
            'planned_date' => 'sometimes|required|date',
            'actual_date' => 'nullable|date',
            'status' => 'sometimes|required|in:pending,in_progress,completed,delayed,cancelled',
            'responsible_user_id' => 'nullable|exists:users,id',
            'dependencies' => 'nullable|array',
            'completion_percentage' => 'nullable|integer|min:0|max:100',
            'is_critical_path' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $validator->validated();
        
        // Automatisch actual_date setzen wenn Status auf completed geändert wird
        if (isset($data['status']) && $data['status'] === 'completed' && !$projectMilestone->actual_date) {
            $data['actual_date'] = now();
            $data['completion_percentage'] = 100;
        }
        
        $projectMilestone->update($data);
        $projectMilestone->load(['project', 'responsibleUser']);
        
        return response()->json([
            'success' => true,
            'message' => 'Meilenstein erfolgreich aktualisiert',
            'data' => $projectMilestone
        ]);
    }
    
    /**
     * Lösche einen Meilenstein
     */
    public function destroy(ProjectMilestone $projectMilestone): JsonResponse
    {
        $projectMilestone->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Meilenstein erfolgreich gelöscht'
        ]);
    }
    
    /**
     * Ändere den Status eines Meilensteins
     */
    public function updateStatus(Request $request, ProjectMilestone $projectMilestone): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed,delayed,cancelled',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $updateData = ['status' => $request->status];
        
        // Automatische Aktionen basierend auf Status
        if ($request->status === 'completed') {
            if (!$projectMilestone->actual_date) {
                $updateData['actual_date'] = now();
            }
            $updateData['completion_percentage'] = 100;
        } elseif ($request->status === 'in_progress' && $projectMilestone->completion_percentage === null) {
            $updateData['completion_percentage'] = 25; // Standardwert für "in progress"
        }
        
        $projectMilestone->update($updateData);
        
        return response()->json([
            'success' => true,
            'message' => 'Meilenstein-Status erfolgreich geändert',
            'data' => $projectMilestone
        ]);
    }
    
    /**
     * Aktualisiere den Fortschritt eines Meilensteins
     */
    public function updateProgress(Request $request, ProjectMilestone $projectMilestone): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'completion_percentage' => 'required|integer|min:0|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $updateData = ['completion_percentage' => $request->completion_percentage];
        
        // Automatische Status-Aktualisierung basierend auf Fortschritt
        if ($request->completion_percentage === 100 && $projectMilestone->status !== 'completed') {
            $updateData['status'] = 'completed';
            if (!$projectMilestone->actual_date) {
                $updateData['actual_date'] = now();
            }
        } elseif ($request->completion_percentage > 0 && $request->completion_percentage < 100 && $projectMilestone->status === 'pending') {
            $updateData['status'] = 'in_progress';
        }
        
        $projectMilestone->update($updateData);
        
        return response()->json([
            'success' => true,
            'message' => 'Meilenstein-Fortschritt erfolgreich aktualisiert',
            'data' => $projectMilestone
        ]);
    }
    
    /**
     * Hole alle Meilensteine (projektübergreifend)
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProjectMilestone::with(['project', 'responsibleUser']);
        
        // Filter anwenden
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('responsible_user_id')) {
            $query->where('responsible_user_id', $request->responsible_user_id);
        }
        
        if ($request->filled('is_critical_path')) {
            $query->where('is_critical_path', $request->boolean('is_critical_path'));
        }
        
        // Datumsfilter
        if ($request->filled('planned_date_from')) {
            $query->where('planned_date', '>=', $request->planned_date_from);
        }
        
        if ($request->filled('planned_date_to')) {
            $query->where('planned_date', '<=', $request->planned_date_to);
        }
        
        // Nur überfällige Meilensteine
        if ($request->boolean('overdue_only')) {
            $query->whereDate('planned_date', '<', now()->toDateString())
                  ->where('status', '!=', 'completed');
        }
        
        // Nur heute fällige Meilensteine
        if ($request->boolean('due_today')) {
            $query->whereDate('planned_date', now()->toDateString())
                  ->whereIn('status', ['pending', 'in_progress']);
        }
        
        // Diese Woche fällige Meilensteine
        if ($request->boolean('due_this_week')) {
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            $query->whereBetween('planned_date', [$startOfWeek, $endOfWeek])
                  ->whereIn('status', ['pending', 'in_progress']);
        }
        
        // Suche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('project', function (Builder $projectQuery) use ($search) {
                      $projectQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('project_number', 'like', "%{$search}%");
                  });
            });
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'planned_date');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Paginierung
        $perPage = min($request->get('per_page', 15), 100);
        $milestones = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $milestones->items(),
            'pagination' => [
                'current_page' => $milestones->currentPage(),
                'last_page' => $milestones->lastPage(),
                'per_page' => $milestones->perPage(),
                'total' => $milestones->total(),
            ]
        ]);
    }
    
    /**
     * Hole verfügbare Optionen für Meilensteine
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => [
                    'pending' => 'Ausstehend',
                    'in_progress' => 'In Bearbeitung',
                    'completed' => 'Abgeschlossen',
                    'delayed' => 'Verzögert',
                    'cancelled' => 'Abgebrochen'
                ],
                'types' => [
                    'planning' => 'Planung',
                    'approval' => 'Genehmigung',
                    'implementation' => 'Umsetzung',
                    'testing' => 'Testing',
                    'delivery' => 'Lieferung',
                    'payment' => 'Zahlung',
                    'review' => 'Review'
                ]
            ]
        ]);
    }
}
