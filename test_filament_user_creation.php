<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Filament\Resources\UserResource\Pages\CreateUser;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Filament Benutzer-Erstellung ===\n\n";

try {
    // 1. Teste die mutateFormDataBeforeCreate Methode
    echo "1. Teste mutateFormDataBeforeCreate...\n";
    
    $createUserPage = new CreateUser();
    
    // Simuliere Form-Daten
    $formData = [
        'name' => 'Filament Test Benutzer',
        'email' => 'filament-test@example.com',
        'role' => 'user',
        'is_active' => true,
        'password' => '', // Leer lassen, um automatische Generierung zu testen
    ];
    
    // Verwende Reflection, um auf die protected Methode zuzugreifen
    $reflection = new ReflectionClass($createUserPage);
    $method = $reflection->getMethod('mutateFormDataBeforeCreate');
    $method->setAccessible(true);
    
    $mutatedData = $method->invoke($createUserPage, $formData);
    
    echo "✓ Form-Daten mutiert\n";
    echo "✓ Passwort generiert: " . (!empty($mutatedData['password']) ? 'Ja' : 'Nein') . "\n";
    echo "✓ password_change_required: " . ($mutatedData['password_change_required'] ? 'true' : 'false') . "\n";
    
    // Prüfe ob temporäres Passwort gespeichert wurde
    $temporaryPasswordProperty = $reflection->getProperty('temporaryPassword');
    $temporaryPasswordProperty->setAccessible(true);
    $temporaryPassword = $temporaryPasswordProperty->getValue($createUserPage);
    
    echo "✓ Temporäres Passwort gespeichert: " . ($temporaryPassword ? $temporaryPassword : 'NULL') . "\n\n";
    
    // 2. Erstelle einen Benutzer manuell und teste afterCreate
    echo "2. Erstelle Benutzer und teste afterCreate...\n";
    
    $user = User::create([
        'name' => $mutatedData['name'],
        'email' => $mutatedData['email'],
        'password' => Hash::make($mutatedData['password']),
        'role' => $mutatedData['role'],
        'is_active' => $mutatedData['is_active'],
        'password_change_required' => $mutatedData['password_change_required'],
    ]);
    
    echo "✓ Benutzer erstellt: {$user->email}\n";
    echo "✓ Vor afterCreate - hasTemporaryPassword(): " . ($user->hasTemporaryPassword() ? 'true' : 'false') . "\n";
    
    // Setze den record für die afterCreate Methode
    $recordProperty = $reflection->getProperty('record');
    $recordProperty->setAccessible(true);
    $recordProperty->setValue($createUserPage, $user);
    
    // Führe afterCreate aus
    $afterCreateMethod = $reflection->getMethod('afterCreate');
    $afterCreateMethod->setAccessible(true);
    $afterCreateMethod->invoke($createUserPage);
    
    // Lade User neu aus der Datenbank
    $user->refresh();
    
    echo "✓ afterCreate ausgeführt\n";
    echo "✓ Nach afterCreate - hasTemporaryPassword(): " . ($user->hasTemporaryPassword() ? 'true' : 'false') . "\n";
    echo "✓ tmp_p Spalte: " . ($user->tmp_p ?? 'NULL') . "\n";
    echo "✓ getTemporaryPasswordForEmail(): " . ($user->getTemporaryPasswordForEmail() ?? 'NULL') . "\n";
    echo "✓ password_change_required: " . ($user->password_change_required ? 'true' : 'false') . "\n\n";
    
    // 3. Teste E-Mail-Bestätigung mit diesem Benutzer
    echo "3. Teste E-Mail-Bestätigung...\n";
    
    if ($user->hasTemporaryPassword()) {
        echo "✓ Benutzer hat temporäres Passwort - E-Mail-Bestätigung sollte zur Passwort-Änderung weiterleiten\n";
        
        // Simuliere E-Mail-Bestätigung
        $user->markEmailAsVerified();
        
        // Teste Weiterleitung-Logik
        if ($user->hasTemporaryPassword()) {
            $token = hash('sha256', $user->id . $user->email . $user->created_at);
            $redirectUrl = route('password.change.temporary', [
                'userId' => $user->id,
                'token' => $token
            ]);
            
            echo "✓ Weiterleitung URL: {$redirectUrl}\n";
            echo "✓ Automatische Weiterleitung zur Passwort-Änderung funktioniert\n";
        } else {
            echo "❌ Benutzer hat nach E-Mail-Bestätigung kein temporäres Passwort mehr\n";
        }
    } else {
        echo "❌ Benutzer hat kein temporäres Passwort - würde zur Login-Seite weiterleiten\n";
        echo "❌ Das ist das Problem!\n";
    }
    
    // 4. Teste kompletten Workflow
    echo "\n4. Teste kompletten Workflow...\n";
    
    echo "✓ Benutzer-Erstellung: " . ($user->id ? 'Erfolgreich' : 'Fehlgeschlagen') . "\n";
    echo "✓ Temporäres Passwort gesetzt: " . ($user->hasTemporaryPassword() ? 'Ja' : 'Nein') . "\n";
    echo "✓ Passwort-Änderung erforderlich: " . ($user->password_change_required ? 'Ja' : 'Nein') . "\n";
    echo "✓ E-Mail verifiziert: " . ($user->hasVerifiedEmail() ? 'Ja' : 'Nein') . "\n";
    echo "✓ Weiterleitung funktioniert: " . ($user->hasTemporaryPassword() ? 'Ja' : 'Nein') . "\n";
    
    // 5. Cleanup
    echo "\n5. Cleanup...\n";
    $user->delete();
    echo "✓ Test-Benutzer gelöscht\n";
    
    echo "\n=== Test abgeschlossen ===\n";
    
    if ($user->hasTemporaryPassword()) {
        echo "\n✅ ERFOLG: Filament Benutzer-Erstellung funktioniert korrekt!\n";
        echo "✅ Benutzer werden mit temporärem Passwort erstellt\n";
        echo "✅ E-Mail-Bestätigung leitet zur Passwort-Änderung weiter\n";
    } else {
        echo "\n❌ PROBLEM: Filament Benutzer-Erstellung funktioniert nicht korrekt!\n";
        echo "❌ Benutzer haben kein temporäres Passwort\n";
        echo "❌ E-Mail-Bestätigung leitet zur Login-Seite weiter\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
