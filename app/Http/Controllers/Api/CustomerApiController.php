<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CustomerApiController extends Controller
{
    /**
     * TEST ENDPOINT - Um zu prüfen ob Controller-Änderungen wirken
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'TEST: Controller wurde erfolgreich geändert!',
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * DEBUG ENDPOINT - Zeigt direkte DB-Abfrage
     */
    public function debug(): JsonResponse
    {
        // Direkte Datenbankabfrage - komplett ohne Model
        $customer = DB::table('customers')
            ->where('id', '019889c5-2f74-7046-9674-289de55c684f')
            ->select(['id', 'name', 'company_name', 'street', 'postal_code', 'city', 'email', 'phone'])
            ->first();
            
        return response()->json([
            'success' => true,
            'message' => 'DEBUG: Direkte DB-Abfrage',
            'data' => $customer,
            'data_type' => gettype($customer),
        ]);
    }
    
    /**
     * DEBUG INDEX - Mit Query Builder statt direkte SQL
     */
    public function debugIndex(Request $request): JsonResponse
    {
        dump("debugIndex wird ausgeführt");
        
        // Mit Query Builder (DB::table) - nicht Eloquent
        $customers = DB::table('customers')
            ->whereNull('deleted_at')
            ->select([
                'id', 'name', 'company_name', 'contact_person', 'department', 'customer_number',
                'email', 'phone', 'fax', 'website', 'street', 'address_line_2', 'postal_code',
                'city', 'state', 'country', 'country_code', 'tax_number', 'vat_id',
                'payment_terms', 'payment_days', 'bank_name', 'iban', 'bic', 'account_holder',
                'payment_method', 'notes', 'custom_field_1', 'custom_field_2', 'custom_field_3',
                'custom_field_4', 'custom_field_5', 'is_active', 'deactivated_at',
                'customer_type', 'ranking', 'lexoffice_id', 'lexoffice_synced_at',
                'lexware_version', 'lexware_json', 'created_at', 'updated_at'
            ])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Konvertiere zu Array
        $customersArray = $customers->toArray();
        
        return response()->json([
            'success' => true,
            'message' => 'DEBUG INDEX: Query Builder (DB::table)',
            'data' => $customersArray,
            'count' => count($customersArray),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * RAW DATA - Komplett ohne Laravel Eloquent/Model Bezug
     */
    public function raw(): JsonResponse
    {
        // Komplett rohe Datenbankabfrage mit json_encode
        $result = DB::select('SELECT id, name, company_name, street, postal_code, city, email, phone FROM customers WHERE deleted_at IS NULL LIMIT 5');
        
        // Direkt als Array konvertieren ohne Laravel Collection
        $rawData = [];
        foreach ($result as $row) {
            $rawData[] = (array) $row;
        }
        
        return new JsonResponse([
            'success' => true,
            'message' => 'RAW: Direkte SQL-Abfrage ohne Laravel Collections',
            'data' => $rawData,
            'data_type' => gettype($rawData),
            'count' => count($rawData),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * SHOW SQL - Zeigt den SQL-Befehl, der für api/app/customers ausgeführt wird
     */
    public function showSql(Request $request): JsonResponse
    {
        // Paginierung Parameter (identisch zur index() Methode)
        $perPage = min($request->get('per_page', 15), 100);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        // Base SQL für Kunden (identisch zur index() Methode)
        $whereClause = 'deleted_at IS NULL';
        $whereParams = [];
        
        // Filter anwenden (identisch zur index() Methode)
        if ($request->filled('customer_type')) {
            $whereClause .= ' AND customer_type = ?';
            $whereParams[] = $request->customer_type;
        }
        
        if ($request->filled('city')) {
            $whereClause .= ' AND city LIKE ?';
            $whereParams[] = '%' . $request->city . '%';
        }
        
        if ($request->filled('is_active')) {
            $whereClause .= ' AND is_active = ?';
            $whereParams[] = $request->boolean('is_active') ? 1 : 0;
        }
        
        // Suche (identisch zur index() Methode)
        if ($request->filled('search')) {
            $search = $request->search;
            $whereClause .= ' AND (name LIKE ? OR company_name LIKE ? OR contact_person LIKE ? OR email LIKE ? OR customer_number LIKE ? OR phone LIKE ? OR city LIKE ?)';
            $searchParam = "%{$search}%";
            $whereParams = array_merge($whereParams, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        // Sortierung (identisch zur index() Methode)
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validiere Sortierfelder zur Sicherheit (identisch zur index() Methode)
        $allowedSorts = ['id', 'name', 'company_name', 'created_at', 'updated_at', 'customer_number', 'city'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';
        
        // COUNT SQL (identisch zur index() Methode)
        $countSql = "SELECT COUNT(*) as total FROM customers WHERE {$whereClause}";
        
        // MAIN SQL (identisch zur index() Methode)
        $mainSql = "SELECT 
            id, name, company_name, contact_person, department, customer_number,
            email, phone, fax, website, street, address_line_2, postal_code,
            city, state, country, country_code, tax_number, vat_id,
            payment_terms, payment_days, bank_name, iban, bic, account_holder,
            payment_method, notes, custom_field_1, custom_field_2, custom_field_3,
            custom_field_4, custom_field_5, is_active, deactivated_at,
            customer_type, ranking, lexoffice_id, lexoffice_synced_at,
            lexware_version, lexware_json, created_at, updated_at
        FROM customers 
        WHERE {$whereClause} 
        ORDER BY {$sortBy} {$sortDirection} 
        LIMIT {$perPage} OFFSET {$offset}";
        
        return response()->json([
            'success' => true,
            'message' => 'SQL-Befehle für api/app/customers',
            'request_parameters' => [
                'per_page' => $perPage,
                'page' => $page,
                'offset' => $offset,
                'customer_type' => $request->get('customer_type'),
                'city' => $request->get('city'),
                'is_active' => $request->get('is_active'),
                'search' => $request->get('search'),
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'count_sql' => $countSql,
            'count_params' => $whereParams,
            'main_sql' => $mainSql,
            'main_params' => $whereParams,
            'where_clause' => $whereClause,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Liste aller Kunden - NUR ROHDATEN AUS DATENBANK
     */
    public function index(Request $request): JsonResponse
    {
        
        // Paginierung Parameter
        $perPage = min($request->get('per_page', 15), 100);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        
        // Base SQL für Kunden
        $whereClause = 'deleted_at IS NULL';
        $whereParams = [];
        
        // Filter anwenden
        if ($request->filled('customer_type')) {
            $whereClause .= ' AND customer_type = ?';
            $whereParams[] = $request->customer_type;
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
            $whereClause .= ' AND (name LIKE ? OR company_name LIKE ? OR contact_person LIKE ? OR email LIKE ? OR customer_number LIKE ? OR phone LIKE ? OR city LIKE ?)';
            $searchParam = "%{$search}%";
            $whereParams = array_merge($whereParams, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        // Sortierung
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        // Validiere Sortierfelder zur Sicherheit
        $allowedSorts = ['id', 'name', 'company_name', 'created_at', 'updated_at', 'customer_number', 'city'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';
        
        // Gesamtanzahl für Paginierung
        $countSql = "SELECT COUNT(*) as total FROM customers WHERE {$whereClause}";
        $totalResult = DB::select($countSql, $whereParams);
        $total = $totalResult[0]->total;
        $lastPage = ceil($total / $perPage);
        
        // Hauptabfrage
        $sql = "SELECT 
            id, name, company_name, contact_person, department, customer_number,
            email, phone, fax, website, street, address_line_2, postal_code,
            city, state, country, country_code, tax_number, vat_id,
            payment_terms, payment_days, bank_name, iban, bic, account_holder,
            payment_method, notes, custom_field_1, custom_field_2, custom_field_3,
            custom_field_4, custom_field_5, is_active, deactivated_at,
            customer_type, ranking, lexoffice_id, lexoffice_synced_at,
            lexware_version, lexware_json, created_at, updated_at
        FROM customers 
        WHERE {$whereClause} 
        ORDER BY {$sortBy} {$sortDirection} 
        LIMIT {$perPage} OFFSET {$offset}";
        
        $result = DB::select($sql, $whereParams);
        
        // Konvertiere zu Array um sicherzustellen, dass keine Laravel Collections verwendet werden
        $customers = [];
        foreach ($result as $row) {
            $customers[] = (array) $row;
        }
        
        return response()->json([
            'success' => true,
            'data' => $customers,
            'pagination' => [
                'current_page' => (int) $page,
                'last_page' => (int) $lastPage,
                'per_page' => (int) $perPage,
                'total' => (int) $total,
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Einzelnen Kunden anzeigen
     */
    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Neuen Kunden erstellen
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $customer = Customer::create($validator->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich erstellt',
            'data' => $customer
        ], 201);
    }

    /**
     * Kunden aktualisieren
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'street' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $customer->update($validator->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich aktualisiert',
            'data' => $customer
        ]);
    }

    /**
     * Kunden löschen
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Kunde erfolgreich gelöscht'
        ]);
    }
}
