<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Router;

echo "=== Router Restart Test ===\n\n";

$router = Router::first();

if (!$router) {
    echo "Kein Router in der Datenbank gefunden.\n";
    exit(1);
}

echo "Router gefunden:\n";
echo "ID: " . $router->id . "\n";
echo "Name: " . $router->name . "\n";
echo "IP-Adresse: " . ($router->ip_address ?? 'Nicht konfiguriert') . "\n";
echo "Letzter Neustart: " . ($router->last_restart_formatted ?? 'Nie') . "\n";
echo "Kann neu gestartet werden: " . (!$router->hasRecentRestart() && $router->ip_address ? 'Ja' : 'Nein') . "\n\n";

if (!$router->ip_address) {
    echo "Setze Test-IP-Adresse...\n";
    $router->update(['ip_address' => '192.168.1.1']);
    echo "IP-Adresse auf 192.168.1.1 gesetzt.\n\n";
}

echo "Teste Neustart-Funktionalität...\n";
$success = $router->restart();

echo "Neustart " . ($success ? 'erfolgreich' : 'fehlgeschlagen') . "\n";
echo "Letzter Neustart: " . ($router->fresh()->last_restart_formatted ?? 'Nie') . "\n";

echo "\n=== Test abgeschlossen ===\n";