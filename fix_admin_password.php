<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Hash;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Aktualisiere Administrator-Passwort...\n\n";

try {
    // Finde den Administrator-Benutzer
    $admin = \App\Models\User::where('email', 'admin@example.com')->first();
    
    if (!$admin) {
        echo "❌ Administrator-Benutzer nicht gefunden!\n";
        exit(1);
    }
    
    echo "Gefundener Benutzer:\n";
    echo "ID: {$admin->id}\n";
    echo "Name: {$admin->name}\n";
    echo "E-Mail: {$admin->email}\n";
    echo "Aktuelle Rolle: {$admin->role}\n\n";
    
    // Neues Passwort setzen
    $newPassword = 'password';
    $admin->password = Hash::make($newPassword);
    $admin->save();
    
    echo "✅ Passwort erfolgreich aktualisiert!\n";
    echo "Neue Anmeldedaten:\n";
    echo "E-Mail: admin@example.com\n";
    echo "Passwort: password\n\n";
    
    // Verifikation
    if (Hash::check($newPassword, $admin->password)) {
        echo "✅ Passwort-Verifikation erfolgreich!\n";
    } else {
        echo "❌ Passwort-Verifikation fehlgeschlagen!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nFertig!\n";