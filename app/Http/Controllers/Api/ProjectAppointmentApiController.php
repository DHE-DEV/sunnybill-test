<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAppointment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ProjectAppointmentApiController extends Controller
{
    /**
     * Liste alle Termine eines Projekts
     */
    public function indexByProject(Request $request, Project $project): JsonResponse
    {
        $query = $project->appointments()->with(['project', 'creator']);
        
        // Filter anwenden
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        
        // Datumsfilter
        if ($request->filled('start_date_from')) {
            $query->where('start_datetime', '>=', $request->start_date_from);
        }
        
        if ($request->filled('start_date_to')) {
            $query->where('start_datetime', '<=', $request->start_date_to);
        }
        
        // Nur bevorstehende Termine
        if ($request->boolean('upcoming_only')) {
            $query->where('start_datetime', '>=', now())
                  ->whereIn('status', ['scheduled', 'confirmed']);
        }
        
        // Nur heute
        if ($request->boolean('today_only')) {
            $query->whereDate('start_datetime', now()->toDateString());
        }
        
        // Diese Woche
        if ($request->boolean('this_week')) {
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            $query->whereBetween('start_datetime', [$startOfWeek, $endOfWeek]);
        }
        
        // Nur überfällige Termine
        if ($request->boolean('overdue_only')) {
            $query->where('start_datetime', '<', now())
                  ->where('status', 'scheduled');
        }
        
        // Suche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'start_datetime');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);
        
        $appointments = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $appointments,
            'project' => $project->only(['id', 'name', 'project_number', 'status'])
        ]);
    }
    
    /**
     * Zeige einen spezifischen Termin
     */
    public function show(ProjectAppointment $projectAppointment): JsonResponse
    {
        $projectAppointment->load(['project', 'creator']);
        
        return response()->json([
            'success' => true,
            'data' => array_merge($projectAppointment->toArray(), [
                'computed_fields' => [
                    'type_label' => $projectAppointment->type_label,
                    'status_color' => $projectAppointment->status_color,
                    'duration' => $projectAppointment->duration,
                    'is_upcoming' => $projectAppointment->is_upcoming,
                    'is_today' => $projectAppointment->is_today,
                    'is_overdue' => $projectAppointment->is_overdue,
                ]
            ])
        ]);
    }
    
    /**
     * Erstelle einen neuen Termin
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:meeting,deadline,review,milestone_check,inspection,training',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'attendees' => 'nullable|array',
            'attendees.*' => 'string|max:255',
            'reminder_minutes' => 'nullable|integer|min:0',
            'is_recurring' => 'boolean',
            'recurring_pattern' => 'nullable|array',
            'status' => 'required|in:scheduled,confirmed,cancelled,completed',
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
        $data['created_by'] = Auth::id();
        
        // Automatisch end_datetime setzen wenn nicht angegeben (1 Stunde)
        if (!isset($data['end_datetime'])) {
            $data['end_datetime'] = Carbon::parse($data['start_datetime'])->addHour();
        }
        
        $appointment = ProjectAppointment::create($data);
        $appointment->load(['project', 'creator']);
        
        return response()->json([
            'success' => true,
            'message' => 'Termin erfolgreich erstellt',
            'data' => $appointment
        ], 201);
    }
    
    /**
     * Aktualisiere einen Termin
     */
    public function update(Request $request, ProjectAppointment $projectAppointment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:meeting,deadline,review,milestone_check,inspection,training',
            'start_datetime' => 'sometimes|required|date',
            'end_datetime' => 'nullable|date|after:start_datetime',
            'location' => 'nullable|string|max:255',
            'attendees' => 'nullable|array',
            'attendees.*' => 'string|max:255',
            'reminder_minutes' => 'nullable|integer|min:0',
            'is_recurring' => 'boolean',
            'recurring_pattern' => 'nullable|array',
            'status' => 'sometimes|required|in:scheduled,confirmed,cancelled,completed',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $projectAppointment->update($validator->validated());
        $projectAppointment->load(['project', 'creator']);
        
        return response()->json([
            'success' => true,
            'message' => 'Termin erfolgreich aktualisiert',
            'data' => $projectAppointment
        ]);
    }
    
    /**
     * Lösche einen Termin
     */
    public function destroy(ProjectAppointment $projectAppointment): JsonResponse
    {
        $projectAppointment->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Termin erfolgreich gelöscht'
        ]);
    }
    
    /**
     * Ändere den Status eines Termins
     */
    public function updateStatus(Request $request, ProjectAppointment $projectAppointment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:scheduled,confirmed,cancelled,completed',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $projectAppointment->update(['status' => $request->status]);
        
        return response()->json([
            'success' => true,
            'message' => 'Terminstatus erfolgreich geändert',
            'data' => $projectAppointment
        ]);
    }
    
    /**
     * Hole alle Termine (projektübergreifend)
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProjectAppointment::with(['project', 'creator']);
        
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
        
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        
        // Datumsfilter
        if ($request->filled('start_date_from')) {
            $query->where('start_datetime', '>=', $request->start_date_from);
        }
        
        if ($request->filled('start_date_to')) {
            $query->where('start_datetime', '<=', $request->start_date_to);
        }
        
        // Nur bevorstehende Termine
        if ($request->boolean('upcoming_only')) {
            $query->where('start_datetime', '>=', now())
                  ->whereIn('status', ['scheduled', 'confirmed']);
        }
        
        // Nur heute
        if ($request->boolean('today_only')) {
            $query->whereDate('start_datetime', now()->toDateString());
        }
        
        // Diese Woche
        if ($request->boolean('this_week')) {
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            $query->whereBetween('start_datetime', [$startOfWeek, $endOfWeek]);
        }
        
        // Nur überfällige Termine
        if ($request->boolean('overdue_only')) {
            $query->where('start_datetime', '<', now())
                  ->where('status', 'scheduled');
        }
        
        // Suche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('project', function (Builder $projectQuery) use ($search) {
                      $projectQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('project_number', 'like', "%{$search}%");
                  });
            });
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'start_datetime');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Paginierung
        $perPage = min($request->get('per_page', 15), 100);
        $appointments = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $appointments->items(),
            'pagination' => [
                'current_page' => $appointments->currentPage(),
                'last_page' => $appointments->lastPage(),
                'per_page' => $appointments->perPage(),
                'total' => $appointments->total(),
            ]
        ]);
    }
    
    /**
     * Hole bevorstehende Termine (alle Projekte)
     */
    public function upcoming(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);
        
        $appointments = ProjectAppointment::with(['project', 'creator'])
            ->where('start_datetime', '>=', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('start_datetime', 'asc')
            ->limit($limit)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $appointments
        ]);
    }
    
    /**
     * Kalenderansicht für Termine
     */
    public function calendar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'project_id' => 'nullable|exists:projects,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $query = ProjectAppointment::with(['project', 'creator'])
            ->whereBetween('start_datetime', [
                $request->start_date,
                $request->end_date
            ]);
        
        // Optional: Filter nach Projekt
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        $appointments = $query->orderBy('start_datetime')->get();
        
        // Gruppiere Termine nach Datum
        $groupedAppointments = $appointments->groupBy(function ($appointment) {
            return Carbon::parse($appointment->start_datetime)->format('Y-m-d');
        });
        
        // Erstelle Kalender-Event-Format
        $calendarEvents = $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'start' => $appointment->start_datetime,
                'end' => $appointment->end_datetime,
                'project_id' => $appointment->project_id,
                'project_name' => $appointment->project->name ?? null,
                'type' => $appointment->type,
                'type_label' => $appointment->type_label,
                'status' => $appointment->status,
                'status_color' => $appointment->status_color,
                'location' => $appointment->location,
                'description' => $appointment->description,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'events' => $calendarEvents,
                'grouped_by_date' => $groupedAppointments,
                'summary' => [
                    'total_appointments' => $appointments->count(),
                    'by_status' => $appointments->groupBy('status')->map->count(),
                    'by_type' => $appointments->groupBy('type')->map->count(),
                    'date_range' => [
                        'start' => $request->start_date,
                        'end' => $request->end_date,
                    ]
                ]
            ]
        ]);
    }
    
    /**
     * Hole verfügbare Optionen für Termine
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => [
                    'scheduled' => 'Geplant',
                    'confirmed' => 'Bestätigt',
                    'cancelled' => 'Abgesagt',
                    'completed' => 'Abgeschlossen'
                ],
                'types' => [
                    'meeting' => 'Meeting',
                    'deadline' => 'Deadline',
                    'review' => 'Review',
                    'milestone_check' => 'Meilenstein-Check',
                    'inspection' => 'Inspektion',
                    'training' => 'Schulung'
                ],
                'reminder_options' => [
                    0 => 'Keine Erinnerung',
                    15 => '15 Minuten vorher',
                    30 => '30 Minuten vorher',
                    60 => '1 Stunde vorher',
                    120 => '2 Stunden vorher',
                    1440 => '1 Tag vorher'
                ]
            ]
        ]);
    }
}
