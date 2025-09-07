<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OnceApiService
{
    private string $baseUrl = 'https://api.1nce.com';
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct()
    {
        $this->clientId = config('services.nce.client_id') ?? env('1NCE_CLIENT_ID');
        $this->clientSecret = config('services.nce.client_secret') ?? env('1NCE_CLIENT_SECRET');

        if (!$this->clientId || !$this->clientSecret) {
            throw new Exception('1nce API credentials not configured. Please set 1NCE_CLIENT_ID and 1NCE_CLIENT_SECRET in your .env file.');
        }
    }

    /**
     * Authenticate with 1nce API and get access token
     */
    private function authenticate(): bool
    {
        try {
            Log::info('1nce API: Starting authentication');

            // Use Basic Authentication with Base64 encoded credentials
            $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);
            
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($this->baseUrl . '/management-api/oauth/token', [
                'grant_type' => 'client_credentials'
            ]);

            if (!$response->successful()) {
                Log::error('1nce API authentication failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

            $data = $response->json();
            $this->accessToken = $data['access_token'];
            $this->tokenExpiresAt = time() + ($data['expires_in'] ?? 3600);

            Log::info('1nce API: Authentication successful');
            return true;

        } catch (Exception $e) {
            Log::error('1nce API authentication exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get valid access token (authenticate if needed)
     */
    private function getAccessToken(): ?string
    {
        // Check if token is still valid (with 5 minute buffer)
        if ($this->accessToken && $this->tokenExpiresAt && (time() + 300) < $this->tokenExpiresAt) {
            return $this->accessToken;
        }

        // Authenticate to get new token
        if ($this->authenticate()) {
            return $this->accessToken;
        }

        return null;
    }

    /**
     * Make authenticated API request
     */
    private function makeRequest(string $endpoint, array $params = []): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            Log::error('1nce API: No valid access token available');
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->get($this->baseUrl . '/management-api' . $endpoint, $params);

            if (!$response->successful()) {
                Log::error('1nce API request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('1nce API request exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all SIM cards from 1nce API
     */
    public function getSimCards(): array
    {
        Log::info('1nce API: Fetching SIM cards');

        $data = $this->makeRequest('/v1/sims');
        
        if (!$data || !is_array($data) || empty($data)) {
            Log::warning('1nce API: No SIM cards data received');
            return [];
        }

        $simCards = [];
        foreach ($data as $sim) {
            $simCards[] = $this->transformSimCardData($sim);
        }

        Log::info('1nce API: Retrieved ' . count($simCards) . ' SIM cards');
        return $simCards;
    }

    /**
     * Transform 1nce API SIM card data to our format
     */
    private function transformSimCardData(array $simData): array
    {
        // Map 1nce API status to our database enum values
        $statusMapping = [
            'ENABLED' => 'active',
            'DISABLED' => 'inactive',
            'SUSPENDED' => 'suspended',
            'TERMINATED' => 'expired',
            'READY' => 'inactive',
            'SHIPPED' => 'inactive',
        ];

        return [
            'iccid' => $simData['iccid'] ?? null,
            'msisdn' => $simData['msisdn'] ?? null,
            'imsi' => $simData['imsi'] ?? null,
            'imei' => $simData['imei'] ?? null,
            'provider' => '1nce',
            'tariff' => $simData['tariff'] ?? '1nce Standard',
            'contract_type' => 'postpaid', // Default for 1nce
            'monthly_cost' => 0, // Will be set based on actual usage/plan
            'data_limit_mb' => null, // Typically unlimited or very high for IoT
            'contract_start' => isset($simData['activation_date']) ? 
                date('Y-m-d', strtotime($simData['activation_date'])) : null,
            'contract_end' => isset($simData['expiry_date']) ? 
                date('Y-m-d', strtotime($simData['expiry_date'])) : null,
            'data_used_mb' => isset($simData['data_usage']) ? 
                round($simData['data_usage'] / 1024 / 1024, 2) : 0,
            'last_activity' => isset($simData['last_activity']) ? 
                date('Y-m-d H:i:s', strtotime($simData['last_activity'])) : null,
            'signal_strength' => $this->parseSignalStrength($simData),
            'status' => $statusMapping[strtoupper($simData['status'] ?? '')] ?? 'inactive',
            'description' => 'Importiert von 1nce API am ' . now()->format('d.m.Y H:i'),
        ];
    }

    /**
     * Parse signal strength from 1nce API data
     */
    private function parseSignalStrength(array $simData): ?int
    {
        // Check various possible field names for signal strength
        $signalFields = [
            'signal_strength',
            'rssi',
            'signal_level',
            'radio_signal',
            'network_signal',
            'signal_quality',
        ];

        foreach ($signalFields as $field) {
            if (isset($simData[$field])) {
                $value = $simData[$field];
                
                // If it's already a number, use it directly
                if (is_numeric($value)) {
                    return intval($value);
                }
                
                // Try to extract numeric value from string (e.g., "-75 dBm")
                if (is_string($value) && preg_match('/(-?\d+)/', $value, $matches)) {
                    return intval($matches[1]);
                }
            }
        }

        // Check if there's connectivity data that might contain signal info
        if (isset($simData['connectivity'])) {
            $connectivity = $simData['connectivity'];
            
            if (isset($connectivity['signal_strength'])) {
                return is_numeric($connectivity['signal_strength']) ? 
                    intval($connectivity['signal_strength']) : null;
            }
            
            if (isset($connectivity['rssi'])) {
                return is_numeric($connectivity['rssi']) ? 
                    intval($connectivity['rssi']) : null;
            }
        }

        return null;
    }

    /**
     * Parse data limit from various formats
     */
    private function parseDataLimit(?string $dataLimit): ?int
    {
        if (!$dataLimit) {
            return null;
        }

        // Remove spaces and convert to uppercase
        $dataLimit = strtoupper(str_replace(' ', '', $dataLimit));

        // Extract number and unit
        if (preg_match('/^(\d+(?:\.\d+)?)([KMGT]?B?)$/', $dataLimit, $matches)) {
            $value = floatval($matches[1]);
            $unit = $matches[2] ?? '';

            // Convert to MB
            switch ($unit) {
                case 'KB':
                    return intval($value / 1024);
                case 'MB':
                case 'M':
                    return intval($value);
                case 'GB':
                case 'G':
                    return intval($value * 1024);
                case 'TB':
                case 'T':
                    return intval($value * 1024 * 1024);
                default:
                    // Assume MB if no unit
                    return intval($value);
            }
        }

        return null;
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return false;
            }

            // Try to make a simple request to test the connection
            $response = Http::withToken($token)->get($this->baseUrl . '/management-api/v1/sims');
            
            return $response->successful();

        } catch (Exception $e) {
            Log::error('1nce API connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}
