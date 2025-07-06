<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "Überprüfe Benutzer-Daten...\n\n";

$users = User::all();

foreach ($users as $user) {
    echo "ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "E-Mail: {$user->email}\n";
    echo "Rolle: " . ($user->role ?? 'nicht gesetzt') . "\n";
    echo "Aktiv: " . ($user->is_active ? 'Ja' : 'Nein') . "\n";
    echo "E-Mail verifiziert: " . ($user->email_verified_at ? 'Ja' : 'Nein') . "\n";
    echo "Letzte Anmeldung: " . ($user->last_login_at ? $user->last_login_at->format('d.m.Y H:i') : 'Nie') . "\n";
    echo "Erstellt: " . $user->created_at->format('d.m.Y H:i') . "\n";
    
    // Test password
    $passwordWorks = Hash::check('password', $user->password);
    echo "Passwort 'password' funktioniert: " . ($passwordWorks ? 'Ja' : 'Nein') . "\n";
    
    echo "---\n\n";
}

echo "Gesamt Benutzer: " . $users->count() . "\n";
echo "Aktive Benutzer: " . User::where('is_active', true)->count() . "\n";
echo "Administratoren: " . User::where('role', 'admin')->count() . "\n";