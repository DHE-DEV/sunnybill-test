<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    /**
     * Get API health status
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'checks' => []
        ];

        // Database Check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['checks']['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        // Cache Check
        try {
            $testKey = 'health_check_' . now()->timestamp;
            Cache::put($testKey, 'test', 1);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === 'test') {
                $health['checks']['cache'] = [
                    'status' => 'healthy',
                    'message' => 'Cache is working properly'
                ];
            } else {
                throw new \Exception('Cache test failed');
            }
        } catch (\Exception $e) {
            $health['status'] = 'degraded';
            $health['checks']['cache'] = [
                'status' => 'unhealthy',
                'message' => 'Cache check failed: ' . $e->getMessage()
            ];
        }

        // Memory Usage
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $health['checks']['memory'] = [
            'status' => 'healthy',
            'current_usage' => $this->formatBytes($memoryUsage),
            'peak_usage' => $this->formatBytes($memoryPeak),
            'current_usage_bytes' => $memoryUsage,
            'peak_usage_bytes' => $memoryPeak
        ];

        // Disk Usage
        try {
            $freeBytes = disk_free_space('/');
            $totalBytes = disk_total_space('/');
            $usedBytes = $totalBytes - $freeBytes;
            $usagePercentage = ($usedBytes / $totalBytes) * 100;

            $health['checks']['disk'] = [
                'status' => $usagePercentage > 90 ? 'warning' : 'healthy',
                'total_space' => $this->formatBytes($totalBytes),
                'used_space' => $this->formatBytes($usedBytes),
                'free_space' => $this->formatBytes($freeBytes),
                'usage_percentage' => round($usagePercentage, 2)
            ];

            if ($usagePercentage > 90) {
                $health['status'] = 'degraded';
            }
        } catch (\Exception $e) {
            $health['checks']['disk'] = [
                'status' => 'unhealthy',
                'message' => 'Disk space check failed: ' . $e->getMessage()
            ];
        }

        // System Information
        $health['system'] = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ];

        // HTTP Status Code based on overall health
        $httpStatus = match ($health['status']) {
            'healthy' => 200,
            'degraded' => 200, // Still OK but with warnings
            'unhealthy' => 503, // Service Unavailable
            default => 200
        };

        return response()->json($health, $httpStatus);
    }

    /**
     * Simple health check endpoint
     *
     * @return JsonResponse
     */
    public function simple(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'message' => 'API is running',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Ready endpoint - checks if application is ready to serve traffic
     *
     * @return JsonResponse
     */
    public function ready(): JsonResponse
    {
        try {
            // Check critical components
            DB::connection()->getPdo();
            
            return response()->json([
                'status' => 'ready',
                'message' => 'Application is ready to serve traffic',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'not_ready',
                'message' => 'Application is not ready: ' . $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Liveness endpoint - basic application liveness check
     *
     * @return JsonResponse
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'alive',
            'message' => 'Application is alive',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
