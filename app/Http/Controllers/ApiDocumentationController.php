<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="VoltMaster API Documentation",
 *     version="1.0.0",
 *     description="API Documentation für das VoltMaster Solar Management System",
 *     @OA\Contact(
 *         email="api@voltmaster.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="VoltMaster API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="app_token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="App Token",
 *     description="App Token für API-Zugriff"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum Token"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentifizierung und Autorisierung"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="Benutzer-Management"
 * )
 * 
 * @OA\Tag(
 *     name="Tasks",
 *     description="Aufgaben-Management"
 * )
 * 
 * @OA\Tag(
 *     name="Customers",
 *     description="Kunden-Management"
 * )
 * 
 * @OA\Tag(
 *     name="Suppliers",
 *     description="Lieferanten-Management"
 * )
 * 
 * @OA\Tag(
 *     name="Solar Plants",
 *     description="Solaranlagen-Management"
 * )
 * 
 * @OA\Tag(
 *     name="Projects",
 *     description="Projekt-Management"
 * )
 */
class ApiDocumentationController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * @OA\Get(
     *     path="/api/users/search",
     *     tags={"Users"},
     *     summary="Benutzer suchen für @mentions",
     *     description="Sucht Benutzer basierend auf Namen für @mention Funktionalität",
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Suchterm für Benutzername",
     *         required=false,
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste der gefundenen Benutzer",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com")
     *             )
     *         )
     *     )
     * )
     */
    public function searchUsers() {}

    /**
     * @OA\Get(
     *     path="/api/users/all",
     *     tags={"Users"},
     *     summary="Alle Benutzer abrufen",
     *     description="Gibt alle Benutzer für @mention Funktionalität zurück",
     *     @OA\Response(
     *         response=200,
     *         description="Liste aller Benutzer",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com")
     *             )
     *         )
     *     )
     * )
     */
    public function getAllUsers() {}

    /**
     * @OA\Get(
     *     path="/api/user",
     *     tags={"Authentication"},
     *     summary="Aktueller authentifizierter Benutzer",
     *     description="Gibt die Informationen des aktuell authentifizierten Benutzers zurück",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Benutzerinformationen",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="email_verified_at", type="string", format="date-time"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Nicht authentifiziert"
     *     )
     * )
     */
    public function getUser() {}

    /**
     * @OA\Get(
     *     path="/api/app/profile",
     *     tags={"Authentication"},
     *     summary="Benutzer-Profil (App Token)",
     *     description="Gibt das Profil des authentifizierten Benutzers zurück (App Token erforderlich)",
     *     security={{"app_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profil-Informationen",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nicht authentifiziert"),
     *     @OA\Response(response=403, description="Keine Berechtigung")
     * )
     */
    public function getProfile() {}

    /**
     * @OA\Get(
     *     path="/api/app/tasks",
     *     tags={"Tasks"},
     *     summary="Aufgaben abrufen",
     *     description="Gibt eine paginierte Liste von Aufgaben zurück",
     *     security={{"app_token": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Seitennummer",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query", 
     *         description="Anzahl Einträge pro Seite",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste der Aufgaben",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="status", type="string", enum={"open", "in_progress", "completed", "cancelled"}),
     *                     @OA\Property(property="priority", type="string", enum={"low", "medium", "high", "urgent"}),
     *                     @OA\Property(property="due_date", type="string", format="date"),
     *                     @OA\Property(property="assigned_to", type="integer"),
     *                     @OA\Property(property="customer_id", type="integer"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nicht authentifiziert"),
     *     @OA\Response(response=403, description="Keine tasks:read Berechtigung")
     * )
     */
    public function getTasks() {}

    /**
     * @OA\Post(
     *     path="/api/app/tasks",
     *     tags={"Tasks"},
     *     summary="Neue Aufgabe erstellen",
     *     description="Erstellt eine neue Aufgabe",
     *     security={{"app_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "status", "priority"},
     *             @OA\Property(property="title", type="string", example="Solar Panel Installation"),
     *             @OA\Property(property="description", type="string", example="Install solar panels on customer roof"),
     *             @OA\Property(property="status", type="string", enum={"open", "in_progress", "completed", "cancelled"}, example="open"),
     *             @OA\Property(property="priority", type="string", enum={"low", "medium", "high", "urgent"}, example="medium"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-12-31"),
     *             @OA\Property(property="assigned_to", type="integer", example=1),
     *             @OA\Property(property="customer_id", type="integer", example=1),
     *             @OA\Property(property="supplier_id", type="integer", example=1),
     *             @OA\Property(property="project_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Aufgabe erfolgreich erstellt",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="priority", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validierungsfehler"),
     *     @OA\Response(response=401, description="Nicht authentifiziert"),
     *     @OA\Response(response=403, description="Keine tasks:create Berechtigung")
     * )
     */
    public function createTask() {}

    /**
     * @OA\Get(
     *     path="/api/app/customers",
     *     tags={"Customers"},
     *     summary="Kunden abrufen",
     *     description="Gibt eine paginierte Liste von Kunden zurück",
     *     security={{"app_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste der Kunden",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="customer_type", type="string", enum={"private", "business"}),
     *                     @OA\Property(property="company_name", type="string"),
     *                     @OA\Property(property="status", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Keine customers:read Berechtigung")
     * )
     */
    public function getCustomers() {}

    /**
     * @OA\Get(
     *     path="/api/app/solar-plants",
     *     tags={"Solar Plants"},
     *     summary="Solaranlagen abrufen",
     *     description="Gibt eine Liste von Solaranlagen zurück",
     *     security={{"app_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste der Solaranlagen",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="plant_number", type="string"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="location", type="string"),
     *                     @OA\Property(property="total_capacity_kw", type="number"),
     *                     @OA\Property(property="status", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Keine solar-plants:read Berechtigung")
     * )
     */
    public function getSolarPlants() {}
}
