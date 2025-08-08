<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PhoneNumber\StorePhoneNumberRequest;
use App\Http\Requests\Api\PhoneNumber\UpdatePhoneNumberRequest;
use App\Http\Resources\PhoneNumberResource;
use App\Models\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * @group Phone Numbers
 *
 * APIs für das Verwalten von Telefonnummern
 */
class PhoneNumberApiController extends Controller
{
    /**
     * Alle Telefonnummern abrufen
     * 
     * Ruft alle Telefonnummern ab, mit optionaler Filterung nach Typ, Besitzer und anderen Parametern.
     *
     * @queryParam type string Filter nach Telefonnummer-Typ (business, private, mobile). Example: mobile
     * @queryParam phoneable_type string Filter nach Besitzer-Typ (z.B. App\Models\User). Example: App\Models\User
     * @queryParam phoneable_id string Filter nach Besitzer-ID. Example: 123e4567-e89b-12d3-a456-426614174000
     * @queryParam is_primary boolean Filter nach Hauptnummern (1 oder 0). Example: 1
     * @queryParam is_favorite boolean Filter nach Favoriten (1 oder 0). Example: 1
     * @queryParam search string Suche in Telefonnummer oder Label. Example: 030
     * @queryParam per_page integer Anzahl Ergebnisse pro Seite (max 100). Example: 15
     * @queryParam page integer Seitennummer. Example: 1
     * @queryParam sort string Sortierung (created_at, phone_number, type, is_primary). Example: created_at
     * @queryParam direction string Sortierrichtung (asc, desc). Example: desc
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": "123e4567-e89b-12d3-a456-426614174000",
     *       "phoneable_id": "456e7890-e89b-12d3-a456-426614174001",
     *       "phoneable_type": "App\\Models\\User",
     *       "phone_number": "+49 30 123456789",
     *       "formatted_number": "+49 30 123 456 789",
     *       "type": "business",
     *       "type_label": "Geschäftlich",
     *       "label": "Büro Berlin",
     *       "display_label": "Geschäftlich (Büro Berlin) [Hauptnummer]",
     *       "is_primary": true,
     *       "is_favorite": false,
     *       "sort_order": 1,
     *       "created_at": "2025-01-08T10:30:00Z",
     *       "updated_at": "2025-01-08T10:30:00Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://api.example.com/phone-numbers?page=1",
     *     "last": "http://api.example.com/phone-numbers?page=3",
     *     "prev": null,
     *     "next": "http://api.example.com/phone-numbers?page=2"
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 3,
     *     "per_page": 15,
     *     "to": 15,
     *     "total": 45
     *   }
     * }
     *
     * @response 422 scenario="Validation Error" {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "per_page": ["The per page field must not be greater than 100."]
     *   }
     * }
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        try {
            // Validierung der Query-Parameter
            $request->validate([
                'type' => 'sometimes|string|in:business,private,mobile',
                'phoneable_type' => 'sometimes|string',
                'phoneable_id' => 'sometimes|string|uuid',
                'is_primary' => 'sometimes|boolean',
                'is_favorite' => 'sometimes|boolean',
                'search' => 'sometimes|string|max:255',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
                'sort' => 'sometimes|string|in:created_at,phone_number,type,is_primary,sort_order',
                'direction' => 'sometimes|string|in:asc,desc',
            ]);

            $query = PhoneNumber::query();

            // Filter anwenden
            if ($request->has('type')) {
                $query->ofType($request->type);
            }

            if ($request->has('phoneable_type')) {
                $query->where('phoneable_type', $request->phoneable_type);
            }

            if ($request->has('phoneable_id')) {
                $query->where('phoneable_id', $request->phoneable_id);
            }

            if ($request->has('is_primary')) {
                if ($request->boolean('is_primary')) {
                    $query->primary();
                } else {
                    $query->where('is_primary', false);
                }
            }

            if ($request->has('is_favorite')) {
                if ($request->boolean('is_favorite')) {
                    $query->favorite();
                } else {
                    $query->where('is_favorite', false);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('phone_number', 'like', "%{$search}%")
                      ->orWhere('label', 'like', "%{$search}%");
                });
            }

            // Sortierung anwenden
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('direction', 'desc');

            if ($sortField === 'is_primary') {
                // Bei is_primary erst true, dann false
                $query->orderBy('is_primary', $sortDirection === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy($sortField, $sortDirection);
            }

            // Fallback-Sortierung für konsistente Ergebnisse
            if ($sortField !== 'created_at') {
                $query->orderBy('created_at', 'desc');
            }

            $perPage = $request->get('per_page', 15);
            $phoneNumbers = $query->paginate($perPage);

            Log::info('Phone numbers retrieved successfully', [
                'total' => $phoneNumbers->total(),
                'per_page' => $perPage,
                'current_page' => $phoneNumbers->currentPage(),
                'filters' => $request->only(['type', 'phoneable_type', 'phoneable_id', 'is_primary', 'is_favorite', 'search'])
            ]);

            return PhoneNumberResource::collection($phoneNumbers);

        } catch (Exception $e) {
            Log::error('Error retrieving phone numbers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Telefonnummer erstellen
     * 
     * Erstellt eine neue Telefonnummer für den angegebenen Besitzer.
     *
     * @bodyParam phoneable_id string required ID des Besitzers. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam phoneable_type string required Typ des Besitzers. Example: App\Models\User
     * @bodyParam phone_number string required Telefonnummer. Example: +49 30 123456789
     * @bodyParam type string required Typ der Telefonnummer. Example: business
     * @bodyParam label string optional Zusätzliche Beschreibung. Example: Büro Berlin
     * @bodyParam is_primary boolean optional Hauptnummer (Standard: false). Example: true
     * @bodyParam is_favorite boolean optional Favorit (Standard: false). Example: false
     * @bodyParam sort_order integer optional Sortierreihenfolge (Standard: 0). Example: 1
     *
     * @response 201 scenario="Success" {
     *   "data": {
     *     "id": "123e4567-e89b-12d3-a456-426614174000",
     *     "phoneable_id": "456e7890-e89b-12d3-a456-426614174001",
     *     "phoneable_type": "App\\Models\\User",
     *     "phone_number": "+49 30 123456789",
     *     "formatted_number": "+49 30 123 456 789",
     *     "type": "business",
     *     "type_label": "Geschäftlich",
     *     "label": "Büro Berlin",
     *     "display_label": "Geschäftlich (Büro Berlin) [Hauptnummer]",
     *     "is_primary": true,
     *     "is_favorite": false,
     *     "sort_order": 1,
     *     "created_at": "2025-01-08T10:30:00Z",
     *     "updated_at": "2025-01-08T10:30:00Z"
     *   }
     * }
     *
     * @response 422 scenario="Validation Error" {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "phone_number": ["The phone number field is required."],
     *     "type": ["The selected type is invalid."]
     *   }
     * }
     */
    public function store(StorePhoneNumberRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // Wenn diese Nummer als Hauptnummer markiert ist, andere deaktivieren
            if ($request->boolean('is_primary')) {
                PhoneNumber::where('phoneable_id', $validatedData['phoneable_id'])
                    ->where('phoneable_type', $validatedData['phoneable_type'])
                    ->update(['is_primary' => false]);
            }

            $phoneNumber = PhoneNumber::create($validatedData);

            Log::info('Phone number created successfully', [
                'phone_number_id' => $phoneNumber->id,
                'phoneable_type' => $phoneNumber->phoneable_type,
                'phoneable_id' => $phoneNumber->phoneable_id,
                'type' => $phoneNumber->type
            ]);

            return response()->json([
                'data' => new PhoneNumberResource($phoneNumber)
            ], 201);

        } catch (Exception $e) {
            Log::error('Error creating phone number', [
                'data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Erstellen der Telefonnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * Telefonnummer anzeigen
     * 
     * Ruft eine spezifische Telefonnummer anhand ihrer ID ab.
     *
     * @urlParam id string required Die ID der Telefonnummer. Example: 123e4567-e89b-12d3-a456-426614174000
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": "123e4567-e89b-12d3-a456-426614174000",
     *     "phoneable_id": "456e7890-e89b-12d3-a456-426614174001",
     *     "phoneable_type": "App\\Models\\User",
     *     "phone_number": "+49 30 123456789",
     *     "formatted_number": "+49 30 123 456 789",
     *     "type": "business",
     *     "type_label": "Geschäftlich",
     *     "label": "Büro Berlin",
     *     "display_label": "Geschäftlich (Büro Berlin) [Hauptnummer]",
     *     "is_primary": true,
     *     "is_favorite": false,
     *     "sort_order": 1,
     *     "created_at": "2025-01-08T10:30:00Z",
     *     "updated_at": "2025-01-08T10:30:00Z"
     *   }
     * }
     *
     * @response 404 scenario="Not Found" {
     *   "message": "Telefonnummer nicht gefunden."
     * }
     */
    public function show(string $id): JsonResponse
    {
        try {
            $phoneNumber = PhoneNumber::findOrFail($id);

            Log::info('Phone number retrieved successfully', [
                'phone_number_id' => $phoneNumber->id
            ]);

            return response()->json([
                'data' => new PhoneNumberResource($phoneNumber)
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('Phone number not found', ['id' => $id]);
            
            return response()->json([
                'message' => 'Telefonnummer nicht gefunden.'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error retrieving phone number', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Abrufen der Telefonnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * Telefonnummer aktualisieren
     * 
     * Aktualisiert eine bestehende Telefonnummer.
     *
     * @urlParam id string required Die ID der Telefonnummer. Example: 123e4567-e89b-12d3-a456-426614174000
     * @bodyParam phone_number string optional Telefonnummer. Example: +49 30 987654321
     * @bodyParam type string optional Typ der Telefonnummer. Example: mobile
     * @bodyParam label string optional Zusätzliche Beschreibung. Example: Büro München
     * @bodyParam is_primary boolean optional Hauptnummer. Example: false
     * @bodyParam is_favorite boolean optional Favorit. Example: true
     * @bodyParam sort_order integer optional Sortierreihenfolge. Example: 2
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": "123e4567-e89b-12d3-a456-426614174000",
     *     "phoneable_id": "456e7890-e89b-12d3-a456-426614174001",
     *     "phoneable_type": "App\\Models\\User",
     *     "phone_number": "+49 30 987654321",
     *     "formatted_number": "+49 30 987 654 321",
     *     "type": "mobile",
     *     "type_label": "Mobil",
     *     "label": "Büro München",
     *     "display_label": "Mobil (Büro München)",
     *     "is_primary": false,
     *     "is_favorite": true,
     *     "sort_order": 2,
     *     "created_at": "2025-01-08T10:30:00Z",
     *     "updated_at": "2025-01-08T12:15:00Z"
     *   }
     * }
     *
     * @response 404 scenario="Not Found" {
     *   "message": "Telefonnummer nicht gefunden."
     * }
     *
     * @response 422 scenario="Validation Error" {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "type": ["The selected type is invalid."]
     *   }
     * }
     */
    public function update(UpdatePhoneNumberRequest $request, string $id): JsonResponse
    {
        try {
            $phoneNumber = PhoneNumber::findOrFail($id);
            $validatedData = $request->validated();

            // Wenn diese Nummer als neue Hauptnummer markiert wird, andere deaktivieren
            if ($request->has('is_primary') && $request->boolean('is_primary') && !$phoneNumber->is_primary) {
                PhoneNumber::where('phoneable_id', $phoneNumber->phoneable_id)
                    ->where('phoneable_type', $phoneNumber->phoneable_type)
                    ->where('id', '!=', $phoneNumber->id)
                    ->update(['is_primary' => false]);
            }

            $phoneNumber->update($validatedData);

            Log::info('Phone number updated successfully', [
                'phone_number_id' => $phoneNumber->id,
                'updated_fields' => array_keys($validatedData)
            ]);

            return response()->json([
                'data' => new PhoneNumberResource($phoneNumber->fresh())
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('Phone number not found for update', ['id' => $id]);
            
            return response()->json([
                'message' => 'Telefonnummer nicht gefunden.'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error updating phone number', [
                'id' => $id,
                'data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Aktualisieren der Telefonnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * Telefonnummer löschen
     * 
     * Löscht eine Telefonnummer permanent.
     *
     * @urlParam id string required Die ID der Telefonnummer. Example: 123e4567-e89b-12d3-a456-426614174000
     *
     * @response 200 scenario="Success" {
     *   "message": "Telefonnummer erfolgreich gelöscht."
     * }
     *
     * @response 404 scenario="Not Found" {
     *   "message": "Telefonnummer nicht gefunden."
     * }
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $phoneNumber = PhoneNumber::findOrFail($id);
            
            // Informationen für Logging vor dem Löschen speichern
            $phoneNumberData = [
                'id' => $phoneNumber->id,
                'phoneable_type' => $phoneNumber->phoneable_type,
                'phoneable_id' => $phoneNumber->phoneable_id,
                'phone_number' => $phoneNumber->phone_number,
                'type' => $phoneNumber->type,
                'was_primary' => $phoneNumber->is_primary
            ];

            $phoneNumber->delete();

            Log::info('Phone number deleted successfully', $phoneNumberData);

            return response()->json([
                'message' => 'Telefonnummer erfolgreich gelöscht.'
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('Phone number not found for deletion', ['id' => $id]);
            
            return response()->json([
                'message' => 'Telefonnummer nicht gefunden.'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error deleting phone number', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Löschen der Telefonnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * Als Hauptnummer setzen
     * 
     * Setzt eine Telefonnummer als Hauptnummer und deaktiviert alle anderen Hauptnummern des gleichen Besitzers.
     *
     * @urlParam id string required Die ID der Telefonnummer. Example: 123e4567-e89b-12d3-a456-426614174000
     *
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": "123e4567-e89b-12d3-a456-426614174000",
     *     "phoneable_id": "456e7890-e89b-12d3-a456-426614174001",
     *     "phoneable_type": "App\\Models\\User",
     *     "phone_number": "+49 30 123456789",
     *     "formatted_number": "+49 30 123 456 789",
     *     "type": "business",
     *     "type_label": "Geschäftlich",
     *     "label": "Büro Berlin",
     *     "display_label": "Geschäftlich (Büro Berlin) [Hauptnummer]",
     *     "is_primary": true,
     *     "is_favorite": false,
     *     "sort_order": 1,
     *     "created_at": "2025-01-08T10:30:00Z",
     *     "updated_at": "2025-01-08T12:45:00Z"
     *   }
     * }
     *
     * @response 404 scenario="Not Found" {
     *   "message": "Telefonnummer nicht gefunden."
     * }
     */
    public function makePrimary(string $id): JsonResponse
    {
        try {
            $phoneNumber = PhoneNumber::findOrFail($id);
            
            $success = $phoneNumber->makePrimary();
            
            if ($success) {
                Log::info('Phone number set as primary', [
                    'phone_number_id' => $phoneNumber->id,
                    'phoneable_type' => $phoneNumber->phoneable_type,
                    'phoneable_id' => $phoneNumber->phoneable_id
                ]);

                return response()->json([
                    'data' => new PhoneNumberResource($phoneNumber->fresh())
                ]);
            } else {
                return response()->json([
                    'message' => 'Fehler beim Setzen der Hauptnummer.'
                ], 500);
            }

        } catch (ModelNotFoundException $e) {
            Log::warning('Phone number not found for makePrimary', ['id' => $id]);
            
            return response()->json([
                'message' => 'Telefonnummer nicht gefunden.'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error making phone number primary', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Setzen der Hauptnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * Telefonnummern eines Besitzers
     * 
     * Ruft alle Telefonnummern eines bestimmten Besitzers ab (z.B. alle Nummern eines Users).
     *
     * @urlParam phoneable_type string required Typ des Besitzers. Example: App\Models\User
     * @urlParam phoneable_id string required ID des Besitzers. Example: 123e4567-e89b-12d3-a456-426614174000
     * @queryParam type string Filter nach Telefonnummer-Typ. Example: mobile
     * @queryParam is_primary boolean Filter nach Hauptnummern. Example: 1
     * @queryParam is_favorite boolean Filter nach Favoriten. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": "123e4567-e89b-12d3-a456-426614174000",
     *       "phoneable_id": "456e7890-e89b-12d3-a456-426614174001",
     *       "phoneable_type": "App\\Models\\User",
     *       "phone_number": "+49 30 123456789",
     *       "formatted_number": "+49 30 123 456 789",
     *       "type": "business",
     *       "type_label": "Geschäftlich",
     *       "label": "Büro Berlin",
     *       "display_label": "Geschäftlich (Büro Berlin) [Hauptnummer]",
     *       "is_primary": true,
     *       "is_favorite": false,
     *       "sort_order": 1,
     *       "created_at": "2025-01-08T10:30:00Z",
     *       "updated_at": "2025-01-08T10:30:00Z"
     *     }
     *   ]
     * }
     */
    public function getByOwner(Request $request, string $phoneableType, string $phoneableId): AnonymousResourceCollection
    {
        try {
            // Validierung der Query-Parameter
            $request->validate([
                'type' => 'sometimes|string|in:business,private,mobile',
                'is_primary' => 'sometimes|boolean',
                'is_favorite' => 'sometimes|boolean',
            ]);

            $query = PhoneNumber::where('phoneable_type', $phoneableType)
                                ->where('phoneable_id', $phoneableId);

            // Filter anwenden
            if ($request->has('type')) {
                $query->ofType($request->type);
            }

            if ($request->has('is_primary')) {
                if ($request->boolean('is_primary')) {
                    $query->primary();
                } else {
                    $query->where('is_primary', false);
                }
            }

            if ($request->has('is_favorite')) {
                if ($request->boolean('is_favorite')) {
                    $query->favorite();
                } else {
                    $query->where('is_favorite', false);
                }
            }

            // Standardsortierung verwenden
            $phoneNumbers = $query->ordered()->get();

            Log::info('Phone numbers by owner retrieved successfully', [
                'phoneable_type' => $phoneableType,
                'phoneable_id' => $phoneableId,
                'count' => $phoneNumbers->count()
            ]);

            return PhoneNumberResource::collection($phoneNumbers);

        } catch (Exception $e) {
            Log::error('Error retrieving phone numbers by owner', [
                'phoneable_type' => $phoneableType,
                'phoneable_id' => $phoneableId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    // ================================
    // USER-SPEZIFISCHE API-METHODEN
    // ================================

    /**
     * Telefonnummern eines Users abrufen
     * GET /api/app/users/{userId}/phone-numbers
     */
    public function getUserPhoneNumbers(Request $request, string $userId): AnonymousResourceCollection
    {
        try {
            $request->validate([
                'type' => 'sometimes|string|in:business,private,mobile',
                'is_primary' => 'sometimes|boolean',
                'is_favorite' => 'sometimes|boolean',
            ]);

            $query = PhoneNumber::where('phoneable_type', 'App\\Models\\User')
                                ->where('phoneable_id', $userId);

            // Filter anwenden
            if ($request->has('type')) {
                $query->ofType($request->type);
            }

            if ($request->has('is_primary')) {
                $query->where('is_primary', $request->boolean('is_primary'));
            }

            if ($request->has('is_favorite')) {
                $query->where('is_favorite', $request->boolean('is_favorite'));
            }

            $phoneNumbers = $query->ordered()->get();

            Log::info('User phone numbers retrieved successfully', [
                'user_id' => $userId,
                'count' => $phoneNumbers->count()
            ]);

            return PhoneNumberResource::collection($phoneNumbers);

        } catch (Exception $e) {
            Log::error('Error retrieving user phone numbers', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Telefonnummer für User erstellen
     * POST /api/app/users/{userId}/phone-numbers
     */
    public function storeForUser(StorePhoneNumberRequest $request, string $userId): JsonResponse
    {
        try {
            $validatedData = $request->validated();
            
            // User-ID und Typ automatisch setzen
            $validatedData['phoneable_id'] = $userId;
            $validatedData['phoneable_type'] = 'App\\Models\\User';

            // Wenn Hauptnummer, andere deaktivieren
            if ($request->boolean('is_primary')) {
                PhoneNumber::where('phoneable_id', $userId)
                    ->where('phoneable_type', 'App\\Models\\User')
                    ->update(['is_primary' => false]);
            }

            $phoneNumber = PhoneNumber::create($validatedData);

            Log::info('User phone number created successfully', [
                'phone_number_id' => $phoneNumber->id,
                'user_id' => $userId,
                'type' => $phoneNumber->type
            ]);

            return response()->json([
                'data' => new PhoneNumberResource($phoneNumber)
            ], 201);

        } catch (Exception $e) {
            Log::error('Error creating user phone number', [
                'user_id' => $userId,
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Erstellen der Telefonnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * User-Telefonnummer anzeigen
     * GET /api/app/users/{userId}/phone-numbers/{id}
     */
    public function getUserPhoneNumber(string $userId, string $id): JsonResponse
    {
        try {
            $phoneNumber = PhoneNumber::where('id', $id)
                ->where('phoneable_id', $userId)
                ->where('phoneable_type', 'App\\Models\\User')
                ->firstOrFail();

            Log::info('User phone number retrieved successfully', [
                'phone_number_id' => $phoneNumber->id,
                'user_id' => $userId
            ]);

            return response()->json([
                'data' => new PhoneNumberResource($phoneNumber)
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('User phone number not found', [
                'id' => $id,
                'user_id' => $userId
            ]);
            
            return response()->json([
                'message' => 'Telefonnummer nicht gefunden.'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error retrieving user phone number', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Abrufen der Telefonnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * User-Telefonnummer aktualisieren
     * PUT /api/app/users/{userId}/phone-numbers/{id}
     */
    public function updateUserPhoneNumber(UpdatePhoneNumberRequest $request, string $userId, string $id): JsonResponse
    {
        try {
            $phoneNumber = PhoneNumber::where('id', $id)
                ->where('phoneable_id', $userId)
                ->where('phoneable_type', 'App\\Models\\User')
                ->firstOrFail();
                
            $validatedData = $request->validated();

            // Wenn als neue Hauptnummer markiert, andere deaktivieren
            if ($request->has('is_primary') && $request->boolean('is_primary') && !$phoneNumber->is_primary) {
                PhoneNumber::where('phoneable_id', $userId)
                    ->where('phoneable_type', 'App\\Models\\User')
                    ->where('id', '!=', $phoneNumber->id)
                    ->update(['is_primary' => false]);
            }

            $phoneNumber->update($validatedData);

            Log::info('User phone number updated successfully', [
                'phone_number_id' => $phoneNumber->id,
                'user_id' => $userId,
                'updated_fields' => array_keys($validatedData)
            ]);

            return response()->json([
                'data' => new PhoneNumberResource($phoneNumber->fresh())
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('User phone number not found for update', [
                'id' => $id,
                'user_id' => $userId
            ]);
            
            return response()->json([
                'message' => 'Telefonnummer nicht gefunden.'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error updating user phone number', [
                'id' => $id,
                'user_id' => $userId,
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Aktualisieren der Telefonnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * User-Telefonnummer löschen
     * DELETE /api/app/users/{userId}/phone-numbers/{id}
     */
    public function destroyUserPhoneNumber(string $userId, string $id): JsonResponse
    {
        try {
            $phoneNumber = PhoneNumber::where('id', $id)
                ->where('phoneable_id', $userId)
                ->where('phoneable_type', 'App\\Models\\User')
                ->firstOrFail();
            
            $phoneNumberData = [
                'id' => $phoneNumber->id,
                'user_id' => $userId,
                'phone_number' => $phoneNumber->phone_number,
                'type' => $phoneNumber->type,
                'was_primary' => $phoneNumber->is_primary
            ];

            $phoneNumber->delete();

            Log::info('User phone number deleted successfully', $phoneNumberData);

            return response()->json([
                'message' => 'Telefonnummer erfolgreich gelöscht.'
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning('User phone number not found for deletion', [
                'id' => $id,
                'user_id' => $userId
            ]);
            
            return response()->json([
                'message' => 'Telefonnummer nicht gefunden.'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error deleting user phone number', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Löschen der Telefonnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    /**
     * User-Telefonnummer als Hauptnummer setzen
     * PATCH /api/app/users/{userId}/phone-numbers/{id}/make-primary
     */
    public function makeUserPhoneNumberPrimary(string $userId, string $id): JsonResponse
    {
        try {
            $phoneNumber = PhoneNumber::where('id', $id)
                ->where('phoneable_id', $userId)
                ->where('phoneable_type', 'App\\Models\\User')
                ->firstOrFail();
            
            $success = $phoneNumber->makePrimary();
            
            if ($success) {
                Log::info('User phone number set as primary', [
                    'phone_number_id' => $phoneNumber->id,
                    'user_id' => $userId
                ]);

                return response()->json([
                    'data' => new PhoneNumberResource($phoneNumber->fresh())
                ]);
            } else {
                return response()->json([
                    'message' => 'Fehler beim Setzen der Hauptnummer.'
                ], 500);
            }

        } catch (ModelNotFoundException $e) {
            Log::warning('User phone number not found for makePrimary', [
                'id' => $id,
                'user_id' => $userId
            ]);
            
            return response()->json([
                'message' => 'Telefonnummer nicht gefunden.'
            ], 404);

        } catch (Exception $e) {
            Log::error('Error making user phone number primary', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Setzen der Hauptnummer.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }
}
