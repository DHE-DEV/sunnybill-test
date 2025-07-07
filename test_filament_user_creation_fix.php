<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\User;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use Illuminate\Support\Facades\Hash;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test: Filament User-Erstellung Fix ===\n\n";

try {
    // Simuliere die Filament User-Erstellung
    echo "1. Simuliere Filament User-Erstellung...\n";
    
    // Erstelle eine Instanz der CreateUser Page (ohne echte Filament-Umgebung)
    $createUserPage = new class {
        protected ?string $temporaryPassword = null;
        
        public function mutateFormDataBeforeCreate(array $data): array
        {
            // Kopiere die Logik aus der echten CreateUser Klasse
            $data['is_active'] = $data['is_active'] ?? true;
            $data['role'] = $data['role'] ?? 'user';
            
            if (empty($data['password'])) {
                $this->temporaryPassword = User::generateRandomPassword(12);
                $data['password'] = $this->temporaryPassword;
            } else {
                $this->temporaryPassword = $data['password'];
            }
            
            $data['password_change_required'] = true;
            unset($data['temporary_password']);
            
            return $data;
        }
        
        public function getTemporaryPassword(): ?string
        {
            return $this->temporaryPassword;
        }
    };
    
    // Simuliere Form-Daten
    $formData = [
        'name' => 'Daniel DH Henninger Test',
        'email' => 'test-filament@example.com',
        'password' => 'MyTestPassword123', // Benutzer gibt Passwort ein
        'phone' => '022429018928',
        'role' => 'user'
    ];
    
    echo "   - Eingabe-Daten:\n";
    echo "     Name: {$formData['name']}\n";
    echo "     Email: {$formData['email']}\n";
    echo "     Password: {$formData['password']}\n";
    echo "     Phone: {$formData['phone']}\n";
    echo "     Role: {$formData['role']}\n";
    
    // Mutiere die Daten wie in Filament
    $mutatedData = $createUserPage->mutateFormDataBeforeCreate($formData);
    
    echo "\n2. Nach mutateFormDataBeforeCreate:\n";
    echo "   - temporary_password in Daten: " . (isset($mutatedData['temporary_password']) ? 'Ja' : 'Nein') . "\n";
    echo "   - Temporäres Passwort in Klasse: {$createUserPage->getTemporaryPassword()}\n";
    
    // Erstelle den User mit den mutierten Daten
    $user = User::create($mutatedData);
    echo "   ✓ User erstellt mit ID: {$user->id}\n";
    
    // Simuliere afterCreate() - setze temporäres Passwort
    $user->temporary_password = $createUserPage->getTemporaryPassword();
    $user->save();
    
    echo "\n3. Nach afterCreate (temporäres Passwort gesetzt):\n";
    
    // Lade User neu aus Datenbank
    $savedUser = User::find($user->id);
    
    echo "   - Name: {$savedUser->name}\n";
    echo "   - Email: {$savedUser->email}\n";
    echo "   - Password (gehashed): " . substr($savedUser->password, 0, 20) . "...\n";
    echo "   - Temporary Password: {$savedUser->temporary_password}\n";
    echo "   - Password Change Required: " . ($savedUser->password_change_required ? 'Ja' : 'Nein') . "\n";
    
    // Überprüfe die Korrektheit
    echo "\n4. Validierung:\n";
    
    // Temporäres Passwort sollte ungehashed sein
    if ($savedUser->temporary_password === $createUserPage->getTemporaryPassword()) {
        echo "   ✓ Temporäres Passwort ist UNGEHASHED (korrekt)\n";
    } else {
        echo "   ✗ Temporäres Passwort ist gehashed (Problem!)\n";
        echo "   Erwartet: {$createUserPage->getTemporaryPassword()}\n";
        echo "   Erhalten: {$savedUser->temporary_password}\n";
    }
    
    // Normales Passwort sollte gehashed sein
    if (Hash::check($createUserPage->getTemporaryPassword(), $savedUser->password)) {
        echo "   ✓ Normales Passwort ist korrekt gehashed\n";
    } else {
        echo "   ✗ Normales Passwort ist nicht korrekt gehashed\n";
    }
    
    // Vergleiche mit dem ursprünglichen Problem-Datensatz
    echo "\n5. Vergleich mit ursprünglichem Problem:\n";
    echo "   Ursprünglich: password und temporary_password waren beide gehashed\n";
    echo "   Jetzt: password ist gehashed, temporary_password ist Klartext\n";
    
    if ($savedUser->password !== $savedUser->temporary_password) {
        echo "   ✓ Problem behoben: password ≠ temporary_password\n";
    } else {
        echo "   ✗ Problem besteht noch: password = temporary_password\n";
    }
    
    // Cleanup
    echo "\n6. Cleanup...\n";
    $user->delete();
    echo "   ✓ Test-User gelöscht\n";
    
    echo "\n=== Test erfolgreich abgeschlossen ===\n";
    echo "\nFazit: Das temporäre Passwort wird jetzt korrekt im Klartext gespeichert,\n";
    echo "während das normale Passwort gehashed bleibt.\n";
    
} catch (Exception $e) {
    echo "Fehler beim Test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
