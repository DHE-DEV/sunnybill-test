<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔐 Filament Admin Login-Informationen\n";
echo "=====================================\n\n";

// Alle Users abrufen
$users = User::orderBy('created_at')->get();

if ($users->isEmpty()) {
    echo "❌ Keine Benutzer in der Datenbank gefunden.\n";
    echo "Führe zuerst 'php create_test_data_for_phone_numbers.php' aus.\n";
    exit(1);
}

echo "📋 Verfügbare Benutzer:\n\n";

foreach ($users as $index => $user) {
    echo ($index + 1) . ". **{$user->name}**\n";
    echo "   📧 Email: {$user->email}\n";
    echo "   🆔 ID: {$user->id}\n";
    echo "   📅 Erstellt: {$user->created_at->format('d.m.Y H:i')}\n";
    
    // Standard-Passwort prüfen (aus create_test_data_for_phone_numbers.php)
    if (Hash::check('password', $user->password)) {
        echo "   🔑 Passwort: password\n";
    } else {
        echo "   🔑 Passwort: (unbekannt - nicht 'password')\n";
    }
    
    echo "\n";
}

echo "🌐 Filament Admin Login:\n";
echo "========================\n";
echo "URL: http://localhost/admin\n";
echo "Empfohlener Admin-Account:\n";

$adminUser = $users->where('email', 'admin@sunnybill.de')->first();
if ($adminUser) {
    echo "📧 Email: admin@sunnybill.de\n";
    echo "🔑 Passwort: password\n";
} else {
    $firstUser = $users->first();
    echo "📧 Email: {$firstUser->email}\n";
    echo "🔑 Passwort: password\n";
}

echo "\n💡 Alle Test-Benutzer verwenden das Passwort 'password'\n";
