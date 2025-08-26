<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LeadApiController extends Controller
{
    /**
     * Liste aller Leads
     */
    public function index(Request $request): JsonResponse
    {
        // Paginierung Parameter
        $perPage = min($request->get('per_page', 15), 100);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        // Base SQL für Leads (nur customer_type = 'lead')
        $whereClause = "deleted_at IS NULL AND customer_type = 'lead'";
        $whereParams = [];
        
        // Filter anwenden
        if ($request->filled('ranking')) {
            $whereClause .= ' AND ranking = ?';
            $whereParams[] = $request->ranking;
        }
        
        if ($request->filled('city')) {
            $whereClause .= ' AND city LIKE ?';
            $whereParams[] = '%' . $request->city . '%';
        }
        
        if ($request->filled('is_active')) {
            $whereClause .= ' AND is_active = ?';
            $whereParams[] = $request->boolean('is_active') ? 1 : 0;
        }
        
        // Suche
        if ($request->filled('search')) {
            $search = $request->search;
            $whereClause .= ' AND (name LIKE ? OR contact_person LIKE ? OR email LIKE ? OR customer_number LIKE ? OR phone LIKE ? OR city LIKE ?)';
            $searchParam = "%{$search}%";
            $whereParams = array_merge($whereParams, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validiere Sortierfelder zur Sicherheit
        $allowedSorts = ['id', 'name', 'created_at', 'updated_at', 'customer_number', 'city', 'ranking'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';
        
        // Gesamtanzahl für Paginierung
        $countSql = "SELECT COUNT(*) as total FROM customers WHERE {$whereClause}";
        $totalResult = DB::select($countSql, $whereParams);
        $total = $totalResult[0]->total;
        $lastPage = ceil($total / $perPage);
        
        // Hauptabfrage - Lead-spezifische Felder
        $sql = "SELECT 
            id, name, contact_person, department, customer_number,
            email, phone, website, street, address_line_2, postal_code,
            city, state, country, country_code, notes, 
            is_active, deactivated_at, customer_type, ranking,
            created_at, updated_at
        FROM customers 
        WHERE {$whereClause} 
        ORDER BY {$sortBy} {$sortDirection} 
        LIMIT {$perPage} OFFSET {$offset}";
        
        $result = DB::select($sql, $whereParams);
        
        // Konvertiere zu Array
        $leads = [];
        foreach ($result as $row) {
            $leads[] = (array) $row;
        }
        
        return response()->json([
            'success' => true,
            'data' => $leads,
            'pagination' => [
                'current_page' => (int) $page,
                'last_page' => (int) $lastPage,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Einzelnen Lead anzeigen
     */
    public function show(Request $request, string $leadId): JsonResponse
    {
        // Prüfe ob Lead existiert und customer_type = 'lead' ist
        $lead = Customer::where('id', $leadId)
            ->where('customer_type', 'lead')
            ->first();

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead nicht gefunden'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $lead
        ]);
    }

    /**
     * Neuen Lead erstellen
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'street' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|max:3',
            'ranking' => 'nullable|in:A,B,C,D,E',
            'notes' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $validator->validated();
        
        // Stelle sicher, dass customer_type auf 'lead' gesetzt wird
        $data['customer_type'] = 'lead';
        
        // Standard-Werte setzen
        $data['country'] = $data['country'] ?? 'Deutschland';
        $data['country_code'] = $data['country_code'] ?? 'DE';
        $data['is_active'] = $data['is_active'] ?? true;
        
        try {
            $lead = Customer::create($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Lead erfolgreich erstellt',
                'data' => $lead
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Erstellen des Leads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lead aktualisieren
     */
    public function update(Request $request, string $leadId): JsonResponse
    {
        // Prüfe ob Lead existiert und customer_type = 'lead' ist
        $lead = Customer::where('id', $leadId)
            ->where('customer_type', 'lead')
            ->first();

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead nicht gefunden'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'street' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'country_code' => 'nullable|string|max:3',
            'ranking' => 'nullable|in:A,B,C,D,E',
            'notes' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $validator->validated();
        
        // Stelle sicher, dass customer_type nicht geändert wird
        unset($data['customer_type']);
        
        try {
            $lead->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Lead erfolgreich aktualisiert',
                'data' => $lead->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Aktualisieren des Leads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lead löschen
     */
    public function destroy(string $leadId): JsonResponse
    {
        // Prüfe ob Lead existiert und customer_type = 'lead' ist
        $lead = Customer::where('id', $leadId)
            ->where('customer_type', 'lead')
            ->first();

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead nicht gefunden'
            ], 404);
        }

        try {
            $lead->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Lead erfolgreich gelöscht'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Löschen des Leads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lead zu Kunde konvertieren
     */
    public function convertToCustomer(string $leadId): JsonResponse
    {
        // Prüfe ob Lead existiert und customer_type = 'lead' ist
        $lead = Customer::where('id', $leadId)
            ->where('customer_type', 'lead')
            ->first();

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead nicht gefunden'
            ], 404);
        }

        try {
            $lead->update(['customer_type' => 'business']);
            
            return response()->json([
                'success' => true,
                'message' => 'Lead erfolgreich zu Kunde konvertiert',
                'data' => $lead->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Konvertieren des Leads',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lead-Status aktualisieren (aktiv/inaktiv)
     */
    public function updateStatus(Request $request, string $leadId): JsonResponse
    {
        // Prüfe ob Lead existiert und customer_type = 'lead' ist
        $lead = Customer::where('id', $leadId)
            ->where('customer_type', 'lead')
            ->first();

        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead nicht gefunden'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lead->update(['is_active' => $request->is_active]);
            
            $status = $request->is_active ? 'aktiviert' : 'deaktiviert';
            
            return response()->json([
                'success' => true,
                'message' => "Lead erfolgreich {$status}",
                'data' => $lead->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Aktualisieren des Lead-Status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API-Optionen für Leads
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'rankings' => [
                    'A' => 'Heißer Lead (A)',
                    'B' => 'Warmer Lead (B)',
                    'C' => 'Kalter Lead (C)',
                    'D' => 'Unqualifiziert (D)',
                    'E' => 'Nicht interessiert (E)',
                ],
                'countries' => [
                    'Deutschland' => 'Deutschland',
                    'Österreich' => 'Österreich',
                    'Schweiz' => 'Schweiz',
                ],
                'country_codes' => [
                    'DE' => 'Deutschland',
                    'AT' => 'Österreich',
                    'CH' => 'Schweiz',
                ],
                'boolean_options' => [
                    'is_active' => [
                        true => 'Aktiv',
                        false => 'Inaktiv',
                    ]
                ]
            ]
        ]);
    }
}
