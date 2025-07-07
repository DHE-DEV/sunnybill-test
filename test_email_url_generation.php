<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: E-Mail URL Generierung ===\n\n";

try {
    // Erstelle einen Test-Benutzer
    $user = User::create([
        'name' => 'URL Test Benutzer',
        'email' => 'url-test@example.com',
        'password' => Hash::make('dummy'),
        'role' => 'user',
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    
    $temporaryPassword = User::generateRandomPassword();
    $user->setTemporaryPassword($temporaryPassword);
    
    echo "✓ Test-Benutzer erstellt: {$user->email}\n";
    echo "✓ Temporäres Passwort: {$temporaryPassword}\n\n";
    
    // Teste URL-Generierung wie in der E-Mail
    echo "1. URL-Generierung testen...\n";
    
    $token = hash('sha256', $user->id . $user->email . $user->created_at);
    echo "✓ Token: {$token}\n";
    
    // Teste verschiedene URL-Generierungsmethoden
    $url1 = url('/password/change/' . $user->id . '/' . $token);
    echo "✓ url() Methode: {$url1}\n";
    
    $url2 = config('app.url') . '/password/change/' . $user->id . '/' . $token;
    echo "✓ config('app.url') Methode: {$url2}\n";
    
    $url3 = route('password.change.temporary', ['userId' => $user->id, 'token' => $token]);
    echo "✓ route() Methode: {$url3}\n\n";
    
    // Teste die AccountActivatedNotification
    echo "2. AccountActivatedNotification testen...\n";
    
    $notification = new \App\Notifications\AccountActivatedNotification($temporaryPassword);
    $mailMessage = $notification->toMail($user);
    
    echo "✓ Notification erstellt\n";
    echo "✓ Mail Message erstellt\n";
    
    // Extrahiere die Action URL aus der Mail Message
    $reflection = new ReflectionClass($mailMessage);
    $actionUrlProperty = $reflection->getProperty('actionUrl');
    $actionUrlProperty->setAccessible(true);
    $actionUrl = $actionUrlProperty->getValue($mailMessage);
    
    echo "✓ Action URL aus E-Mail: {$actionUrl}\n\n";
    
    // Teste ob die Route existiert
    echo "3. Route-Existenz testen...\n";
    
    try {
        $routeExists = \Illuminate\Support\Facades\Route::has('password.change.temporary');
        echo "✓ Route 'password.change.temporary' existiert: " . ($routeExists ? 'Ja' : 'Nein') . "\n";
        
        if ($routeExists) {
            $routeUrl = route('password.change.temporary', ['userId' => $user->id, 'token' => $token]);
            echo "✓ Route URL: {$routeUrl}\n";
        }
    } catch (Exception $e) {
        echo "❌ Fehler beim Route-Test: " . $e->getMessage() . "\n";
    }
    
    // Teste alle verfügbaren Routen
    echo "\n4. Alle Passwort-Routen auflisten...\n";
    
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name && str_contains($name, 'password')) {
            echo "✓ Route: {$name} -> " . $route->uri() . "\n";
        }
    }
    
    // Cleanup
    echo "\n5. Cleanup...\n";
    $user->delete();
    echo "✓ Test-Benutzer gelöscht\n";
    
    echo "\n=== Test abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
