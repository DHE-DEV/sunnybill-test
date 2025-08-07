<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolarPlant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class SolarPlantApiController extends Controller
{
    /**
     * Liste alle Solaranlagen
     */
    public function index(Request $request): JsonResponse
    {
        $query = SolarPlant::with([
            'participations', 
            'solarInverters', 
            'solarModules', 
            'solarBatteries',
            'supplierContracts',
            'monthlyResults'
        ]);
        
        // Token-basierte Ressourcen-Filter anwenden
        if ($request->app_token) {
            $allowedIds = $request->app_token->getAllowedResourceIds('solar_plants');
            if ($allowedIds !== null) {
                $query->whereIn('id', $allowedIds);
            }
        }
        
        // Filter anwenden
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        
        if ($request->filled('min_capacity')) {
            $query->where('total_capacity_kw', '>=', $request->min_capacity);
        }
        
        if ($request->filled('max_capacity')) {
            $query->where('total_capacity_kw', '<=', $request->max_capacity);
        }
        
        if ($request->filled('commissioning_from')) {
            $query->where('commissioning_date', '>=', $request->commissioning_from);
        }
        
        if ($request->filled('commissioning_to')) {
            $query->where('commissioning_date', '<=', $request->commissioning_to);
        }
        
        // Suche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('plant_number', 'like', "%{$search}%")
                  ->orWhere('app_code', 'like', "%{$search}%");
            });
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);
        
        // Paginierung
        $perPage = min($request->get('per_page', 15), 100);
        $solarPlants = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $solarPlants->items(),
            'pagination' => [
                'current_page' => $solarPlants->currentPage(),
                'last_page' => $solarPlants->lastPage(),
                'per_page' => $solarPlants->perPage(),
                'total' => $solarPlants->total(),
            ]
        ]);
    }
    
    /**
     * Zeige eine spezifische Solaranlage
     */
    public function show(Request $request, SolarPlant $solarPlant): JsonResponse
    {
        // Prüfe Token-basierte Zugriffsberechtigung
        if ($request->app_token && !$request->app_token->canAccessSolarPlant($solarPlant->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Zugriff auf diese Solaranlage nicht erlaubt'
            ], 403);
        }
        
        $solarPlant->load([
            'participations.customer',
            'solarInverters',
            'solarModules', 
            'solarBatteries',
            'supplierContracts.supplier',
            'monthlyResults',
            'targetYields',
            'notes.user',
            'documents'
        ]);
        
        return response()->json([
            'success' => true,
            'data' => array_merge($solarPlant->toArray(), [
                'statistics' => [
                    'total_participation' => $solarPlant->total_participation,
                    'available_participation' => $solarPlant->available_participation,
                    'participations_count' => $solarPlant->participations_count,
                    'total_inverter_power' => $solarPlant->total_inverter_power,
                    'total_module_power' => $solarPlant->total_module_power,
                    'total_battery_capacity' => $solarPlant->total_battery_capacity,
                    'current_total_power' => $solarPlant->current_total_power,
                    'current_battery_soc' => $solarPlant->current_battery_soc,
                    'components_count' => $solarPlant->components_count,
                    'formatted_coordinates' => $solarPlant->formatted_coordinates,
                    'google_maps_url' => $solarPlant->google_maps_url,
                ]
            ])
        ]);
    }
    
    /**
     * Erstelle eine neue Solaranlage
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'plot_number' => 'nullable|string|max:255',
            'mastr_number_unit' => 'nullable|string|max:255',
            'mastr_registration_date_unit' => 'nullable|date',
            'mastr_number_eeg_plant' => 'nullable|string|max:255',
            'commissioning_date_eeg_plant' => 'nullable|date',
            'malo_id' => 'nullable|string|max:255',
            'melo_id' => 'nullable|string|max:255',
            'vnb_process_number' => 'nullable|string|max:255',
            'commissioning_date_unit' => 'nullable|date',
            'unit_commissioning_date' => 'nullable|date',
            'pv_soll_planning_date' => 'nullable|date',
            'pv_soll_project_number' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string',
            'installation_date' => 'nullable|date',
            'planned_installation_date' => 'nullable|date',
            'commissioning_date' => 'nullable|date',
            'planned_commissioning_date' => 'nullable|date',
            'total_capacity_kw' => 'nullable|numeric|min:0',
            'panel_count' => 'nullable|integer|min:0',
            'inverter_count' => 'nullable|integer|min:0',
            'battery_capacity_kwh' => 'nullable|numeric|min:0',
            'expected_annual_yield_kwh' => 'nullable|numeric|min:0',
            'total_investment' => 'nullable|numeric|min:0',
            'annual_operating_costs' => 'nullable|numeric|min:0',
            'feed_in_tariff_per_kwh' => 'nullable|numeric|min:0',
            'electricity_price_per_kwh' => 'nullable|numeric|min:0',
            'degradation_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'custom_field_1' => 'nullable|string|max:255',
            'custom_field_2' => 'nullable|string|max:255',
            'custom_field_3' => 'nullable|string|max:255',
            'custom_field_4' => 'nullable|string|max:255',
            'custom_field_5' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $solarPlant = SolarPlant::create($validator->validated());
        $solarPlant->load(['participations', 'solarInverters', 'solarModules', 'solarBatteries']);
        
        return response()->json([
            'success' => true,
            'message' => 'Solaranlage erfolgreich erstellt',
            'data' => $solarPlant
        ], 201);
    }
    
    /**
     * Aktualisiere eine Solaranlage
     */
    public function update(Request $request, SolarPlant $solarPlant): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:255',
            'plot_number' => 'nullable|string|max:255',
            'mastr_number_unit' => 'nullable|string|max:255',
            'mastr_registration_date_unit' => 'nullable|date',
            'mastr_number_eeg_plant' => 'nullable|string|max:255',
            'commissioning_date_eeg_plant' => 'nullable|date',
            'malo_id' => 'nullable|string|max:255',
            'melo_id' => 'nullable|string|max:255',
            'vnb_process_number' => 'nullable|string|max:255',
            'commissioning_date_unit' => 'nullable|date',
            'unit_commissioning_date' => 'nullable|date',
            'pv_soll_planning_date' => 'nullable|date',
            'pv_soll_project_number' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string',
            'installation_date' => 'nullable|date',
            'planned_installation_date' => 'nullable|date',
            'commissioning_date' => 'nullable|date',
            'planned_commissioning_date' => 'nullable|date',
            'total_capacity_kw' => 'nullable|numeric|min:0',
            'panel_count' => 'nullable|integer|min:0',
            'inverter_count' => 'nullable|integer|min:0',
            'battery_capacity_kwh' => 'nullable|numeric|min:0',
            'expected_annual_yield_kwh' => 'nullable|numeric|min:0',
            'total_investment' => 'nullable|numeric|min:0',
            'annual_operating_costs' => 'nullable|numeric|min:0',
            'feed_in_tariff_per_kwh' => 'nullable|numeric|min:0',
            'electricity_price_per_kwh' => 'nullable|numeric|min:0',
            'degradation_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'custom_field_1' => 'nullable|string|max:255',
            'custom_field_2' => 'nullable|string|max:255',
            'custom_field_3' => 'nullable|string|max:255',
            'custom_field_4' => 'nullable|string|max:255',
            'custom_field_5' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $solarPlant->update($validator->validated());
        $solarPlant->load(['participations', 'solarInverters', 'solarModules', 'solarBatteries']);
        
        return response()->json([
            'success' => true,
            'message' => 'Solaranlage erfolgreich aktualisiert',
            'data' => $solarPlant
        ]);
    }
    
    /**
     * Lösche eine Solaranlage
     */
    public function destroy(SolarPlant $solarPlant): JsonResponse
    {
        $solarPlant->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Solaranlage erfolgreich gelöscht'
        ]);
    }
    
    /**
     * Hole Komponenten einer Solaranlage
     */
    public function components(SolarPlant $solarPlant): JsonResponse
    {
        $components = [
            'inverters' => $solarPlant->solarInverters,
            'modules' => $solarPlant->solarModules,
            'batteries' => $solarPlant->solarBatteries,
            'statistics' => [
                'total_inverter_power' => $solarPlant->total_inverter_power,
                'total_module_power' => $solarPlant->total_module_power,
                'total_battery_capacity' => $solarPlant->total_battery_capacity,
                'components_count' => $solarPlant->components_count,
            ]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $components
        ]);
    }
    
    /**
     * Hole Kundenbeteiligungen einer Solaranlage
     */
    public function participations(SolarPlant $solarPlant): JsonResponse
    {
        $participations = $solarPlant->participations()->with('customer')->get();
        
        return response()->json([
            'success' => true,
            'data' => [
                'participations' => $participations,
                'statistics' => [
                    'total_participation' => $solarPlant->total_participation,
                    'available_participation' => $solarPlant->available_participation,
                    'participations_count' => $solarPlant->participations_count,
                ]
            ]
        ]);
    }
    
    /**
     * Hole monatliche Ergebnisse einer Solaranlage
     */
    public function monthlyResults(Request $request, SolarPlant $solarPlant): JsonResponse
    {
        $query = $solarPlant->monthlyResults();
        
        // Filter nach Jahr
        if ($request->filled('year')) {
            $query->whereYear('month', $request->year);
        }
        
        // Filter nach Monat
        if ($request->filled('month')) {
            $query->whereMonth('month', $request->month);
        }
        
        $results = $query->orderBy('month', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
    
    /**
     * Hole Statistiken einer Solaranlage
     */
    public function statistics(SolarPlant $solarPlant): JsonResponse
    {
        $statistics = [
            'basic' => [
                'plant_number' => $solarPlant->plant_number,
                'app_code' => $solarPlant->app_code,
                'total_capacity_kw' => $solarPlant->total_capacity_kw,
                'panel_count' => $solarPlant->panel_count,
                'inverter_count' => $solarPlant->inverter_count,
                'battery_capacity_kwh' => $solarPlant->battery_capacity_kwh,
                'expected_annual_yield_kwh' => $solarPlant->expected_annual_yield_kwh,
            ],
            'financial' => [
                'total_investment' => $solarPlant->total_investment,
                'annual_operating_costs' => $solarPlant->annual_operating_costs,
                'feed_in_tariff_per_kwh' => $solarPlant->feed_in_tariff_per_kwh,
                'electricity_price_per_kwh' => $solarPlant->electricity_price_per_kwh,
                'degradation_rate' => $solarPlant->degradation_rate,
                'formatted_total_investment' => $solarPlant->formatted_total_investment,
                'formatted_annual_operating_costs' => $solarPlant->formatted_annual_operating_costs,
                'formatted_feed_in_tariff' => $solarPlant->formatted_feed_in_tariff,
                'formatted_electricity_price' => $solarPlant->formatted_electricity_price,
                'formatted_degradation_rate' => $solarPlant->formatted_degradation_rate,
            ],
            'participations' => [
                'total_participation' => $solarPlant->total_participation,
                'available_participation' => $solarPlant->available_participation,
                'participations_count' => $solarPlant->participations_count,
            ],
            'components' => [
                'total_inverter_power' => $solarPlant->total_inverter_power,
                'total_module_power' => $solarPlant->total_module_power,
                'total_battery_capacity' => $solarPlant->total_battery_capacity,
                'current_total_power' => $solarPlant->current_total_power,
                'current_battery_soc' => $solarPlant->current_battery_soc,
                'components_count' => $solarPlant->components_count,
            ],
            'location' => [
                'latitude' => $solarPlant->latitude,
                'longitude' => $solarPlant->longitude,
                'has_coordinates' => $solarPlant->hasCoordinates(),
                'formatted_coordinates' => $solarPlant->formatted_coordinates,
                'google_maps_url' => $solarPlant->google_maps_url,
                'open_street_map_url' => $solarPlant->open_street_map_url,
            ]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}
