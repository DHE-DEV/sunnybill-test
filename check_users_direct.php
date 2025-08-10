<?php

// Direkte Datenbankverbindung
$host = 'localhost';
$dbname = 'sunnybill';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Benutzer in der Datenbank ===\n\n";
    
    $sql = "SELECT id, name, email, is_active FROM users ORDER BY id LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $status = $user['is_active'] ? '✅ Aktiv' : '❌ Inaktiv';
        echo "ID: {$user['id']} | Name: {$user['name']} | Email: {$user['email']} | Status: $status\n";
    }
    
    echo "\n" . count($users) . " Benutzer gefunden.\n";
    
} catch (PDOException $e) {
    echo "❌ Datenbankfehler: " . $e->getMessage() . "\n";
}
