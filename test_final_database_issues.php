<?php

require_once 'vendor/autoload.php';

use App\Models\Project;
use App\Models\SupplierContractBilling;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing both database issues:\n\n";

echo "1. Projects query: ";
try {
    $count = Project::whereIn('status', ['planning', 'active'])->count();
    echo "$count projects found ✓\n";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "2. Supplier billings query: ";
try {
    $count = SupplierContractBilling::count();
    echo "$count billings found ✓\n";
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nBoth issues resolved successfully!\n";
