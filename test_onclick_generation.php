<?php

// Test der onclick-Generierung
$contract_id = '018e8b8b-b5b8-7b8b-8b8b-8b8b8b8b8b8b';
$year = 2025;
$month = 7;
$title = 'Abrechnung 2025-07 - Energieversorgung E.ON';

// Alte Methode (problematisch)
$escapedTitle_old = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$onclick_old = "openCreateBillingModal('" . $contract_id . "', " . $year . ", " . $month . ", '" . $escapedTitle_old . "')";

// Neue Methode (JavaScript-sicher) - mit doppelten Anführungszeichen
$jsTitle_new = str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], $title);
$onclickFunction_new = 'openCreateBillingModal("' . $contract_id . '", ' . $year . ', ' . $month . ', "' . $jsTitle_new . '")';
$onclick_new = $onclickFunction_new;

echo "=== ONCLICK GENERATION TEST ===\n\n";

echo "Original Title: " . $title . "\n\n";

echo "ALTE METHODE (problematisch):\n";
echo "Escaped Title: " . $escapedTitle_old . "\n";
echo "onclick Attribut: " . $onclick_old . "\n";
echo "HTML: <span onclick=\"" . htmlspecialchars($onclick_old, ENT_QUOTES) . "\">Test</span>\n\n";

echo "NEUE METHODE (JavaScript-sicher):\n";
echo "JS Title: " . $jsTitle_new . "\n";
echo "onclick Attribut: " . $onclick_new . "\n";
echo "HTML: <span onclick=\"" . htmlspecialchars($onclick_new, ENT_QUOTES) . "\">Test</span>\n\n";

echo "=== VOLLSTÄNDIGES HTML BEISPIEL ===\n";
$fullHtml = '<span onclick=\'' . $onclick_new . '\' style="cursor: pointer; background-color: #f8d7da;">Klick mich</span>';
echo $fullHtml . "\n\n";

echo "=== BROWSER-READY HTML ===\n";
echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head><title>Test</title></head>\n";
echo "<body>\n";
echo "<script>\n";
echo "function openCreateBillingModal(contractId, year, month, title) {\n";
echo "    alert('Klick erkannt! Contract ID: ' + contractId + ', Jahr: ' + year + ', Monat: ' + month + ', Titel: ' + title);\n";
echo "}\n";
echo "</script>\n";
echo $fullHtml . "\n";
echo "</body>\n";
echo "</html>\n";

?>