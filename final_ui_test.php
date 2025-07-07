<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Facades\Hash;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FINALER UI-TEST: Was Sie in der Benutzeroberfläche sehen sollten ===\n\n";

// 1. Test: Neuen Benutzer über Filament erstellen (wie Sie es in der UI machen würden)
echo "1. FILAMENT CREATE USER TEST:\n";
echo "   Simuliert: Admin geht zu 'Benutzer erstellen' und lässt Passwort leer\n\n";

// Simuliere exakt den Filament CreateUser Prozess
$createUserPage = new \App\Filament\Resources\UserResource\Pages\CreateUser();

// Simuliere Form-Daten wie sie von Filament kommen würden
$formData = [
    'name' => 'UI Test User',
    'email' => 'uitest@example.com',
    'password' => '', // Leer = automatisches Passwort
    'role' => 'user',
    'is_active' => true,
];

// Simuliere mutateFormDataBeforeCreate
$reflection = new ReflectionClass($createUserPage);
$method = $reflection->getMethod('mutateFormDataBeforeCreate');
$method->setAccessible(true);

$mutatedData = $method->invoke($createUserPage, $formData);

echo "   Nach mutateFormDataBeforeCreate():\n";
echo "   - Temporäres Passwort generiert: '{$mutatedData['temporary_password']}'\n";
echo "   - Password für Hashing: '{$mutatedData['password']}'\n\n";

// Simuliere Filament create() - hasht password automatisch
$mutatedData['password'] = Hash::make($mutatedData['password']);

// Erstelle Benutzer
$user = User::create($mutatedData);
echo "   Benutzer erstellt (ID: {$user->id})\n";

// Simuliere afterCreate() Hook
$createUserPage->record = $user;
$temporaryPassword = $mutatedData['temporary_password'] ?? null;

// Setze temporaryPassword Property für afterCreate
$reflection = new ReflectionClass($createUserPage);
$property = $reflection->getProperty('temporaryPassword');
$property->setAccessible(true);
$property->setValue($createUserPage, $temporaryPassword);

// Führe afterCreate aus
$afterCreateMethod = $reflection->getMethod('afterCreate');
$afterCreateMethod->setAccessible(true);
$afterCreateMethod->invoke($createUserPage);

// Lade Benutzer neu
$user->refresh();

echo "   Nach afterCreate() Hook:\n";
echo "   - Temporäres Passwort in DB: '{$user->temporary_password}'\n";
echo "   - Ist gehashed: " . (str_starts_with($user->temporary_password, '$2y$') ? 'JA - PROBLEM!' : 'NEIN - OK') . "\n\n";

// 2. Test: E-Mail-Inhalt
echo "2. E-MAIL INHALT TEST:\n";
$notification = new CustomVerifyEmail($user->getTemporaryPasswordForEmail());
$mailMessage = $notification->toMail($user);

echo "   E-Mail-Betreff: {$mailMessage->subject}\n";
echo "   E-Mail-Inhalt:\n";
foreach ($mailMessage->introLines as $index => $line) {
    echo "     {$line}\n";
}

// 3. Test: Admin-Aktionen
echo "\n3. ADMIN-AKTIONEN TEST:\n";
echo "   Simuliert: Admin klickt 'Zufälliges Passwort generieren'\n";

$newTempPassword = User::generateRandomPassword(12);
$user->update([
    'password' => Hash::make($newTempPassword),
    'temporary_password' => $newTempPassword,
    'password_change_required' => true,
    'password_changed_at' => now(),
]);

$user->refresh();
echo "   Nach 'Zufälliges Passwort generieren':\n";
echo "   - Neues temporäres Passwort: '{$user->temporary_password}'\n";
echo "   - Ist gehashed: " . (str_starts_with($user->temporary_password, '$2y$') ? 'JA - PROBLEM!' : 'NEIN - OK') . "\n\n";

// 4. Test: Direkte Datenbank-Abfrage (was tatsächlich gespeichert ist)
echo "4. DATENBANK-REALITÄT:\n";
$dbUser = \DB::table('users')->where('id', $user->id)->first();
echo "   Direkt aus Datenbank: '{$dbUser->temporary_password}'\n";
echo "   Ist gehashed: " . (str_starts_with($dbUser->temporary_password, '$2y$') ? 'JA - PROBLEM!' : 'NEIN - OK') . "\n\n";

// Cleanup
$user->delete();

echo "=== FAZIT ===\n";
echo "Wenn alle Tests 'NEIN - OK' zeigen, funktioniert das System korrekt.\n";
echo "Falls Sie immer noch Probleme sehen:\n";
echo "1. Leeren Sie den Browser-Cache\n";
echo "2. Stellen Sie sicher, dass Sie die neueste Version verwenden\n";
echo "3. Testen Sie mit einem komplett neuen Benutzer\n";
echo "\nTest abgeschlossen.\n";
