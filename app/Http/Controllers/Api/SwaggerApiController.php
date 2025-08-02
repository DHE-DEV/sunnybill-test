<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class SwaggerApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/users/search",
     *     tags={"Users"},
     *     summary="Search users for @mentions",
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search term for username",
     *         required=false,
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of found users",
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
     *     path="/users/all",
     *     tags={"Users"},
     *     summary="Get all users",
     *     @OA\Response(
     *         response=200,
     *         description="List of all users",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function getAllUsers() {}

    /**
     * @OA\Get(
     *     path="/user",
     *     tags={"Authentication"},
     *     summary="Get current authenticated user",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User information",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function getUser() {}

    /**
     * @OA\Get(
     *     path="/app/profile",
     *     tags={"App Token API"},
     *     summary="Get user profile (App Token)",
     *     security={{"app_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile information",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function getProfile() {}

    /**
     * @OA\Get(
     *     path="/app/tasks",
     *     tags={"App Token API"},
     *     summary="Get tasks",
     *     security={{"app_token": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of tasks",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="priority", type="string"),
     *                     @OA\Property(property="due_date", type="string", format="date")
     *                 )
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Missing tasks:read permission")
     * )
     */
    public function getTasks() {}

    /**
     * @OA\Get(
     *     path="/app/customers",
     *     tags={"App Token API"},
     *     summary="Get customers",
     *     security={{"app_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of customers",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="customer_type", type="string"),
     *                     @OA\Property(property="company_name", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Missing customers:read permission")
     * )
     */
    public function getCustomers() {}

    /**
     * @OA\Get(
     *     path="/app/solar-plants",
     *     tags={"App Token API"},
     *     summary="Get solar plants",
     *     security={{"app_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of solar plants",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="plant_number", type="string"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="location", type="string"),
     *                     @OA\Property(property="total_capacity_kw", type="number")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Missing solar-plants:read permission")
     * )
     */
    public function getSolarPlants() {}

    /**
     * @OA\Get(
     *     path="/app/projects",
     *     tags={"App Token API"},
     *     summary="Get projects",
     *     security={{"app_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of projects",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="customer_id", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Missing projects:read permission")
     * )
     */
    public function getProjects() {}
}