<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Search users for mention autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        $users = User::select(['id', 'name', 'email'])
            ->where('is_active', true)
            ->when($query, function ($queryBuilder) use ($query) {
                $queryBuilder->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('email', 'LIKE', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($users);
    }

    /**
     * Get all active users for mention functionality
     */
    public function all(): JsonResponse
    {
        $users = User::select(['id', 'name', 'email'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }
}