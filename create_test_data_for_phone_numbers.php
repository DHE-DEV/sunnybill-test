<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\PhoneNumber;
use App\Models\AppToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Laravel Bootstrap
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🚀 Erstelle Testdaten für Phone Numbers API...\n\n";

// 1. Admin User erstellen/updaten
echo "👤 Admin User erstellen...\n";
$admin = User::updateOrCreate(
    ['email' => 'admin@sunnybill.de'],
    [
        'name' => 'Administrator',
        'password' => Hash::make('password'),
        'email_verified_at' => now(),
    ]
);
echo "✅ Admin User erstellt: ID {$admin->id}\n";

// 2. Test Users erstellen
echo "\n👥 Test Users erstellen...\n";
$testUsers = [
    [
        'name' => 'Max Mustermann',
        'email' => 'max@test.de',
        'password' => Hash::make('password'),
    ],
    [
        'name' => 'Anna Schmidt', 
        'email' => 'anna@test.de',
        'password' => Hash::make('password'),
    ],
    [
        'name' => 'Thomas Weber',
        'email' => 'thomas@test.de', 
        'password' => Hash::make('password'),
    ],
];

$users = [];
foreach ($testUsers as $userData) {
    $user = User::updateOrCreate(
        ['email' => $userData['email']],
        $userData
    );
    $users[] = $user;
    echo "✅ User erstellt: {$user->name} (ID: {$user->id})\n";
}

// 3. Telefonnummern für Admin User erstellen
echo "\n📞 Telefonnummern für Admin erstellen...\n";
$adminPhones = [
    [
        'phone_number' => '+49 30 12345678',
        'type' => 'business',
        'label' => 'Büro Berlin',
        'is_primary' => true,
        'sort_order' => 1,
    ],
    [
        'phone_number' => '+49 175 9876543',
        'type' => 'mobile', 
        'label' => 'Handy Geschäft',
        'is_favorite' => true,
        'sort_order' => 2,
    ],
    [
        'phone_number' => '+49 40 55556666',
        'type' => 'business',
        'label' => 'Büro Hamburg',
        'sort_order' => 3,
    ],
];

foreach ($adminPhones as $phoneData) {
    $phone = PhoneNumber::create(array_merge($phoneData, [
        'phoneable_id' => $admin->id,
        'phoneable_type' => User::class,
        'id' => Str::uuid(),
    ]));
    echo "✅ Telefonnummer erstellt: {$phone->phone_number} ({$phone->type})\n";
}

// 4. Telefonnummern für Test Users
echo "\n📱 Telefonnummern für Test Users...\n";
$phoneTypes = ['business', 'private', 'mobile'];
$phoneNumbers = [
    '+49 711 1234567',
    '+49 89 9876543', 
    '+49 221 5555666',
    '+49 172 1112233',
    '+49 160 4445566',
    '+49 177 7778899',
];

foreach ($users as $index => $user) {
    // Jeder User bekommt 2-3 Telefonnummern
    $numPhones = rand(2, 3);
    for ($i = 0; $i < $numPhones; $i++) {
        $phone = PhoneNumber::create([
            'id' => Str::uuid(),
            'phoneable_id' => $user->id,
            'phoneable_type' => User::class,
            'phone_number' => $phoneNumbers[($index * 3) + $i] ?? '+49 30 ' . rand(1000000, 9999999),
            'type' => $phoneTypes[$i % 3],
            'label' => $i == 0 ? 'Hauptnummer' : ($i == 1 ? 'Privat' : 'Mobil'),
            'is_primary' => $i == 0,
            'is_favorite' => rand(0, 1) == 1,
            'sort_order' => $i + 1,
        ]);
        echo "✅ {$user->name}: {$phone->phone_number} ({$phone->type})\n";
    }
}

// 5. API Token erstellen
echo "\n🔑 API Token erstellen...\n";
$token = AppToken::create([
    'id' => Str::uuid(),
    'token' => 'sb_' . Str::random(60),
    'name' => 'Phone Numbers API Test Token',
    'user_id' => (string) $admin->id, // Cast to string for UUID compatibility
    'abilities' => [
        'phone-numbers:read',
        'phone-numbers:create', 
        'phone-numbers:update',
        'phone-numbers:delete',
        'users:read',
        'tasks:read',
        'tasks:create',
        'tasks:update',
        'tasks:delete',
    ],
    'last_used_at' => null,
    'expires_at' => null,
]);

echo "✅ API Token erstellt: {$token->token}\n";
echo "🔗 Für API-Tests verwenden: Authorization: Bearer {$token->token}\n";

// 6. Statistiken anzeigen
echo "\n📊 Erstellte Testdaten:\n";
echo "├── Users: " . User::count() . "\n";
echo "├── Telefonnummern: " . PhoneNumber::count() . "\n";
echo "└── API Tokens: " . AppToken::count() . "\n";

// 7. Test URLs anzeigen  
echo "\n🚀 Test URLs für die Phone Numbers API:\n";
echo "Base URL: http://localhost/api/app\n";
echo "Bearer Token: {$token->token}\n\n";

echo "📱 User-spezifische API (Empfohlen):\n";
echo "GET /users/{$admin->id}/phone-numbers - Alle Telefonnummern des Admins\n";
echo "POST /users/{$admin->id}/phone-numbers - Neue Telefonnummer hinzufügen\n";
echo "PUT /users/{$admin->id}/phone-numbers/{phone-id} - Telefonnummer bearbeiten\n";
echo "DELETE /users/{$admin->id}/phone-numbers/{phone-id} - Telefonnummer löschen\n\n";

echo "🛠️ Allgemeine API:\n";
echo "GET /phone-numbers - Alle Telefonnummern\n";
echo "POST /phone-numbers - Neue Telefonnummer (mit phoneable_id & phoneable_type)\n\n";

// 8. Beispiel cURL Commands
$firstPhone = PhoneNumber::where('phoneable_id', $admin->id)->first();
if ($firstPhone) {
    echo "💡 Beispiel cURL Commands:\n\n";
    
    echo "# Alle Admin-Telefonnummern abrufen:\n";
    echo "curl -X GET 'http://localhost/api/app/users/{$admin->id}/phone-numbers' \\\n";
    echo "  -H 'Authorization: Bearer {$token->token}' \\\n";
    echo "  -H 'Accept: application/json'\n\n";
    
    echo "# Neue Telefonnummer hinzufügen:\n";
    echo "curl -X POST 'http://localhost/api/app/users/{$admin->id}/phone-numbers' \\\n";
    echo "  -H 'Authorization: Bearer {$token->token}' \\\n";
    echo "  -H 'Accept: application/json' \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -d '{\n";
    echo "    \"phone_number\": \"+49 30 99887766\",\n";
    echo "    \"type\": \"mobile\",\n";
    echo "    \"label\": \"Test Handy\",\n";
    echo "    \"is_favorite\": true\n";
    echo "  }'\n\n";
    
    echo "# Telefonnummer bearbeiten:\n";
    echo "curl -X PUT 'http://localhost/api/app/users/{$admin->id}/phone-numbers/{$firstPhone->id}' \\\n";
    echo "  -H 'Authorization: Bearer {$token->token}' \\\n";
    echo "  -H 'Accept: application/json' \\\n";
    echo "  -H 'Content-Type: application/json' \\\n";
    echo "  -d '{\n";
    echo "    \"label\": \"Aktualisiertes Label\",\n";
    echo "    \"is_favorite\": false\n";
    echo "  }'\n\n";
}

echo "🎉 Alle Testdaten erfolgreich erstellt!\n";
echo "📖 Vollständige Dokumentation: PHONE_NUMBERS_API_DOCUMENTATION.md\n";
