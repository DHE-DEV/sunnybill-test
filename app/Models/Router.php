<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Router extends Model
{
    protected $fillable = [
        'name',
        'model',
        'serial_number',
        'lan_mac_address',
        'location',
        'description',
        'login_username',
        'login_password',
        'is_active',
        'connection_status',
        'last_seen_at',
        'operator',
        'signal_strength',
        'network_type',
        'signal_bars',
        'webhook_token',
        'ip_address',
        'webhook_port',
        'latitude',
        'longitude',
        'last_data',
        'total_webhooks',
        'installed_at',
        'last_restart_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'installed_at' => 'datetime',
        'last_restart_at' => 'datetime',
        'last_data' => 'json',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'total_webhooks' => 'integer',
        'signal_strength' => 'integer',
        'signal_bars' => 'integer',
        'webhook_port' => 'integer',
        'login_password' => 'encrypted',
    ];

    protected $attributes = [
        'model' => 'RUTX50',
        'is_active' => true,
        'connection_status' => 'offline',
        'webhook_port' => 3000,
        'total_webhooks' => 0,
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($router) {
            if (empty($router->webhook_token)) {
                $router->webhook_token = Str::random(32);
            }
        });
    }

    /**
     * Relationship to SIM Cards
     */
    public function simCards(): HasMany
    {
        return $this->hasMany(SimCard::class);
    }

    /**
     * Status-Optionen für Select-Felder
     */
    public static function getStatusOptions(): array
    {
        return [
            'online' => 'Online',
            'delayed' => 'Verzögert',
            'offline' => 'Offline',
        ];
    }

    /**
     * Modell-Optionen für Select-Felder
     */
    public static function getModelOptions(): array
    {
        return [
            'RUTX50' => 'Teltonika RUTX50',
            'RUTX11' => 'Teltonika RUTX11',
            'RUTX12' => 'Teltonika RUTX12',
            'RUT241' => 'Teltonika RUT241',
            'RUT955' => 'Teltonika RUT955',
            'Sonstiges' => 'Sonstiges',
        ];
    }

    /**
     * Berechnet den Verbindungsstatus basierend auf last_seen_at
     */
    public function updateConnectionStatus(): string
    {
        if (!$this->last_seen_at) {
            $this->connection_status = 'offline';
            return 'offline';
        }

        $minutesAgo = $this->last_seen_at->diffInMinutes(now());

        if ($minutesAgo <= 3) {
            $this->connection_status = 'online';
        } elseif ($minutesAgo <= 10) {
            $this->connection_status = 'delayed';
        } else {
            $this->connection_status = 'offline';
        }

        return $this->connection_status;
    }

    /**
     * Berechnet Signalbalken basierend auf Signal Strength
     */
    public function calculateSignalBars(?int $signalStrength = null): int
    {
        $strength = $signalStrength ?? $this->signal_strength;
        
        if (!$strength) {
            return 0;
        }

        if ($strength >= -70) {
            return 5; // Sehr gut
        } elseif ($strength >= -80) {
            return 4; // Gut
        } elseif ($strength >= -90) {
            return 3; // Mittel
        } elseif ($strength >= -100) {
            return 2; // Schwach
        } else {
            return 1; // Sehr schwach
        }
    }

    /**
     * Aktualisiert Router-Daten von Webhook
     */
    public function updateFromWebhook(array $data): void
    {
        $this->update([
            'operator' => $data['operator'] ?? null,
            'signal_strength' => $data['signal_strength'] ?? null,
            'network_type' => $data['network_type'] ?? null,
            'signal_bars' => $this->calculateSignalBars($data['signal_strength'] ?? null),
            'last_seen_at' => now(),
            'last_data' => $data,
            'total_webhooks' => $this->total_webhooks + 1,
        ]);

        $this->updateConnectionStatus();
        $this->save();
    }

    /**
     * Prüft ob Router Koordinaten hat
     */
    public function hasCoordinates(): bool
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Formatierte Koordinaten für Anzeige
     */
    public function getFormattedCoordinatesAttribute(): ?string
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return number_format($this->latitude, 6, ',', '.') . '°N, ' 
             . number_format($this->longitude, 6, ',', '.') . '°E';
    }

    /**
     * Minutenanzahl seit letztem Signal
     */
    public function getMinutesAgoAttribute(): ?int
    {
        if (!$this->last_seen_at) {
            return null;
        }

        return $this->last_seen_at->diffInMinutes(now());
    }

    /**
     * Formatierte Zeit seit letztem Signal
     */
    public function getLastSeenFormattedAttribute(): string
    {
        if (!$this->last_seen_at) {
            return 'Nie';
        }

        $diff = round($this->last_seen_at->diffInMinutes(now()));
        
        if ($diff < 1) {
            return 'Gerade eben';
        } elseif ($diff < 60) {
            return "vor {$diff} Minute" . ($diff > 1 ? 'n' : '');
        } elseif ($diff < 1440) {
            $hours = floor($diff / 60);
            return "vor {$hours} Stunde" . ($hours > 1 ? 'n' : '');
        } else {
            return $this->last_seen_at->format('d.m.Y H:i') . ' Uhr';
        }
    }

    /**
     * Status Badge Farbe
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->connection_status) {
            'online' => 'success',
            'delayed' => 'warning',
            'offline' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Status Badge Text
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->connection_status) {
            'online' => 'Online',
            'delayed' => 'Verzögert',
            'offline' => 'Offline',
            default => 'Unbekannt',
        };
    }

    /**
     * Signalstärke Farbe für Badge
     */
    public function getSignalStrengthColorAttribute(): string
    {
        if (!$this->signal_bars) {
            return 'gray';
        }

        return match($this->signal_bars) {
            5 => 'success',
            4 => 'success', 
            3 => 'warning',
            2 => 'danger',
            1 => 'danger',
            default => 'gray',
        };
    }

    /**
     * Webhook URL für diesen Router
     */
    public function getWebhookUrlAttribute(): string
    {
        $baseUrl = config('app.url');
        return "{$baseUrl}/api/router-webhook/{$this->webhook_token}";
    }

    /**
     * Dashboard URL für diesen Router
     */
    public function getDashboardUrlAttribute(): string
    {
        $baseUrl = config('app.url');
        return "{$baseUrl}/router-dashboard/{$this->id}";
    }

    /**
     * Test Curl Command für diesen Router
     */
    public function getTestCurlCommandAttribute(): string
    {
        return 'curl -k -X POST -H "Content-Type: application/json" -d \'{"operator": "Telekom.de", "signal_strength": -65, "network_type": "5G"}\' ' . $this->webhook_url;
    }

    /**
     * Router neu starten
     * Sendet einen Neustart-Befehl an den Router
     */
    public function restart(): bool
    {
        \Log::info("Router restart requested", [
            'router_id' => $this->id,
            'router_name' => $this->name,
            'ip_address' => $this->ip_address,
            'model' => $this->model
        ]);

        // Für externe Router verwenden wir verschiedene Methoden
        if ($this->isExternalRouter()) {
            return $this->restartExternalRouter();
        } else {
            return $this->restartLocalRouter();
        }
    }

    /**
     * Prüft ob Router extern ist (nicht im lokalen Netzwerk)
     */
    protected function isExternalRouter(): bool
    {
        if (!$this->ip_address) {
            return true; // Ohne IP-Adresse nehmen wir an, dass er extern ist
        }

        // Lokale IP-Bereiche prüfen
        $localRanges = [
            '192.168.',
            '10.',
            '172.16.',
            '172.17.',
            '172.18.',
            '172.19.',
            '172.20.',
            '172.21.',
            '172.22.',
            '172.23.',
            '172.24.',
            '172.25.',
            '172.26.',
            '172.27.',
            '172.28.',
            '172.29.',
            '172.30.',
            '172.31.',
            '127.0.0.1',
            'localhost'
        ];

        foreach ($localRanges as $range) {
            if (str_starts_with($this->ip_address, $range)) {
                return false; // Lokaler Router
            }
        }

        return true; // Externe IP oder unbekannt = extern
    }

    /**
     * Neustart für externe Router (über Cloud-Management oder SMS)
     */
    protected function restartExternalRouter(): bool
    {
        \Log::info("Attempting external router restart", [
            'router_id' => $this->id,
            'router_name' => $this->name
        ]);

        try {
            // Für externe Router gibt es verschiedene Optionen:
            // 1. Cloud Management API (falls verfügbar)
            // 2. SMS-Befehl an Router
            // 3. Remote Management Platform
            
            // Da die Router extern sind, simulieren wir erstmal den Befehl
            // und loggen ihn für manuelle Bearbeitung
            
            \Log::warning("External router restart requested - manual intervention may be required", [
                'router_id' => $this->id,
                'router_name' => $this->name,
                'ip_address' => $this->ip_address,
                'action_required' => 'Manual router restart via physical access, SMS, or cloud management platform',
                'timestamp' => now()->toISOString()
            ]);

            // Restart-Zeitpunkt speichern (auch für externe Router)
            $this->update(['last_restart_at' => now()]);
            
            // Für jetzt geben wir false zurück, da externe Router manuell neugestartet werden müssen
            return false;
            
        } catch (\Exception $e) {
            \Log::error("External router restart exception", [
                'router_id' => $this->id,
                'router_name' => $this->name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Neustart für lokale Router (HTTP API)
     */
    protected function restartLocalRouter(): bool
    {
        if (!$this->ip_address) {
            \Log::error("Local router restart failed: No IP address configured", [
                'router_id' => $this->id,
                'router_name' => $this->name
            ]);
            return false;
        }

        try {
            // Teltonika Router Neustart über HTTP API
            $restartUrl = "http://{$this->ip_address}/cgi-bin/luci/admin/system/reboot";
            
            \Log::info("Attempting local router restart", [
                'router_id' => $this->id,
                'router_name' => $this->name,
                'ip_address' => $this->ip_address,
                'restart_url' => $restartUrl
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $restartUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            // Basic Auth für Teltonika Router
            curl_setopt($ch, CURLOPT_USERPWD, "root:admin01");
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            \Log::info("Local router restart attempt result", [
                'router_id' => $this->id,
                'http_code' => $httpCode,
                'curl_error' => $curlError
            ]);
            
            $success = ($httpCode >= 200 && $httpCode < 400) || $curlError === '';
            
            if ($success) {
                $this->update(['last_restart_at' => now()]);
                \Log::info("Local router restart command sent successfully", [
                    'router_id' => $this->id,
                    'router_name' => $this->name
                ]);
            }
            
            return $success;
            
        } catch (\Exception $e) {
            \Log::error("Local router restart exception", [
                'router_id' => $this->id,
                'router_name' => $this->name,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Prüft ob ein Neustart kürzlich durchgeführt wurde
     */
    public function hasRecentRestart(): bool
    {
        if (!$this->last_restart_at) {
            return false;
        }
        
        return $this->last_restart_at->diffInMinutes(now()) < 5;
    }

    /**
     * Formatierte Anzeige des letzten Neustarts
     */
    public function getLastRestartFormattedAttribute(): ?string
    {
        if (!$this->last_restart_at) {
            return null;
        }
        
        $diff = round($this->last_restart_at->diffInMinutes(now()));
        
        if ($diff < 1) {
            return 'Gerade eben';
        } elseif ($diff < 60) {
            return "vor {$diff} Minute" . ($diff > 1 ? 'n' : '');
        } elseif ($diff < 1440) {
            $hours = floor($diff / 60);
            return "vor {$hours} Stunde" . ($hours > 1 ? 'n' : '');
        } else {
            return $this->last_restart_at->format('d.m.Y H:i') . ' Uhr';
        }
    }
}
