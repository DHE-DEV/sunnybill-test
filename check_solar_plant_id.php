<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$plant = App\Models\SolarPlant::first();
if ($plant) {
    echo "Solar Plant ID Type: " . gettype($plant->id) . "\n";
    echo "Solar Plant ID Value: " . $plant->id . "\n";
    echo "Solar Plant ID Length: " . strlen($plant->id) . "\n";
} else {
    echo "No solar plant found\n";
}