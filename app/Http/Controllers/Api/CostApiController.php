<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SolarPlant;
use App\Models\SolarPlantBilling;
use App\Models\SupplierContractBilling;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CostApiController extends Controller
{
    /**
     * Kostenübersicht (alle Bereiche)
     */
    public function overview(Request $request): JsonResponse
    {
        // Projekt-Kosten
        $projectCosts = Project::selectRaw('
            SUM(budget) as total_budget,
            SUM(actual_costs) as total_actual_costs,
            COUNT(*) as project_count
        ')->first();
        
        // Solaranlagen-Investitionen
        $solarPlantCosts = SolarPlant::selectRaw('
            SUM(total_investment) as total_investment,
            SUM(annual_operating_costs) as total_annual_operating_costs,
            COUNT(*) as plant_count
        ')->first();
        
        // Abrechnungen der letzten 12 Monate
        $startDate = now()->subMonths(12)->startOfMonth();
        $endDate = now()->endOfMonth();
        
        $solarPlantBillings = SolarPlantBilling::whereBetween('billing_month', [$startDate, $endDate])
            ->selectRaw('
                SUM(total_income) as total_billing_income,
                SUM(total_costs) as total_billing_costs,
                SUM(net_result) as total_net_result,
                COUNT(*) as billing_count
            ')->first();
        
        $supplierBillings = SupplierContractBilling::whereBetween('billing_month', [$startDate, $endDate])
            ->selectRaw('
                SUM(total_amount) as total_supplier_payments,
                COUNT(*) as supplier_billing_count
            ')->first();
        
        // Monatliche Trends (letzte 12 Monate)
        $monthlyTrends = SolarPlantBilling::selectRaw('
            DATE_FORMAT(billing_month, "%Y-%m") as month,
            SUM(total_income) as income,
            SUM(total_costs) as costs,
            SUM(net_result) as net_result
        ')
        ->whereBetween('billing_month', [$startDate, $endDate])
        ->groupBy('month')
        ->orderBy('month')
        ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'project_costs' => [
                    'total_budget' => (float) ($projectCosts->total_budget ?? 0),
                    'total_actual_costs' => (float) ($projectCosts->total_actual_costs ?? 0),
                    'budget_utilization' => $projectCosts->total_budget > 0 
                        ? round(($projectCosts->total_actual_costs / $projectCosts->total_budget) * 100, 2)
                        : 0,
                    'project_count' => $projectCosts->project_count ?? 0,
                ],
                'solar_plant_investments' => [
                    'total_investment' => (float) ($solarPlantCosts->total_investment ?? 0),
                    'total_annual_operating_costs' => (float) ($solarPlantCosts->total_annual_operating_costs ?? 0),
                    'plant_count' => $solarPlantCosts->plant_count ?? 0,
                ],
                'billing_performance' => [
                    'period' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                    ],
                    'solar_plants' => [
                        'total_income' => (float) ($solarPlantBillings->total_billing_income ?? 0),
                        'total_costs' => (float) ($solarPlantBillings->total_billing_costs ?? 0),
                        'net_result' => (float) ($solarPlantBillings->total_net_result ?? 0),
                        'billing_count' => $solarPlantBillings->billing_count ?? 0,
                    ],
                    'supplier_payments' => [
                        'total_amount' => (float) ($supplierBillings->total_supplier_payments ?? 0),
                        'billing_count' => $supplierBillings->supplier_billing_count ?? 0,
                    ],
                ],
                'monthly_trends' => $monthlyTrends,
                'summary' => [
                    'total_income_12m' => (float) ($solarPlantBillings->total_billing_income ?? 0),
                    'total_costs_12m' => (float) ($solarPlantBillings->total_billing_costs ?? 0) + (float) ($supplierBillings->total_supplier_payments ?? 0),
                    'net_result_12m' => (float) ($solarPlantBillings->total_net_result ?? 0) - (float) ($supplierBillings->total_supplier_payments ?? 0),
                ]
            ]
        ]);
    }
    
    /**
     * Projektkosten-Details
     */
    public function projectCosts(Request $request, Project $project): JsonResponse
    {
        $project->load(['customer', 'supplier', 'solarPlant', 'tasks']);
        
        // Task-basierte Kostenberechnung
        $taskCosts = $project->tasks->sum(function($task) {
            return ($task->actual_minutes ?? 0) * 0.5; // Beispiel: 0.5€ pro Minute
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'project' => $project->only(['id', 'name', 'project_number', 'status']),
                'costs' => [
                    'budget' => (float) ($project->budget ?? 0),
                    'actual_costs' => (float) ($project->actual_costs ?? 0),
                    'task_based_costs' => $taskCosts,
                    'remaining_budget' => (float) (($project->budget ?? 0) - ($project->actual_costs ?? 0)),
                    'budget_utilization_percentage' => $project->budget > 0 
                        ? round(($project->actual_costs / $project->budget) * 100, 2)
                        : 0,
                ],
                'breakdown' => [
                    'labor_costs' => $taskCosts,
                    'material_costs' => (float) ($project->actual_costs ?? 0) - $taskCosts,
                    'overhead_percentage' => 15, // Beispiel
                ],
                'tasks_summary' => [
                    'total_tasks' => $project->tasks->count(),
                    'completed_tasks' => $project->tasks->where('status', 'completed')->count(),
                    'total_hours' => round($project->tasks->sum('actual_minutes') / 60, 2),
                    'estimated_hours' => round($project->tasks->sum('estimated_minutes') / 60, 2),
                ]
            ]
        ]);
    }
    
    /**
     * Kosten zu Projekt hinzufügen
     */
    public function addProjectCost(Request $request, Project $project): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'date' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Aktualisiere actual_costs
        $newActualCosts = ($project->actual_costs ?? 0) + $request->amount;
        $project->update(['actual_costs' => $newActualCosts]);
        
        // Hier könntest du auch eine separate cost_entries Tabelle verwenden
        // für detaillierte Kostenverfolgung
        
        return response()->json([
            'success' => true,
            'message' => 'Kosten erfolgreich hinzugefügt',
            'data' => [
                'added_amount' => (float) $request->amount,
                'new_total_costs' => (float) $newActualCosts,
                'remaining_budget' => (float) (($project->budget ?? 0) - $newActualCosts),
            ]
        ]);
    }
    
    /**
     * Solaranlagen-Kosten
     */
    public function solarPlantCosts(Request $request, SolarPlant $solarPlant): JsonResponse
    {
        $solarPlant->load(['participations', 'supplierContracts', 'billings']);
        
        // Letzten 12 Monate Abrechnungen
        $startDate = now()->subMonths(12)->startOfMonth();
        $recentBillings = $solarPlant->billings()
            ->where('billing_month', '>=', $startDate)
            ->orderBy('billing_month', 'desc')
            ->get();
        
        $totalIncome = $recentBillings->sum('total_income');
        $totalCosts = $recentBillings->sum('total_costs');
        $netResult = $recentBillings->sum('net_result');
        
        return response()->json([
            'success' => true,
            'data' => [
                'solar_plant' => $solarPlant->only(['id', 'name', 'plant_number', 'location']),
                'investment_costs' => [
                    'total_investment' => (float) ($solarPlant->total_investment ?? 0),
                    'annual_operating_costs' => (float) ($solarPlant->annual_operating_costs ?? 0),
                ],
                'performance_12m' => [
                    'period' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => now()->format('Y-m-d'),
                    ],
                    'total_income' => (float) $totalIncome,
                    'total_costs' => (float) $totalCosts,
                    'net_result' => (float) $netResult,
                    'billing_count' => $recentBillings->count(),
                ],
                'roi_calculation' => [
                    'annual_net_result' => (float) $netResult,
                    'roi_percentage' => $solarPlant->total_investment > 0 
                        ? round(($netResult / $solarPlant->total_investment) * 100, 2)
                        : 0,
                    'payback_years' => $netResult > 0 
                        ? round($solarPlant->total_investment / $netResult, 1)
                        : null,
                ],
                'monthly_billings' => $recentBillings->map(function($billing) {
                    return [
                        'month' => $billing->billing_month->format('Y-m'),
                        'income' => (float) $billing->total_income,
                        'costs' => (float) $billing->total_costs,
                        'net_result' => (float) $billing->net_result,
                    ];
                }),
            ]
        ]);
    }
    
    /**
     * Abrechnungen einer Solaranlage
     */
    public function solarPlantBillings(Request $request, SolarPlant $solarPlant): JsonResponse
    {
        $query = $solarPlant->billings();
        
        // Filter nach Jahr
        if ($request->filled('year')) {
            $query->whereYear('billing_month', $request->year);
        }
        
        // Filter nach Zeitraum
        if ($request->filled('start_month')) {
            $query->where('billing_month', '>=', $request->start_month . '-01');
        }
        
        if ($request->filled('end_month')) {
            $query->where('billing_month', '<=', $request->end_month . '-31');
        }
        
        $billings = $query->orderBy('billing_month', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $billings,
            'summary' => [
                'total_income' => (float) $billings->sum('total_income'),
                'total_costs' => (float) $billings->sum('total_costs'),
                'net_result' => (float) $billings->sum('net_result'),
                'billing_count' => $billings->count(),
                'average_monthly_income' => $billings->count() > 0 
                    ? round($billings->sum('total_income') / $billings->count(), 2)
                    : 0,
            ]
        ]);
    }
    
    /**
     * Kostenberichte mit Zeitraum-Filter
     */
    public function reports(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'report_type' => 'nullable|in:monthly,quarterly,yearly',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)
            : now()->subYear()->startOfMonth();
        
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : now()->endOfMonth();
        
        $reportType = $request->get('report_type', 'monthly');
        
        // Gruppierung basierend auf Report-Typ
        $dateFormat = match($reportType) {
            'yearly' => '%Y',
            'quarterly' => '%Y-Q%q',
            default => '%Y-%m'
        };
        
        // Solaranlagen-Abrechnungen
        $solarReports = SolarPlantBilling::selectRaw("
            DATE_FORMAT(billing_month, '{$dateFormat}') as period,
            SUM(total_income) as income,
            SUM(total_costs) as costs,
            SUM(net_result) as net_result,
            COUNT(*) as billing_count
        ")
        ->whereBetween('billing_month', [$startDate, $endDate])
        ->groupBy('period')
        ->orderBy('period')
        ->get();
        
        // Lieferanten-Abrechnungen
        $supplierReports = SupplierContractBilling::selectRaw("
            DATE_FORMAT(billing_month, '{$dateFormat}') as period,
            SUM(total_amount) as amount,
            COUNT(*) as billing_count
        ")
        ->whereBetween('billing_month', [$startDate, $endDate])
        ->groupBy('period')
        ->orderBy('period')
        ->get();
        
        // Projekt-Kosten (basierend auf Erstellungsdatum)
        $projectReports = Project::selectRaw("
            DATE_FORMAT(created_at, '{$dateFormat}') as period,
            SUM(budget) as budget,
            SUM(actual_costs) as actual_costs,
            COUNT(*) as project_count
        ")
        ->whereBetween('created_at', [$startDate, $endDate])
        ->groupBy('period')
        ->orderBy('period')
        ->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'parameters' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'report_type' => $reportType,
                ],
                'solar_plant_performance' => $solarReports,
                'supplier_costs' => $supplierReports,
                'project_costs' => $projectReports,
                'summary' => [
                    'total_solar_income' => (float) $solarReports->sum('income'),
                    'total_solar_costs' => (float) $solarReports->sum('costs'),
                    'total_supplier_costs' => (float) $supplierReports->sum('amount'),
                    'total_project_budget' => (float) $projectReports->sum('budget'),
                    'total_project_costs' => (float) $projectReports->sum('actual_costs'),
                ]
            ]
        ]);
    }
    
    /**
     * Hole verfügbare Optionen für Kosten
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'cost_categories' => [
                    'labor' => 'Arbeitskosten',
                    'materials' => 'Materialkosten',
                    'equipment' => 'Ausrüstung',
                    'transport' => 'Transport',
                    'permits' => 'Genehmigungen',
                    'maintenance' => 'Wartung',
                    'insurance' => 'Versicherung',
                    'other' => 'Sonstiges'
                ],
                'report_types' => [
                    'monthly' => 'Monatlich',
                    'quarterly' => 'Quartalsweise',
                    'yearly' => 'Jährlich'
                ],
                'currencies' => [
                    'EUR' => 'Euro (€)',
                ]
            ]
        ]);
    }
}
