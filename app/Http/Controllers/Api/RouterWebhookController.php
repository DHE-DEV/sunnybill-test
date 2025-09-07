<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RouterWebhookController extends Controller
{
    /**
     * Handle webhook data from Teltonika routers with token-based authentication
     *
     * @param Request $request
     * @param string $token
     * @return JsonResponse
     */
    public function routerWebhook(Request $request, string $token): JsonResponse
    {
        try {
            // Validate incoming webhook data
            $validator = Validator::make($request->all(), [
                'operator' => 'required|string|max:255',
                'signal_strength' => 'required|integer',
                'network_type' => 'required|string|max:50',
                'connection_time' => 'sometimes|integer', // Optional: seconds connected
                'data_usage_mb' => 'sometimes|numeric', // Optional: data usage
                'ip_address' => 'sometimes|ip', // Optional: router's IP
            ]);

            if ($validator->fails()) {
                Log::warning('Router webhook validation failed', [
                    'token' => $token,
                    'errors' => $validator->errors(),
                    'data' => $request->all(),
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'error' => 'Invalid webhook data',
                    'details' => $validator->errors()
                ], 400);
            }

            $data = $validator->validated();
            $clientIp = $request->ip();

            // Find router by webhook token
            $router = Router::where('webhook_token', $token)->first();

            if (!$router) {
                Log::warning('Router webhook with invalid token', [
                    'token' => $token,
                    'ip' => $clientIp,
                    'data' => $data
                ]);

                return response()->json([
                    'error' => 'Invalid webhook token',
                    'message' => 'Router not found for this token'
                ], 404);
            }

            // Update router with new webhook data
            $updateData = [
                'operator' => $data['operator'],
                'signal_strength' => $data['signal_strength'], 
                'network_type' => $data['network_type'],
                'last_seen_at' => now(),
                'total_webhooks' => $router->total_webhooks + 1,
                'last_data' => $data
            ];

            // Add optional fields if provided
            if (isset($data['connection_time'])) {
                $updateData['connection_time_seconds'] = $data['connection_time'];
            }
            
            if (isset($data['data_usage_mb'])) {
                $updateData['data_usage_mb'] = $data['data_usage_mb'];
            }

            if (isset($data['ip_address'])) {
                $updateData['ip_address'] = $data['ip_address'];
            }

            $router->update($updateData);
            
            // Update connection status
            $router->updateConnectionStatus();

            Log::info('Router updated from webhook', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'token' => $token,
                'operator' => $data['operator'],
                'signal_strength' => $data['signal_strength'],
                'network_type' => $data['network_type'],
                'status' => $router->connection_status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Router data updated successfully',
                'router' => [
                    'id' => $router->id,
                    'name' => $router->name,
                    'status' => $router->connection_status,
                    'operator' => $router->operator,
                    'signal_strength' => $router->signal_strength,
                    'signal_bars' => $router->calculateSignalBars(),
                    'network_type' => $router->network_type,
                    'last_seen' => $router->last_seen_at->toISOString(),
                    'total_webhooks' => $router->total_webhooks
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Router webhook processing failed', [
                'token' => $token,
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Failed to process router webhook data'
            ], 500);
        }
    }

    /**
     * Handle webhook data from Teltonika routers
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Validate incoming webhook data
            $validator = Validator::make($request->all(), [
                'operator' => 'required|string|max:255',
                'signal_strength' => 'required|integer',
                'network_type' => 'required|string|max:50',
                'router_token' => 'sometimes|string', // Optional token for router identification
            ]);

            if ($validator->fails()) {
                Log::warning('Router webhook validation failed', [
                    'errors' => $validator->errors(),
                    'data' => $request->all(),
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'error' => 'Invalid webhook data',
                    'details' => $validator->errors()
                ], 400);
            }

            $data = $validator->validated();
            $clientIp = $request->ip();

            // Try to find router by webhook token (if provided) or IP address
            $router = null;
            
            if (isset($data['router_token'])) {
                $router = Router::where('webhook_token', $data['router_token'])->first();
            }
            
            // If no router found by token, try to find by IP address
            if (!$router) {
                $router = Router::where('ip_address', $clientIp)->first();
            }

            // If still no router found, create a new one or use a default behavior
            if (!$router) {
                // For now, we'll create a new router entry automatically
                $router = Router::create([
                    'name' => 'Auto-detected Router ' . substr($clientIp, -6),
                    'model' => 'Unknown',
                    'ip_address' => $clientIp,
                    'is_active' => true,
                    'operator' => $data['operator'],
                    'signal_strength' => $data['signal_strength'],
                    'network_type' => $data['network_type'],
                    'last_seen_at' => now(),
                    'total_webhooks' => 1,
                    'last_data' => $data
                ]);

                Log::info('Auto-created new router from webhook', [
                    'router_id' => $router->id,
                    'ip' => $clientIp,
                    'data' => $data
                ]);
            } else {
                // Update existing router with new webhook data
                $router->updateFromWebhook($data);
                
                Log::info('Updated router from webhook', [
                    'router_id' => $router->id,
                    'router_name' => $router->name,
                    'data' => $data
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook data received and processed',
                'router_id' => $router->id,
                'router_name' => $router->name,
                'status' => $router->connection_status,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Router webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Failed to process webhook data'
            ], 500);
        }
    }

    /**
     * Get current status of all routers or specific router
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $routerId = $request->query('router_id');
            $routerToken = $request->query('router_token');

            if ($routerId) {
                // Get specific router by ID
                $router = Router::find($routerId);
                if (!$router) {
                    return response()->json([
                        'error' => 'Router not found'
                    ], 404);
                }

                return response()->json([
                    'router' => $this->formatRouterStatus($router),
                    'timestamp' => now()->toISOString()
                ]);
            } elseif ($routerToken) {
                // Get specific router by token
                $router = Router::where('webhook_token', $routerToken)->first();
                if (!$router) {
                    return response()->json([
                        'error' => 'Router not found'
                    ], 404);
                }

                return response()->json([
                    'router' => $this->formatRouterStatus($router),
                    'timestamp' => now()->toISOString()
                ]);
            } else {
                // Get all routers
                $routers = Router::where('is_active', true)
                    ->orderBy('last_seen_at', 'desc')
                    ->get();

                return response()->json([
                    'routers' => $routers->map(function ($router) {
                        return $this->formatRouterStatus($router);
                    }),
                    'total_routers' => $routers->count(),
                    'timestamp' => now()->toISOString()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Router status retrieval failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => 'Failed to retrieve router status'
            ], 500);
        }
    }

    /**
     * Format router data for API response
     *
     * @param Router $router
     * @return array
     */
    private function formatRouterStatus(Router $router): array
    {
        // Update connection status before returning
        $router->updateConnectionStatus();

        return [
            'id' => $router->id,
            'name' => $router->name,
            'model' => $router->model,
            'serial_number' => $router->serial_number,
            'location' => $router->location,
            'coordinates' => $router->getFormattedCoordinatesAttribute(),
            
            // Network Information
            'operator' => $router->operator,
            'signal_strength' => $router->signal_strength,
            'signal_strength_bars' => $router->calculateSignalBars(),
            'network_type' => $router->network_type,
            
            // Status Information
            'connection_status' => $router->connection_status,
            'is_active' => $router->is_active,
            'last_seen_at' => $router->last_seen_at?->toISOString(),
            'last_seen_formatted' => $router->getLastSeenFormattedAttribute(),
            'minutes_since_last_seen' => $router->last_seen_at ? 
                now()->diffInMinutes($router->last_seen_at) : null,
            
            // Additional Data
            'ip_address' => $router->ip_address,
            'webhook_url' => $router->getWebhookUrlAttribute(),
            'total_webhooks' => $router->total_webhooks,
            'last_data' => $router->last_data,
            'notes' => $router->notes,
            
            // Timestamps
            'created_at' => $router->created_at?->toISOString(),
            'updated_at' => $router->updated_at?->toISOString(),
        ];
    }

    /**
     * Get test curl command for a specific router
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testCurl(Request $request): JsonResponse
    {
        $routerId = $request->query('router_id');
        
        if (!$routerId) {
            return response()->json([
                'error' => 'router_id parameter required'
            ], 400);
        }

        $router = Router::find($routerId);
        if (!$router) {
            return response()->json([
                'error' => 'Router not found'
            ], 404);
        }

        return response()->json([
            'router_id' => $router->id,
            'router_name' => $router->name,
            'test_curl_command' => $router->getTestCurlCommandAttribute(),
            'webhook_url' => $router->getWebhookUrlAttribute()
        ]);
    }
}
