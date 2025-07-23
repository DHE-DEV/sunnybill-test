<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class SupplierApiController extends Controller
{
    /**
     * Liste aller Lieferanten
     */
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::with(['contracts', 'projects']);
        
        // Filter anwenden
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('supplier_type')) {
            $query->where('supplier_type', $request->supplier_type);
        }
        
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Filter für Verträge
        if ($request->filled('has_contracts')) {
            if ($request->boolean('has_contracts')) {
                $query->whereHas('contracts');
            } else {
                $query->whereDoesntHave('contracts');
            }
        }
        
        if ($request->filled('has_projects')) {
            if ($request->boolean('has_projects')) {
                $query->whereHas('projects');
            } else {
                $query->whereDoesntHave('projects');
            }
        }
        
        // Filter für aktive Verträge
        if ($request->filled('has_active_contracts')) {
            if ($request->boolean('has_active_contracts')) {
                $query->whereHas('contracts', function (Builder $contractQuery) {
                    $contractQuery->where('status', 'active')
                                  ->where('end_date', '>=', now());
                });
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
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('supplier_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Paginierung
        $perPage = min($request->get('per_page', 15), 100);
        $suppliers = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $suppliers->items(),
            'pagination' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ]
        ]);
    }
    
    /**
     * Zeige einen spezifischen Lieferanten
     */
    public function show(Supplier $supplier): JsonResponse
    {
        $supplier->load([
            'contracts' => function($query) {
                $query->with(['billings' => function($billingQuery) {
                    $billingQuery->latest('billing_month')->limit(12);
                }]);
            },
            'projects.milestones',
            'tasks' => function($query) {
                $query->latest()->limit(10);
            }
        ]);
        
        // Zusätzliche Berechnungen
        $totalContracts = $supplier->contracts->count();
        $activeContracts = $supplier->contracts->where('status', 'active')->count();
        $totalProjects = $supplier->projects->count();
        $openTasks = $supplier->tasks->whereIn('status', ['pending', 'in_progress'])->count();
        
        // Gesamtvolumen der letzten 12 Monate
        $totalBillingAmount = $supplier->contracts->flatMap->billings
            ->where('billing_month', '>=', now()->subMonths(12)->startOfMonth())
            ->sum('total_amount');
        
        return response()->json([
            'success' => true,
            'data' => array_merge($supplier->toArray(), [
                'computed_fields' => [
                    'display_name' => $supplier->display_name,
                    'complete_address' => $supplier->complete_address,
                ],
                'statistics' => [
                    'total_contracts' => $totalContracts,
                    'active_contracts' => $activeContracts,
                    'total_projects' => $totalProjects,
                    'open_tasks' => $openTasks,
                    'billing_volume_12m' => (float) $totalBillingAmount,
                ]
            ])
        ]);
    }
    
    /**
     * Erstelle einen neuen Lieferanten
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'supplier_type' => 'required|in:energy_provider,maintenance,installation,consulting,insurance,other',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'required|email|unique:suppliers,email',
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'supplier_number' => 'nullable|string|max:50|unique:suppliers,supplier_number',
            'website' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive,blocked',
            'is_active' => 'boolean',
            'payment_terms' => 'nullable|integer|min:0|max:365',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $validator->validated();
        
        // Automatische Lieferantennummer generieren wenn nicht angegeben
        if (!isset($data['supplier_number'])) {
            $lastSupplier = Supplier::orderBy('id', 'desc')->first();
            $nextNumber = $lastSupplier ? ($lastSupplier->id + 1) : 1;
            $data['supplier_number'] = 'LF-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        }
        
        $supplier = Supplier::create($data);
        $supplier->load(['contracts', 'projects']);
        
        return response()->json([
            'success' => true,
            'message' => 'Lieferant erfolgreich erstellt',
            'data' => $supplier
        ], 201);
    }
    
    /**
     * Aktualisiere einen Lieferanten
     */
    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'sometimes|required|string|max:255',
            'supplier_type' => 'sometimes|required|in:energy_provider,maintenance,installation,consulting,insurance,other',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'sometimes|required|email|unique:suppliers,email,' . $supplier->id,
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:20',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'supplier_number' => 'sometimes|required|string|max:50|unique:suppliers,supplier_number,' . $supplier->id,
            'website' => 'nullable|url|max:255',
            'status' => 'sometimes|required|in:active,inactive,blocked',
            'is_active' => 'boolean',
            'payment_terms' => 'nullable|integer|min:0|max:365',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $supplier->update($validator->validated());
        $supplier->load(['contracts', 'projects']);
        
        return response()->json([
            'success' => true,
            'message' => 'Lieferant erfolgreich aktualisiert',
            'data' => $supplier
        ]);
    }
    
    /**
     * Lösche einen Lieferanten
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        // Prüfe ob Lieferant verknüpfte Daten hat
        if ($supplier->contracts()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Lieferant kann nicht gelöscht werden, da noch Verträge verknüpft sind'
            ], 400);
        }
        
        if ($supplier->projects()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Lieferant kann nicht gelöscht werden, da noch Projekte verknüpft sind'
            ], 400);
        }
        
        $supplier->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Lieferant erfolgreich gelöscht'
        ]);
    }
    
    /**
     * Ändere den Status eines Lieferanten
     */
    public function updateStatus(Request $request, Supplier $supplier): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,blocked',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $supplier->update(['status' => $request->status]);
        
        return response()->json([
            'success' => true,
            'message' => 'Lieferantenstatus erfolgreich geändert',
            'data' => $supplier
        ]);
    }
    
    /**
     * Hole Verträge eines Lieferanten
     */
    public function contracts(Supplier $supplier): JsonResponse
    {
        $contracts = $supplier->contracts()
            ->with(['solarPlant', 'billings' => function($query) {
                $query->latest('billing_month')->limit(12);
            }])
            ->get();
        
        $totalValue = $contracts->sum('contract_value');
        $activeContracts = $contracts->where('status', 'active')->count();
        $totalBillings = $contracts->flatMap->billings->sum('total_amount');
        
        return response()->json([
            'success' => true,
            'data' => $contracts,
            'summary' => [
                'total_contracts' => $contracts->count(),
                'active_contracts' => $activeContracts,
                'total_contract_value' => (float) $totalValue,
                'total_billings_12m' => (float) $totalBillings,
            ]
        ]);
    }
    
    /**
     * Hole Projekte eines Lieferanten
     */
    public function projects(Supplier $supplier): JsonResponse
    {
        $projects = $supplier->projects()
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
     * Hole Aufgaben eines Lieferanten
     */
    public function tasks(Supplier $supplier): JsonResponse
    {
        $tasks = $supplier->tasks()
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
     * Finanzielle Übersicht eines Lieferanten
     */
    public function financials(Supplier $supplier): JsonResponse
    {
        $contracts = $supplier->contracts()->with('billings')->get();
        
        // Berechne Gesamtabrechnungen der letzten 12 Monate
        $recentBillings = $contracts->flatMap->billings
            ->where('billing_month', '>=', now()->subMonths(12)->startOfMonth());
        
        $totalBillingAmount = $recentBillings->sum('total_amount');
        $averageMonthlyAmount = $recentBillings->count() > 0 
            ? $totalBillingAmount / min($recentBillings->count(), 12) 
            : 0;
        
        // Monatliche Aufschlüsselung
        $monthlyBreakdown = $recentBillings->groupBy(function($billing) {
            return $billing->billing_month->format('Y-m');
        })->map(function($monthBillings) {
            return [
                'total_amount' => (float) $monthBillings->sum('total_amount'),
                'billing_count' => $monthBillings->count(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => $supplier->only(['id', 'supplier_number', 'company_name']),
                'contract_summary' => [
                    'total_contracts' => $contracts->count(),
                    'active_contracts' => $contracts->where('status', 'active')->count(),
                    'total_contract_value' => (float) $contracts->sum('contract_value'),
                ],
                'billing_performance_12m' => [
                    'period' => [
                        'start' => now()->subMonths(12)->startOfMonth()->format('Y-m-d'),
                        'end' => now()->format('Y-m-d'),
                    ],
                    'total_amount' => (float) $totalBillingAmount,
                    'billing_count' => $recentBillings->count(),
                    'average_monthly_amount' => (float) $averageMonthlyAmount,
                ],
                'monthly_breakdown' => $monthlyBreakdown,
                'payment_analysis' => [
                    'payment_terms_days' => $supplier->payment_terms ?? 30,
                    'discount_percentage' => (float) ($supplier->discount_percentage ?? 0),
                ]
            ]
        ]);
    }
    
    /**
     * Performance-Analyse eines Lieferanten
     */
    public function performance(Supplier $supplier): JsonResponse
    {
        $projects = $supplier->projects()->with(['milestones', 'tasks'])->get();
        $contracts = $supplier->contracts()->with('billings')->get();
        
        // Projekt-Performance
        $completedProjects = $projects->where('status', 'completed');
        $avgProjectDuration = $completedProjects->count() > 0 
            ? $completedProjects->avg(function($project) {
                return $project->start_date && $project->actual_end_date 
                    ? $project->start_date->diffInDays($project->actual_end_date)
                    : null;
            }) 
            : 0;
        
        // Budget-Performance
        $budgetAccuracy = $completedProjects->count() > 0 
            ? $completedProjects->avg(function($project) {
                return $project->budget > 0 
                    ? (($project->budget - abs($project->actual_costs - $project->budget)) / $project->budget) * 100
                    : 0;
            })
            : 0;
        
        // Vertragstreue
        $contractReliability = $contracts->count() > 0 
            ? $contracts->where('status', 'active')->count() / $contracts->count() * 100
            : 0;
        
        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => $supplier->only(['id', 'supplier_number', 'company_name']),
                'project_performance' => [
                    'total_projects' => $projects->count(),
                    'completed_projects' => $completedProjects->count(),
                    'success_rate' => $projects->count() > 0 
                        ? round($completedProjects->count() / $projects->count() * 100, 2)
                        : 0,
                    'average_project_duration_days' => round($avgProjectDuration, 1),
                    'budget_accuracy_percentage' => round($budgetAccuracy, 2),
                ],
                'contract_performance' => [
                    'total_contracts' => $contracts->count(),
                    'active_contracts' => $contracts->where('status', 'active')->count(),
                    'contract_reliability_percentage' => round($contractReliability, 2),
                    'average_contract_value' => $contracts->count() > 0 
                        ? round($contracts->sum('contract_value') / $contracts->count(), 2)
                        : 0,
                ],
                'quality_metrics' => [
                    'on_time_delivery' => 85, // Beispielwert - könnte aus Tasks/Milestones berechnet werden
                    'customer_satisfaction' => 90, // Beispielwert - könnte aus Bewertungen kommen
                    'issue_resolution_time' => 2.5, // Beispielwert in Tagen
                ]
            ]
        ]);
    }
    
    /**
     * Hole verfügbare Optionen für Lieferanten
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'supplier_types' => [
                    'energy_provider' => 'Energieversorger',
                    'maintenance' => 'Wartung & Service',
                    'installation' => 'Installation',
                    'consulting' => 'Beratung',
                    'insurance' => 'Versicherung',
                    'other' => 'Sonstige'
                ],
                'statuses' => [
                    'active' => 'Aktiv',
                    'inactive' => 'Inaktiv',
                    'blocked' => 'Gesperrt'
                ],
                'countries' => [
                    'Deutschland' => 'Deutschland',
                    'Österreich' => 'Österreich',
                    'Schweiz' => 'Schweiz'
                ],
                'payment_terms' => [
                    14 => '14 Tage',
                    30 => '30 Tage',
                    60 => '60 Tage',
                    90 => '90 Tage'
                ]
            ]
        ]);
    }
}
