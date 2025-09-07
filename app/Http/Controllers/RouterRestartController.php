<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouterRestartController extends Controller
{
    /**
     * Router neu starten
     *
     * @param Request $request
     * @param int $routerId
     * @return JsonResponse
     */
    public function restart(Request $request, int $routerId): JsonResponse
    {
        try {
            $router = Router::findOrFail($routerId);
            
            // Prüfen ob kürzlich schon ein Neustart durchgeführt wurde
            if ($router->hasRecentRestart()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Router wurde kürzlich bereits neu gestartet. Bitte warten Sie 5 Minuten.',
                    'last_restart' => $router->last_restart_formatted
                ], 429);
            }
            
            // Prüfen ob Router IP-Adresse vorhanden ist
            if (!$router->ip_address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Router hat keine IP-Adresse konfiguriert. Neustart nicht möglich.'
                ], 400);
            }
            
            // Router neu starten
            $success = $router->restart();
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Router wird neu gestartet. Dies kann einige Minuten dauern.',
                    'router' => [
                        'id' => $router->id,
                        'name' => $router->name,
                        'ip_address' => $router->ip_address,
                        'last_restart_at' => $router->last_restart_at->toISOString(),
                        'last_restart_formatted' => $router->last_restart_formatted
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Neustart fehlgeschlagen. Router ist möglicherweise nicht erreichbar.'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Neustarten des Routers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Router-Neustart-Status abfragen
     *
     * @param Request $request
     * @param int $routerId
     * @return JsonResponse
     */
    public function status(Request $request, int $routerId): JsonResponse
    {
        try {
            $router = Router::findOrFail($routerId);
            
            return response()->json([
                'success' => true,
                'router' => [
                    'id' => $router->id,
                    'name' => $router->name,
                    'connection_status' => $router->connection_status,
                    'last_seen_at' => $router->last_seen_at?->toISOString(),
                    'last_seen_formatted' => $router->last_seen_formatted,
                    'last_restart_at' => $router->last_restart_at?->toISOString(),
                    'last_restart_formatted' => $router->last_restart_formatted,
                    'has_recent_restart' => $router->hasRecentRestart(),
                    'can_restart' => !$router->hasRecentRestart() && $router->ip_address
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen des Router-Status: ' . $e->getMessage()
            ], 500);
        }
    }
}