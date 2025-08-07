<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Database\Capsule\Manager as Capsule;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Query the token
$token = \App\Models\AppToken::find(8);

if ($token) {
    echo "Token ID: " . $token->id . "\n";
    echo "Token Name: " . $token->name . "\n";
    echo "Abilities (raw): " . json_encode($token->abilities) . "\n";
    echo "Abilities count: " . count($token->abilities ?? []) . "\n";
    echo "Individual abilities:\n";
    
    if ($token->abilities) {
        foreach ($token->abilities as $ability) {
            echo "- $ability\n";
        }
    } else {
        echo "No abilities found!\n";
    }
} else {
    echo "Token with ID 8 not found!\n";
}
