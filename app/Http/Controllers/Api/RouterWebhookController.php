<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\RouterWebhookLog;
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
        $startTime = microtime(true);
        
        try {
            $rawData = $request->all();
            
            // Check if data is in VoltMaster format and transform it
            $transformedData = $this->transformVoltMasterData($rawData);
            
            // Validate incoming webhook data
            $validator = Validator::make($transformedData, [
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
                    'raw_data' => $rawData,
                    'transformed_data' => $transformedData,
                    'ip' => $request->ip()
                ]);

                $response = [
                    'error' => 'Invalid webhook data',
                    'details' => $validator->errors()
                ];

                // Log failed validation attempt
                $this->logWebhookAttempt(
                    null,
                    $token,
                    $rawData,
                    $transformedData,
                    $validator->errors()->toArray(),
                    $response,
                    $startTime,
                    $request->ip(),
                    $request->userAgent() ?? 'Unknown',
                    'failed'
                );

                return response()->json($response, 400);
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

                $response = [
                    'error' => 'Invalid webhook token',
                    'message' => 'Router not found for this token'
                ];

                // Log failed token validation
                $this->logWebhookAttempt(
                    null,
                    $token,
                    $request->all(),
                    $data,
                    ['token' => 'Invalid webhook token'],
                    $response,
                    $startTime,
                    $request->ip(),
                    $request->userAgent() ?? 'Unknown',
                    'failed'
                );

                return response()->json($response, 404);
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

            $response = [
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
            ];

            // Log successful webhook processing
            $this->logWebhookAttempt(
                $router->id,
                $token,
                $request->all(),
                $data,
                null,
                $response,
                $startTime,
                $request->ip(),
                $request->userAgent() ?? 'Unknown',
                'success'
            );

            return response()->json($response);

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

    /**
     * Log webhook attempt to database for historical tracking
     *
     * @param int|null $routerId
     * @param string $webhookToken
     * @param array $rawData
     * @param array|null $processedData
     * @param array|null $validationErrors
     * @param array $responseData
     * @param float $startTime
     * @param string $sourceIp
     * @param string $userAgent
     * @param string $status
     * @return void
     */
    private function logWebhookAttempt(
        ?int $routerId,
        string $webhookToken,
        array $rawData,
        ?array $processedData,
        ?array $validationErrors,
        array $responseData,
        float $startTime,
        string $sourceIp,
        string $userAgent,
        string $status
    ): void {
        $processingTime = round((microtime(true) - $startTime) * 1000, 2);

        // Extract key data from processed data for quick queries
        $logData = [
            'router_id' => $routerId,
            'webhook_token' => $webhookToken,
            'raw_data' => $rawData,
            'validation_errors' => $validationErrors,
            'response_data' => $responseData,
            'processing_time_ms' => $processingTime,
            'client_ip' => $sourceIp,
            'user_agent' => $userAgent,
            'status' => $status === 'success' ? 'success' : ($validationErrors ? 'validation_error' : 'processing_error'),
            'http_response_code' => $status === 'success' ? 200 : ($validationErrors ? 400 : 500),
        ];

        // Extract individual fields from processed data for quick queries
        if ($processedData) {
            $logData['operator'] = $processedData['operator'] ?? null;
            $logData['signal_strength'] = $processedData['signal_strength'] ?? null;
            $logData['network_type'] = $processedData['network_type'] ?? null;
            $logData['connection_time'] = $processedData['connection_time'] ?? null;
            $logData['data_usage_mb'] = $processedData['data_usage_mb'] ?? null;
            $logData['router_ip'] = $processedData['ip_address'] ?? null;
        }

        RouterWebhookLog::create($logData);
    }

    /**
     * Transform VoltMaster webhook data to expected format
     *
     * @param array $rawData
     * @return array
     */
    private function transformVoltMasterData(array $rawData): array
    {
        // Check if this is VoltMaster format
        if (!isset($rawData['VoltMaster']) || !is_array($rawData['VoltMaster'])) {
            // Return raw data if not VoltMaster format
            return $rawData;
        }

        $voltMasterData = $rawData['VoltMaster'];
        
        // Transform to expected format
        $transformedData = [];

        // Extract operator (clean up the name)
        if (isset($voltMasterData['operator'])) {
            $operator = $voltMasterData['operator'];
            // Clean up operator name (remove underscores, country codes)
            $operator = str_replace(['_', 'Deutschland', 'GER'], [' ', 'DE', ''], $operator);
            $operator = trim($operator);
            $transformedData['operator'] = $operator;
        }

        // Extract signal strength (use RSSI)
        if (isset($voltMasterData['rssi'])) {
            $transformedData['signal_strength'] = (int) $voltMasterData['rssi'];
        }

        // Extract network type (use connection type)
        if (isset($voltMasterData['conntype'])) {
            $transformedData['network_type'] = $voltMasterData['conntype'];
        }

        // Extract IP address from ip object
        if (isset($voltMasterData['ip']) && is_array($voltMasterData['ip'])) {
            $ipAddresses = array_keys($voltMasterData['ip']);
            if (!empty($ipAddresses)) {
                $transformedData['ip_address'] = $ipAddresses[0];
            }
        }

        // Add additional optional fields if they exist
        if (isset($voltMasterData['temp'])) {
            $transformedData['temperature'] = $voltMasterData['temp'];
        }

        if (isset($voltMasterData['imei'])) {
            $transformedData['imei'] = $voltMasterData['imei'];
        }

        if (isset($voltMasterData['iccid'])) {
            $transformedData['iccid'] = $voltMasterData['iccid'];
        }

        if (isset($voltMasterData['model'])) {
            $transformedData['modem_model'] = $voltMasterData['model'];
        }

        Log::info('Transformed VoltMaster data', [
            'original' => $rawData,
            'transformed' => $transformedData
        ]);

        return $transformedData;
    }
}
