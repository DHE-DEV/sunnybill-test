<?php

// Direkte Datenbankverbindung
$host = 'localhost';
$dbname = 'sunnybill';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Erstelle neuen Live Test Token direkt in DB ===\n\n";
    
    // Hole Admin User ID
    $userSql = "SELECT id, name FROM users WHERE email = 'admin@example.com' LIMIT 1";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ Administrator nicht gefunden!\n";
        exit;
    }
    
    // Generiere neuen Token
    $plainToken = 'sb_' . bin2hex(random_bytes(32)); // 64 Zeichen Random String
    $hashedToken = hash('sha256', $plainToken);
    $tokenId = uniqid('token_', true);
    
    // Abilities Array als JSON
    $abilities = json_encode([
        'customers:read',
        'customers:create', 
        'customers:update',
        'customers:delete',
        'phone-numbers:read',
        'phone-numbers:create',
        'phone-numbers:update',
        'phone-numbers:delete'
    ]);
    
    // Token in DB erstellen
    $tokenSql = "INSERT INTO app_tokens (
        id, user_id, name, token, abilities, expires_at, is_active, 
        created_by_ip, app_type, notes, created_at, updated_at
    ) VALUES (
        :id, :user_id, :name, :token, :abilities, :expires_at, :is_active,
        :created_by_ip, :app_type, :notes, :created_at, :updated_at
    )";
    
    $tokenStmt = $pdo->prepare($tokenSql);
    $tokenStmt->execute([
        'id' => $tokenId,
        'user_id' => $user['id'],
        'name' => 'Live API Test Token',
        'token' => $hashedToken,
        'abilities' => $abilities,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
        'is_active' => 1,
        'created_by_ip' => '127.0.0.1',
        'app_type' => 'web_app',
        'notes' => 'Test Token für Live API Customer Daten',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    echo "✅ Neuer Token erstellt:\n";
    echo "Token ID: $tokenId\n";
    echo "Plain Token (für API): $plainToken\n";
    echo "Hash (in DB): $hashedToken\n";
    echo "User: {$user['name']}\n";
    echo "Abilities: customers:read, customers:create, customers:update, customers:delete, phone-numbers:*\n";
    echo "Expires: " . date('Y-m-d H:i:s', strtotime('+1 year')) . "\n\n";
    
    echo "🔑 Verwende diesen Token in der API:\n";
    echo "Authorization: Bearer $plainToken\n";
    
} catch (PDOException $e) {
    echo "❌ Datenbankfehler: " . $e->getMessage() . "\n";
}
