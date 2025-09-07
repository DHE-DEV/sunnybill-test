# Teltonika RUTX50 Integration Guide

## 🎯 Was Sie erreichen wollen:
- ✅ **Online/Offline Status** in Echtzeit
- ✅ **Provider-Name** (z.B. Telekom.de, Vodafone)  
- ✅ **Signalstärke** (-65 dBm, 5 Balken)
- ✅ **Verbindungszeit** (seit wann online)
- ✅ **Letzte Datenlieferung** (wann Router zuletzt gemeldet)

## 📋 Ihr System ist bereits vorbereitet!

Ihr Router-System hat bereits alle benötigten Felder:
- `connection_status` → Online/Delayed/Offline
- `operator` → Provider-Name  
- `signal_strength` → -65 dBm
- `signal_bars` → 1-5 Balken (automatisch berechnet)
- `network_type` → 4G/5G
- `last_seen_at` → Letzte Verbindung
- `total_webhooks` → Anzahl empfangener Updates

## 🔧 3 Integration-Optionen

### Option 1: HTTP Webhooks (EMPFOHLEN) ⭐
**Vorteile:** Einfach, Echtzeit, zuverlässig

#### Schritt 1: Router konfigurieren
1. **Router Web-Interface öffnen:** `http://192.168.1.1` (Standard IP)
2. **Einloggen:** admin/admin01 (Standard)
3. **Services → Webhooks** aktivieren
4. **HTTP POST URL einstellen:**
   ```
   https://sunnybill-test.test/api/router-webhook/[IHR_WEBHOOK_TOKEN]
   ```

#### Schritt 2: Webhook-Script erstellen
**Datei:** `/routes/api.php`
```php
// Router Webhook Endpoint
Route::post('/router-webhook/{token}', function (Request $request, $token) {
    $router = Router::where('webhook_token', $token)->first();
    
    if (!$router) {
        return response()->json(['error' => 'Invalid token'], 401);
    }
    
    // Teltonika RUTX50 Datenformat
    $data = [
        'operator' => $request->input('operator') ?? 'Unbekannt',
        'signal_strength' => $request->input('signal_rssi') ?? $request->input('signal_strength'),
        'network_type' => $request->input('connection_type') ?? $request->input('network_type'),
        'connection_state' => $request->input('connection_state', 'connected'),
    ];
    
    $router->updateFromWebhook($data);
    
    return response()->json(['status' => 'success', 'message' => 'Data received']);
});
```

#### Schritt 3: Router-Konfiguration
Im Router Web-Interface unter **Services → Data to Server:**
```json
{
  "operator": "%operator%",
  "signal_rssi": "%signal_rssi%",
  "connection_type": "%connection_type%",
  "connection_state": "%connection_state%",
  "timestamp": "%timestamp%"
}
```

### Option 2: HTTP API Polling (Zuverlässig) 🔄
**Vorteile:** Funktioniert immer, auch bei Netzwerkproblemen

#### Schritt 1: Service erstellen
```bash
php artisan make:service TeltonikaApiService
```

#### Schritt 2: API Service implementieren
**Datei:** `app/Services/TeltonikaApiService.php`
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeltonikaApiService
{
    public function getRouterData(string $ip, string $username = 'admin', string $password = 'admin01'): ?array
    {
        try {
            // Teltonika HTTP API
            $response = Http::withBasicAuth($username, $password)
                ->get("http://{$ip}/cgi-bin/luci/admin/status/overview/mobile_info");
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'operator' => $data['operator'] ?? null,
                    'signal_strength' => $data['signal_strength'] ?? null,
                    'network_type' => $data['connection_type'] ?? null,
                    'connection_state' => $data['connection_state'] ?? 'unknown',
                ];
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error("Teltonika API Error for {$ip}: " . $e->getMessage());
            return null;
        }
    }
}
```

#### Schritt 3: Job für periodisches Polling
```bash
php artisan make:job PollTeltonikaRoutersJob
```

```php
<?php

namespace App\Jobs;

use App\Models\Router;
use App\Services\TeltonikaApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollTeltonikaRoutersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $teltonikaService = new TeltonikaApiService();
        
        Router::where('model', 'RUTX50')
            ->where('is_active', true)
            ->whereNotNull('ip_address')
            ->each(function ($router) use ($teltonikaService) {
                $data = $teltonikaService->getRouterData($router->ip_address);
                
                if ($data) {
                    $router->updateFromWebhook($data);
                }
            });
    }
}
```

#### Schritt 4: Scheduler einrichten
**Datei:** `app/Console/Kernel.php`
```php
protected function schedule(Schedule $schedule)
{
    $schedule->job(new PollTeltonikaRoutersJob)->everyMinute();
}
```

### Option 3: Teltonika RMS (Cloud-basiert) ☁️
**Vorteile:** Professionell, viele Features

1. **RMS Account erstellen:** https://rms.teltonika-networks.com
2. **Router zu RMS hinzufügen**
3. **RMS API Token generieren**
4. **RMS API Integration entwickeln**

## 🚀 Schnellstart - Webhook Test

### 1. Ihren Webhook-Token finden
Gehen Sie zu: https://sunnybill-test.test/admin/routers/1/edit
Der `webhook_token` wird angezeigt.

### 2. Test-Webhook senden
```bash
curl -X POST -H "Content-Type: application/json" \
  -d '{
    "operator": "Telekom.de",
    "signal_rssi": -65,
    "connection_type": "4G",
    "connection_state": "connected"
  }' \
  https://sunnybill-test.test/api/router-webhook/[IHR_WEBHOOK_TOKEN]
```

### 3. Ergebnis prüfen
- Router-Liste neu laden
- Status sollte "Online" anzeigen
- Signalstärke: -65 dBm (5 Balken)
- Provider: Telekom.de

## 📊 Was Sie dann sehen werden:

### In der Router-Übersicht:
- 🟢 **Online** (Status-Badge grün)
- 📶 **-65 dBm ▰▰▰▰▰** (Signalstärke mit Balken)
- 📱 **Telekom.de** (Provider)
- 🌐 **4G** (Netzwerk-Typ)
- ⏱️ **vor 2 Minuten** (Letzte Verbindung)

### Automatische Status-Berechnung:
- **Online:** Daten innerhalb der letzten 3 Minuten
- **Verzögert:** Daten 3-10 Minuten alt
- **Offline:** Keine Daten seit über 10 Minuten

## 🔧 Nächste Schritte:

1. **Webhook-Endpoint implementieren** (5 Minuten)
2. **Router-IP konfigurieren** in https://sunnybill-test.test/admin/routers/1/edit
3. **Teltonika Router konfigurieren** für HTTP Posts
4. **Testen** mit curl-Kommando
5. **Live-Daten genießen!** 🎉

## 📞 Bei Problemen:

- **Router nicht erreichbar?** → IP-Adresse prüfen
- **Webhook funktioniert nicht?** → Token prüfen  
- **Keine Daten?** → Router-Konfiguration prüfen
- **API-Fehler?** → Logs in `storage/logs/laravel.log` prüfen
