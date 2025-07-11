<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CompanySetting Fix ===\n";

$cs = \App\Models\CompanySetting::first();
echo "Aktueller Prefix: '" . $cs->customer_number_prefix . "'\n";

$cs->customer_number_prefix = 'K';
$cs->save();

echo "Neuer Prefix: '" . $cs->customer_number_prefix . "'\n";

// Teste die Kundennummer-Generierung
$generated = $cs->generateCustomerNumber(1);
echo "Generierte Nummer: '$generated'\n";

echo "Fix abgeschlossen!\n";