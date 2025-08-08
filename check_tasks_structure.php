<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Tasks table structure:\n";
$columns = DB::select('DESCRIBE tasks');
foreach($columns as $column) {
    echo $column->Field . ': ' . $column->Type . "\n";
}

echo "\nProjects table structure:\n";  
$columns = DB::select('DESCRIBE projects');
foreach($columns as $column) {
    echo $column->Field . ': ' . $column->Type . "\n";
}
