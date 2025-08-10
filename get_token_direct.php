<?php

// Direkte Datenbankverbindung ohne Laravel Boot
$host = 'localhost';
$dbname = 'sunnybill';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Token mit customers:read Berechtigung ===\n\n";
    
    $sql = "SELECT token, name, abilities, expires_at, is_active FROM app_tokens 
            WHERE is_active = 1 
            AND expires_at > NOW() 
            AND JSON_CONTAINS(abilities, '\"customers:read\"')
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Token gefunden:\n";
        echo "Name: {$result['name']}\n";
        echo "Token: {$result['token']}\n";
        echo "Abilities: {$result['abilities']}\n";
        echo "Expires: {$result['expires_at']}\n";
        echo "Active: {$result['is_active']}\n";
    } else {
        echo "❌ Kein Token gefunden.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Datenbankfehler: " . $e->getMessage() . "\n";
}
