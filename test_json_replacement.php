<?php

echo "=== TEST: JSON STRING REPLACEMENT ===\n\n";

// Simuliere die Datenstruktur
$customerRole = new \stdClass();
$data = [
    'roles' => [
        'customer' => $customerRole
    ],
    'person' => [
        'firstName' => 'Max',
        'lastName' => 'Mustermann'
    ]
];

echo "1. Original PHP-Datenstruktur:\n";
var_dump($data['roles']['customer']);
echo "\n";

echo "2. JSON ohne Ersetzung:\n";
$jsonOriginal = json_encode($data, JSON_PRETTY_PRINT);
echo $jsonOriginal . "\n\n";

echo "3. JSON mit String-Ersetzung:\n";
$jsonReplaced = str_replace('"customer":[]', '"customer":{}', $jsonOriginal);
echo $jsonReplaced . "\n\n";

echo "4. Prüfe ob Ersetzung stattgefunden hat:\n";
if ($jsonOriginal !== $jsonReplaced) {
    echo "✅ Ersetzung erfolgreich!\n";
} else {
    echo "❌ Keine Ersetzung stattgefunden!\n";
}

echo "\n5. Suche nach Pattern:\n";
if (strpos($jsonOriginal, '"customer":[]') !== false) {
    echo "✅ Pattern 'customer':[] gefunden\n";
} else {
    echo "❌ Pattern 'customer':[] NICHT gefunden\n";
}

if (strpos($jsonOriginal, '"customer": []') !== false) {
    echo "✅ Pattern 'customer': [] (mit Leerzeichen) gefunden\n";
} else {
    echo "❌ Pattern 'customer': [] (mit Leerzeichen) NICHT gefunden\n";
}

echo "\n6. Teste verschiedene Ersetzungspattern:\n";
$patterns = [
    '"customer":[]' => '"customer":{}',
    '"customer": []' => '"customer": {}',
    '"customer" : []' => '"customer" : {}',
    '"customer":\s*\[\]' => '"customer":{}',
];

foreach ($patterns as $search => $replace) {
    $testJson = $jsonOriginal;
    if (strpos($search, '\s') !== false) {
        // Regex-Pattern
        $testJson = preg_replace('/' . $search . '/', $replace, $testJson);
    } else {
        // String-Pattern
        $testJson = str_replace($search, $replace, $testJson);
    }
    
    if ($testJson !== $jsonOriginal) {
        echo "✅ Pattern '$search' funktioniert!\n";
        echo "Ergebnis:\n" . $testJson . "\n\n";
        break;
    } else {
        echo "❌ Pattern '$search' funktioniert nicht\n";
    }
}

echo "=== TEST ABGESCHLOSSEN ===\n";
