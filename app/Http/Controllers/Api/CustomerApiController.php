<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class CustomerApiController extends Controller
{
    /**
     * Liste aller Kunden
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with(['solarPlants', 'participations']);
        
        // Filter anwenden
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }
        
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Filter für Beteiligungen
        if ($request->filled('has_participations')) {
            if ($request->boolean('has_participations')) {
                $query->whereHas('participations');
            } else {
                $query->whereDoesntHave('participations');
            }
        }
        
        if ($request->filled('has_solar_plants')) {
            if ($request->boolean('has_solar_plants')) {
                $query->whereHas('solarPlants');
            } else {
                $query->whereDoesntHave('solarPlants');
            }
        }
        
        // Datumsfilter
        if ($request->filled('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }
        
        if ($request->filled('created_to')) {
            $query->where('created_at', '<=', $request->created_to);
        }
        
        // Suche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('customer_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Paginierung
        $perPage = min($request->get('per_page', 15), 100);
        $customers = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ]
        ]);
    }
    
    /**
     * Zeige einen spezifischen Kunden
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load([
            'solarPlants' => function($query) {
                $query->with(['billings' => function($billingQuery) {
                    $billingQuery->latest('billing_month')->limit(12);
                }]);
            },
            'participations.solarPlant',
            'projects.milestones',
            'tasks' => function($query) {
                $query->latest()->limit(10);
            }
        ]);
        
        // Zusätzliche Berechnungen
        $totalInvestment = $customer->participations->sum('investment_amount');
        $totalParticipations = $customer->participations->count();
        $activePlants = $customer->solarPlants->where('is_active', true)->count();
        $totalProjects = $customer->projects->count();
        $openTasks = $customer->tasks->whereIn('status', ['pending', 'in_progress'])->count();
        
        return response()->json([
            'success' => true,
            'data' => array_merge($customer->toArray(), [
                'computed_fields' => [
                    'full_name' => $customer->full_name,
                    'display_name' => $customer->display_name,
                    'complete_address' => $customer->complete_address,
                ],
                'statistics' => [
                    'total_investment' => (float) $totalInvestment,
                    'total_participations' => $totalParticipations,
                    'active_plants' => $activePlants,
                    'total_projects' => $totalProjects,
                    'open_tasks' => $openTasks,
                ]
            ])
        ]);
    }
    
    /**
     * Erstelle einen neuen Kunden
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_type' => 'required|in:private,business',
            'first_name' => 'required_if:customer_type,private|string|max:255',
            'last_name' => 'required_if:customer_type,private|string|max:255',
            'company_name' => 'required_if:customer_type,business|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'customer_number' => 'nullable|string|max:50|unique:customers,customer_number',
            'status' => 'required|in:active,inactive,prospect,blocked',
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
        
        // Automatische Kundennummer generieren wenn nicht angegeben
        if (!isset($data['customer_number'])) {
            $lastCustomer = Customer::orderBy('id', 'desc')->first();
            $nextNumber = $lastCustomer ? ($lastCustomer->id + 1) : 1;
            $data['customer_number'] = 'KD-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        }
        
        $customer = Customer::create($data);
        $customer->load(['solarPlants', 'participations']);
        
        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich erstellt',
            'data' => $customer
        ], 201);
    }
    
    /**
     * Aktualisiere einen Kunden
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_type' => 'sometimes|required|in:private,business',
            'first_name' => 'required_if:customer_type,private|string|max:255',
            'last_name' => 'required_if:customer_type,private|string|max:255',
            'company_name' => 'required_if:customer_type,business|string|max:255',
            'email' => 'sometimes|required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'customer_number' => 'sometimes|required|string|max:50|unique:customers,customer_number,' . $customer->id,
            'status' => 'sometimes|required|in:active,inactive,prospect,blocked',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $customer->update($validator->validated());
        $customer->load(['solarPlants', 'participations']);
        
        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich aktualisiert',
            'data' => $customer
        ]);
    }
    
    /**
     * Lösche einen Kunden
     */
    public function destroy(Customer $customer): JsonResponse
    {
        // Prüfe ob Kunde verknüpfte Daten hat
        if ($customer->solarPlants()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kunde kann nicht gelöscht werden, da noch Solaranlagen verknüpft sind'
            ], 400);
        }
        
        if ($customer->participations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kunde kann nicht gelöscht werden, da noch Beteiligungen vorhanden sind'
            ], 400);
        }
        
        if ($customer->projects()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kunde kann nicht gelöscht werden, da noch Projekte verknüpft sind'
            ], 400);
        }
        
        $customer->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich gelöscht'
        ]);
    }
    
    /**
     * Ändere den Status eines Kunden
     */
    public function updateStatus(Request $request, Customer $customer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,prospect,blocked',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $customer->update(['status' => $request->status]);
        
        return response()->json([
            'success' => true,
            'message' => 'Kundenstatus erfolgreich geändert',
            'data' => $customer
        ]);
    }
    
    /**
     * Hole Beteiligungen eines Kunden
     */
    public function participations(Customer $customer): JsonResponse
    {
        $participations = $customer->participations()
            ->with(['solarPlant', 'solarPlant.billings' => function($query) {
                $query->latest('billing_month')->limit(12);
            }])
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $participations,
            'summary' => [
                'total_participations' => $participations->count(),
                'total_investment' => (float) $participations->sum('investment_amount'),
                'total_percentage' => (float) $participations->sum('percentage'),
            ]
        ]);
    }
    
    /**
     * Hole Projekte eines Kunden
     */
    public function projects(Customer $customer): JsonResponse
    {
        $projects = $customer->projects()
            ->with(['milestones', 'appointments', 'tasks'])
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $projects,
            'summary' => [
                'total_projects' => $projects->count(),
                'active_projects' => $projects->where('status', 'active')->count(),
                'completed_projects' => $projects->where('status', 'completed')->count(),
                'total_budget' => (float) $projects->sum('budget'),
            ]
        ]);
    }
    
    /**
     * Hole Aufgaben eines Kunden
     */
    public function tasks(Customer $customer): JsonResponse
    {
        $tasks = $customer->tasks()
            ->with(['assignedUser', 'project'])
            ->latest()
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $tasks,
            'summary' => [
                'total_tasks' => $tasks->count(),
                'open_tasks' => $tasks->whereIn('status', ['pending', 'in_progress'])->count(),
                'completed_tasks' => $tasks->where('status', 'completed')->count(),
                'high_priority_tasks' => $tasks->where('priority', 'high')->count(),
            ]
        ]);
    }
    
    /**
     * Finanzielle Übersicht eines Kunden
     */
    public function financials(Customer $customer): JsonResponse
    {
        $participations = $customer->participations()->with('solarPlant.billings')->get();
        
        // Berechne Gesamterträge der letzten 12 Monate
        $totalIncome = 0;
        $totalCosts = 0;
        $totalNetResult = 0;
        
        foreach ($participations as $participation) {
            $recentBillings = $participation->solarPlant->billings()
                ->where('billing_month', '>=', now()->subMonths(12)->startOfMonth())
                ->get();
            
            $participationPercentage = $participation->percentage / 100;
            
            $totalIncome += $recentBillings->sum('total_income') * $participationPercentage;
            $totalCosts += $recentBillings->sum('total_costs') * $participationPercentage;
            $totalNetResult += $recentBillings->sum('net_result') * $participationPercentage;
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer->only(['id', 'customer_number', 'display_name']),
                'investment_summary' => [
                    'total_investment' => (float) $participations->sum('investment_amount'),
                    'total_participations' => $participations->count(),
                    'active_plants' => $participations->whereIn('solar_plant_id', 
                        $customer->solarPlants()->where('is_active', true)->pluck('id')
                    )->count(),
                ],
                'performance_12m' => [
                    'period' => [
                        'start' => now()->subMonths(12)->startOfMonth()->format('Y-m-d'),
                        'end' => now()->format('Y-m-d'),
                    ],
                    'total_income' => (float) $totalIncome,
                    'total_costs' => (float) $totalCosts,
                    'net_result' => (float) $totalNetResult,
                ],
                'roi_analysis' => [
                    'annual_return' => (float) $totalNetResult,
                    'total_investment' => (float) $participations->sum('investment_amount'),
                    'roi_percentage' => $participations->sum('investment_amount') > 0 
                        ? round(($totalNetResult / $participations->sum('investment_amount')) * 100, 2)
                        : 0,
                ]
            ]
        ]);
    }
    
    /**
     * Hole verfügbare Optionen für Kunden
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'customer_types' => [
                    'private' => 'Privatkunde',
                    'business' => 'Geschäftskunde'
                ],
                'statuses' => [
                    'active' => 'Aktiv',
                    'inactive' => 'Inaktiv',
                    'prospect' => 'Interessent',
                    'blocked' => 'Gesperrt'
                ],
                'countries' => [
                    'Deutschland' => 'Deutschland',
                    'Österreich' => 'Österreich',
                    'Schweiz' => 'Schweiz'
                ]
            ]
        ]);
    }
}
