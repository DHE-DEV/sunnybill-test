<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\SolarPlant;
use App\Models\SolarInverter;
use App\Models\SolarModule;
use App\Models\SolarBattery;

class FusionSolarService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $token = null;
    private ?Carbon $tokenExpiry = null;

    public function __construct()
    {
        $this->baseUrl = config('services.fusionsolar.base_url', 'https://eu5.fusionsolar.huawei.com/thirdData');
        $this->username = config('services.fusionsolar.username');
        $this->password = config('services.fusionsolar.password');
    }

    /**
     * Authentifizierung bei der FusionSolar API
     */
    private function authenticate(): bool
    {
        if ($this->token && $this->tokenExpiry && $this->tokenExpiry->isFuture()) {
            return true;
        }

        // Rate-Limiting berücksichtigen - warten zwischen Anfragen
        static $lastRequest = null;
        if ($lastRequest && (time() - $lastRequest) < 2) {
            sleep(2 - (time() - $lastRequest));
        }
        $lastRequest = time();

        try {
            $response = Http::timeout(60)
                ->connectTimeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'SunnyBill/1.0'
                ])
                ->post($this->baseUrl . '/login', [
                    'userName' => $this->username,
                    'systemCode' => $this->password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Prüfe auf Rate-Limiting
                if (isset($data['failCode']) && $data['failCode'] == 407) {
                    Log::warning('FusionSolar rate limit hit, waiting 10 seconds...');
                    sleep(10);
                    return $this->authenticate(); // Retry
                }
                
                if (isset($data['success']) && $data['success'] === true) {
                    // Token wird im XSRF-TOKEN Header übertragen, nicht in data
                    $this->token = $response->header('xsrf-token');
                    
                    if (!$this->token) {
                        // Fallback: Token aus Set-Cookie Header extrahieren
                        $cookies = $response->header('set-cookie');
                        if (is_array($cookies)) {
                            foreach ($cookies as $cookie) {
                                if (strpos($cookie, 'XSRF-TOKEN=') === 0) {
                                    $this->token = substr($cookie, 11, strpos($cookie, ';') - 11);
                                    break;
                                }
                            }
                        } elseif (is_string($cookies) && strpos($cookies, 'XSRF-TOKEN=') === 0) {
                            $this->token = substr($cookies, 11, strpos($cookies, ';') - 11);
                        }
                    }
                    
                    if ($this->token) {
                        // Token ist normalerweise 30 Minuten gültig
                        $this->tokenExpiry = Carbon::now()->addMinutes(25);
                        
                        Log::info('FusionSolar authentication successful', [
                            'token_preview' => substr($this->token, 0, 10) . '...'
                        ]);
                        return true;
                    }
                }
            }

            Log::error('FusionSolar authentication failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'headers' => $response->headers()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('FusionSolar authentication error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * API-Request mit automatischer Authentifizierung
     */
    private function apiRequest(string $endpoint, array $params = []): ?array
    {
        if (!$this->authenticate()) {
            return null;
        }

        // Rate-Limiting berücksichtigen
        static $lastApiRequest = null;
        if ($lastApiRequest && (time() - $lastApiRequest) < 1) {
            sleep(1);
        }
        $lastApiRequest = time();

        try {
            $jsonBody = empty($params) ? '{}' : json_encode($params);
            
            $response = Http::timeout(60)
                ->connectTimeout(30)
                ->withHeaders([
                    'XSRF-TOKEN' => $this->token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'SunnyBill/1.0'
                ])->withBody($jsonBody, 'application/json')->post($this->baseUrl . $endpoint);

            if ($response->successful()) {
                $data = $response->json();
                
                // Prüfe auf Rate-Limiting
                if (isset($data['failCode']) && $data['failCode'] == 407) {
                    Log::warning('FusionSolar API rate limit hit, waiting 5 seconds...', ['endpoint' => $endpoint]);
                    sleep(5);
                    return $this->apiRequest($endpoint, $params); // Retry
                }
                
                // Prüfe auf Berechtigungsfehler
                if (isset($data['failCode']) && $data['failCode'] == 20056) {
                    Log::error('FusionSolar authorization error: Northbound user not authorized for resources', [
                        'endpoint' => $endpoint,
                        'message' => $data['message'] ?? 'No message'
                    ]);
                    return null;
                }
                
                if (isset($data['success']) && $data['success'] === true) {
                    return $data['data'] ?? [];
                }
            }

            Log::error('FusionSolar API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'response' => $response->json(),
                'params' => $params,
                'body' => $jsonBody
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('FusionSolar API request error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Alle Anlagen abrufen
     */
    public function getPlantList(): ?array
    {
        return $this->apiRequest('/getStationList');
    }

    /**
     * Anlagen-Details abrufen
     */
    public function getPlantDetails(string $stationCode): ?array
    {
        return $this->apiRequest('/getStationRealKpi', [
            'stationCodes' => $stationCode
        ]);
    }

    /**
     * Anlagen-Echtzeitdaten abrufen
     */
    public function getPlantRealtimeData(string $stationCode): ?array
    {
        return $this->apiRequest('/getDevRealKpi', [
            'devIds' => $stationCode,
            'devTypeId' => 1 // 1 = Inverter
        ]);
    }

    /**
     * Historische Daten abrufen
     */
    public function getPlantHistoricalData(string $stationCode, Carbon $startTime, Carbon $endTime): ?array
    {
        return $this->apiRequest('/getKpiStationHour', [
            'stationCodes' => $stationCode,
            'collectTime' => $startTime->timestamp * 1000, // Millisekunden
            'endTime' => $endTime->timestamp * 1000
        ]);
    }

    /**
     * Monatliche Ertragsdaten abrufen
     */
    public function getMonthlyYield(string $stationCode, int $year, int $month): ?array
    {
        $startTime = Carbon::create($year, $month, 1)->startOfMonth();
        $endTime = Carbon::create($year, $month, 1)->endOfMonth();

        return $this->apiRequest('/getKpiStationMonth', [
            'stationCodes' => $stationCode,
            'collectTime' => $startTime->timestamp * 1000,
            'endTime' => $endTime->timestamp * 1000
        ]);
    }

    /**
     * Anlagen-Alarme abrufen
     */
    public function getPlantAlarms(string $stationCode): ?array
    {
        return $this->apiRequest('/getAlarmList', [
            'stationCodes' => $stationCode,
            'language' => 'en_US'
        ]);
    }

    /**
     * Geräteliste einer Anlage abrufen
     */
    public function getDeviceList(string $stationCode): ?array
    {
        return $this->apiRequest('/getDevList', [
            'stationCodes' => $stationCode
        ]);
    }

    /**
     * Wechselrichter-Details abrufen
     */
    public function getInverterDetails(string $deviceId): ?array
    {
        return $this->apiRequest('/getDevRealKpi', [
            'devIds' => $deviceId,
            'devTypeId' => 1 // 1 = Inverter
        ]);
    }

    /**
     * String-Details abrufen (Module)
     */
    public function getStringDetails(string $deviceId): ?array
    {
        return $this->apiRequest('/getDevRealKpi', [
            'devIds' => $deviceId,
            'devTypeId' => 47 // 47 = String
        ]);
    }

    /**
     * Batterie-Details abrufen
     */
    public function getBatteryDetails(string $deviceId): ?array
    {
        return $this->apiRequest('/getDevRealKpi', [
            'devIds' => $deviceId,
            'devTypeId' => 39 // 39 = Battery
        ]);
    }

    /**
     * Detaillierte Geräteinformationen abrufen
     */
    public function getDeviceInfo(string $deviceId): ?array
    {
        return $this->apiRequest('/getDeviceInfo', [
            'devId' => $deviceId
        ]);
    }

    /**
     * Alle verfügbaren Anlagen mit Details synchronisieren
     */
    public function syncAllPlants(): array
    {
        $result = [
            'success' => false,
            'synced' => 0,
            'errors' => [],
            'plants' => [],
            'components' => [
                'inverters' => 0,
                'modules' => 0,
                'batteries' => 0
            ]
        ];

        $plantList = $this->getPlantList();
        
        if (!$plantList) {
            $result['errors'][] = 'Konnte Anlagenliste nicht abrufen';
            return $result;
        }

        foreach ($plantList as $plant) {
            try {
                $stationCode = $plant['stationCode'] ?? null;
                
                if (!$stationCode) {
                    continue;
                }

                // Detaillierte Daten abrufen
                $details = $this->getPlantDetails($stationCode);
                $realtimeData = $this->getPlantRealtimeData($stationCode);

                $plantData = [
                    'fusion_solar_id' => $stationCode,
                    'name' => $plant['stationName'] ?? 'Unbekannte Anlage',
                    'location' => $plant['stationAddr'] ?? '',
                    'total_capacity_kw' => ($plant['capacity'] ?? 0) / 1000, // Watt zu kW
                    'installation_date' => isset($plant['buildTime']) ?
                        Carbon::createFromTimestamp($plant['buildTime'] / 1000)->format('Y-m-d') :
                        now()->format('Y-m-d'),
                    'status' => $this->mapPlantStatus($plant['stationStatus'] ?? 1),
                    'is_active' => ($plant['stationStatus'] ?? 1) === 1,
                ];

                // Zusätzliche Details aus der Detail-API
                if ($details && isset($details[0])) {
                    $detail = $details[0];
                    $plantData['expected_annual_yield_kwh'] = $detail['yearPower'] ?? null;
                }

                // Anlage in Datenbank speichern/aktualisieren
                $solarPlant = SolarPlant::updateOrCreate(
                    ['fusion_solar_id' => $stationCode],
                    $plantData
                );

                // Komponenten synchronisieren
                $componentResult = $this->syncPlantComponents($stationCode, $solarPlant);
                $plantData['components'] = $componentResult;
                
                $result['components']['inverters'] += $componentResult['inverters'] ?? 0;
                $result['components']['modules'] += $componentResult['modules'] ?? 0;
                $result['components']['batteries'] += $componentResult['batteries'] ?? 0;

                $result['plants'][] = $plantData;
                $result['synced']++;

            } catch (\Exception $e) {
                $result['errors'][] = "Fehler bei Anlage {$stationCode}: " . $e->getMessage();
                Log::error('FusionSolar sync error for plant: ' . $stationCode, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $result['success'] = $result['synced'] > 0;
        return $result;
    }

    /**
     * Komponenten einer Anlage synchronisieren
     */
    public function syncPlantComponents(string $stationCode, SolarPlant $solarPlant): array
    {
        $result = [
            'inverters' => 0,
            'modules' => 0,
            'batteries' => 0,
            'errors' => []
        ];

        try {
            // Geräteliste abrufen
            $deviceList = $this->getDeviceList($stationCode);
            
            if (!$deviceList) {
                $result['errors'][] = 'Konnte Geräteliste nicht abrufen';
                return $result;
            }

            foreach ($deviceList as $device) {
                $deviceType = $device['devTypeId'] ?? null;
                $deviceId = $device['devDn'] ?? $device['devId'] ?? null; // devDn ist die korrekte ID
                
                if (!$deviceId) continue;

                try {
                    switch ($deviceType) {
                        case 1: // Wechselrichter
                            $inverterData = $this->syncInverter($deviceId, $device, $solarPlant);
                            if ($inverterData) {
                                $result['inverters']++;
                            }
                            break;
                            
                        case 46: // Optimizer (PV-Module)
                            $moduleData = $this->syncOptimizer($deviceId, $device, $solarPlant);
                            if ($moduleData) {
                                $result['modules']++;
                            }
                            break;
                            
                        case 47: // Meter/String
                            // Meter überspringen, da es kein PV-Modul ist
                            if (strpos($device['devName'] ?? '', 'Meter') === false) {
                                $moduleData = $this->syncModules($deviceId, $device, $solarPlant);
                                if ($moduleData) {
                                    $result['modules'] += $moduleData;
                                }
                            }
                            break;
                            
                        case 39: // Batterie
                            $batteryData = $this->syncBattery($deviceId, $device, $solarPlant);
                            if ($batteryData) {
                                $result['batteries']++;
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = "Fehler bei Gerät {$deviceId}: " . $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $result['errors'][] = "Fehler beim Abrufen der Komponenten: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Wechselrichter synchronisieren
     */
    private function syncInverter(string $deviceId, array $deviceInfo, SolarPlant $solarPlant): ?array
    {
        // Versuche Detail-APIs, aber verwende Fallback-Daten aus deviceInfo
        $details = $this->getInverterDetails($deviceId);
        $info = $this->getDeviceInfo($deviceId);
        
        $inverterData = [
            'solar_plant_id' => $solarPlant->id,
            'fusion_solar_device_id' => $deviceId,
            'name' => $deviceInfo['devName'] ?? "Wechselrichter {$deviceId}",
            'model' => $deviceInfo['model'] ?? $deviceInfo['invType'] ?? null,
            'serial_number' => $deviceInfo['esnCode'] ?? null,
            'manufacturer' => 'Huawei',
            'rated_power_kw' => $this->extractPowerFromModel($deviceInfo['model'] ?? $deviceInfo['invType'] ?? ''),
            'status' => 'normal', // Default, da devStatus nicht verfügbar
            'is_active' => true,
            'firmware_version' => $deviceInfo['softwareVersion'] ?? null,
            'last_sync_at' => now(),
        ];

        // Falls Detail-APIs verfügbar sind, ergänze die Daten
        if ($details && isset($details[0])) {
            $detail = $details[0];
            $inverterData['current_power_kw'] = isset($detail['activePower']) ? $detail['activePower'] / 1000 : null;
            $inverterData['current_voltage_v'] = $detail['ab_u'] ?? null;
            $inverterData['current_current_a'] = $detail['ab_i'] ?? null;
            $inverterData['current_frequency_hz'] = $detail['elec_freq'] ?? null;
            $inverterData['current_temperature_c'] = $detail['temperature'] ?? null;
            $inverterData['daily_yield_kwh'] = isset($detail['dayPower']) ? $detail['dayPower'] / 1000 : null;
            $inverterData['total_yield_kwh'] = isset($detail['totalPower']) ? $detail['totalPower'] / 1000 : null;
        }

        // Wechselrichter in Datenbank speichern/aktualisieren
        SolarInverter::updateOrCreate(
            [
                'solar_plant_id' => $solarPlant->id,
                'fusion_solar_device_id' => $deviceId
            ],
            $inverterData
        );

        return $inverterData;
    }

    /**
     * Optimizer (PV-Module) synchronisieren
     */
    private function syncOptimizer(string $deviceId, array $deviceInfo, SolarPlant $solarPlant): ?array
    {
        $moduleData = [
            'solar_plant_id' => $solarPlant->id,
            'fusion_solar_device_id' => $deviceId,
            'name' => $deviceInfo['devName'] ?? "Modul {$deviceId}",
            'model' => $deviceInfo['model'] ?? null,
            'manufacturer' => 'Huawei',
            'rated_power_wp' => $this->extractPowerFromModel($deviceInfo['model'] ?? '', true), // true für Wp
            'status' => 'normal',
            'is_active' => true,
            'cell_type' => 'mono', // Standard für Huawei Module
            'last_sync_at' => now(),
        ];

        // Modul in Datenbank speichern/aktualisieren
        SolarModule::updateOrCreate(
            [
                'solar_plant_id' => $solarPlant->id,
                'fusion_solar_device_id' => $deviceId
            ],
            $moduleData
        );

        return $moduleData;
    }

    /**
     * Leistung aus Modellname extrahieren
     */
    private function extractPowerFromModel(string $model, bool $isModule = false): ?float
    {
        if (empty($model)) return null;
        
        // Für Wechselrichter: SUN2000-4KTL-M1 -> 4 kW
        if (!$isModule && preg_match('/(\d+)KTL/', $model, $matches)) {
            return (float) $matches[1];
        }
        
        // Für Module: SUN2000-450W-P2 -> 450 Wp
        if ($isModule && preg_match('/(\d+)W/', $model, $matches)) {
            return (float) $matches[1];
        }
        
        return null;
    }

    /**
     * Module synchronisieren
     */
    private function syncModules(string $deviceId, array $deviceInfo, SolarPlant $solarPlant): int
    {
        $details = $this->getStringDetails($deviceId);
        $info = $this->getDeviceInfo($deviceId);
        
        // Ein String kann mehrere Module enthalten
        $moduleCount = $info['moduleCount'] ?? 1;
        
        for ($i = 1; $i <= $moduleCount; $i++) {
            $moduleData = [
                'solar_plant_id' => $solarPlant->id,
                'fusion_solar_device_id' => $deviceId . "_module_{$i}",
                'name' => ($deviceInfo['devName'] ?? "String {$deviceId}") . " - Modul {$i}",
                'model' => $info['moduleModel'] ?? null,
                'manufacturer' => $info['moduleManufacturer'] ?? 'Unbekannt',
                'rated_power_wp' => $info['modulePower'] ?? null,
                'string_number' => $deviceInfo['stringNumber'] ?? null,
                'position_in_string' => $i,
                'status' => $this->mapDeviceStatus($deviceInfo['devStatus'] ?? 1),
                'is_active' => ($deviceInfo['devStatus'] ?? 1) === 1,
                'current_power_w' => isset($details[0]['activePower']) ? ($details[0]['activePower'] / $moduleCount) : null,
                'current_voltage_v' => $details[0]['pv_voltage'] ?? null,
                'current_current_a' => $details[0]['pv_current'] ?? null,
                'last_sync_at' => now(),
            ];
            
            // Modul in Datenbank speichern/aktualisieren
            SolarModule::updateOrCreate(
                [
                    'solar_plant_id' => $solarPlant->id,
                    'fusion_solar_device_id' => $deviceId . "_module_{$i}"
                ],
                $moduleData
            );
        }
        
        return $moduleCount;
    }

    /**
     * Batterie synchronisieren
     */
    private function syncBattery(string $deviceId, array $deviceInfo, SolarPlant $solarPlant): ?array
    {
        // Versuche Detail-APIs, aber verwende Fallback-Daten aus deviceInfo
        $details = $this->getBatteryDetails($deviceId);
        $info = $this->getDeviceInfo($deviceId);
        
        $batteryData = [
            'solar_plant_id' => $solarPlant->id,
            'fusion_solar_device_id' => $deviceId,
            'name' => $deviceInfo['devName'] ?? "Batterie {$deviceId}",
            'model' => $deviceInfo['model'] ?? null,
            'manufacturer' => 'Huawei',
            'capacity_kwh' => $this->extractCapacityFromModel($deviceInfo['model'] ?? ''),
            'chemistry' => 'lifepo4', // Standard für Huawei LUNA Batterien
            'status' => 'normal',
            'is_active' => true,
            'last_sync_at' => now(),
        ];

        // Falls Detail-APIs verfügbar sind, ergänze die Daten
        if ($details && isset($details[0])) {
            $detail = $details[0];
            $batteryData['status'] = $this->mapBatteryStatus($detail['runningStatus'] ?? 1);
            $batteryData['current_soc_percent'] = $detail['soc'] ?? null;
            $batteryData['current_voltage_v'] = $detail['voltage'] ?? null;
            $batteryData['current_current_a'] = $detail['current'] ?? null;
            $batteryData['current_power_kw'] = isset($detail['chargeDischargePower']) ? $detail['chargeDischargePower'] / 1000 : null;
            $batteryData['current_temperature_c'] = $detail['temperature'] ?? null;
            $batteryData['daily_charge_kwh'] = isset($detail['dayChargeCapacity']) ? $detail['dayChargeCapacity'] / 1000 : null;
            $batteryData['daily_discharge_kwh'] = isset($detail['dayDischargeCapacity']) ? $detail['dayDischargeCapacity'] / 1000 : null;
            $batteryData['total_charge_kwh'] = isset($detail['totalChargeCapacity']) ? $detail['totalChargeCapacity'] / 1000 : null;
            $batteryData['total_discharge_kwh'] = isset($detail['totalDischargeCapacity']) ? $detail['totalDischargeCapacity'] / 1000 : null;
            $batteryData['health_percent'] = $detail['soh'] ?? null;
        }

        // Batterie in Datenbank speichern/aktualisieren
        SolarBattery::updateOrCreate(
            [
                'solar_plant_id' => $solarPlant->id,
                'fusion_solar_device_id' => $deviceId
            ],
            $batteryData
        );

        return $batteryData;
    }

    /**
     * Kapazität aus Modellname extrahieren
     */
    private function extractCapacityFromModel(string $model): ?float
    {
        if (empty($model)) return null;
        
        // Für Batterien: LUNA2000-5KW-C0 -> 5 kWh
        if (preg_match('/(\d+)KW/', $model, $matches)) {
            return (float) $matches[1];
        }
        
        return null;
    }

    /**
     * Geräte-Status-Mapping
     */
    private function mapDeviceStatus(int $status): string
    {
        return match($status) {
            1 => 'normal',
            2 => 'alarm',
            3 => 'offline',
            4 => 'maintenance',
            default => 'normal'
        };
    }

    /**
     * Batterie-Status-Mapping
     */
    private function mapBatteryStatus(int $status): string
    {
        return match($status) {
            0 => 'standby',
            1 => 'charging',
            2 => 'discharging',
            3 => 'normal',
            4 => 'alarm',
            default => 'normal'
        };
    }

    /**
     * Status-Mapping von FusionSolar zu unserem System
     */
    private function mapPlantStatus(int $status): string
    {
        return match($status) {
            1 => 'active',      // Normal
            2 => 'maintenance', // Alarm
            3 => 'inactive',    // Offline
            default => 'planned'
        };
    }
}