<?php

require_once 'vendor/autoload.php';

use App\Services\GmailService;
use App\Models\CompanySetting;
use App\Models\GmailEmail;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Complete Filter Fix Test ===\n\n";

try {
    $gmailService = new GmailService();
    $settings = CompanySetting::current();
    
    // Test 1: Aktuelle E-Mails löschen
    echo "1. Deleting existing emails from database...\n";
    $deletedCount = GmailEmail::count();
    GmailEmail::truncate();
    echo "Deleted {$deletedCount} emails\n\n";
    
    // Test 2: Filter deaktivieren und normale E-Mails synchronisieren
    echo "2. Disabling filter and syncing normal emails...\n";
    $settings->gmail_filter_inbox = false;
    $settings->save();
    
    $stats = $gmailService->syncEmails(['maxResults' => 5]);
    echo "Sync stats (no filter): " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";
    
    // Prüfen was synchronisiert wurde
    echo "\n3. Checking what was synced (should include INBOX emails):\n";
    $emails = GmailEmail::select('id', 'subject', 'labels')->get();
    foreach ($emails as $email) {
        $labels = is_array($email->labels) ? $email->labels : json_decode($email->labels, true);
        $hasInbox = in_array('INBOX', $labels ?? []);
        echo "ID: {$email->id}, Subject: {$email->subject}, Has INBOX: " . ($hasInbox ? 'YES' : 'NO') . "\n";
    }
    
    // Test 3: E-Mails wieder löschen
    echo "\n4. Deleting emails again...\n";
    GmailEmail::truncate();
    echo "Deleted all emails\n";
    
    // Test 4: Filter aktivieren und synchronisieren
    echo "\n5. Enabling filter and syncing...\n";
    $settings->gmail_filter_inbox = true;
    $settings->save();
    
    $stats = $gmailService->syncEmails(['maxResults' => 10]);
    echo "Sync stats (with filter): " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";
    
    // Prüfen was synchronisiert wurde
    echo "\n6. Checking what was synced (should NOT include INBOX emails):\n";
    $emails = GmailEmail::select('id', 'subject', 'labels')->get();
    if ($emails->count() === 0) {
        echo "No emails found - filter is working correctly!\n";
    } else {
        foreach ($emails as $email) {
            $labels = is_array($email->labels) ? $email->labels : json_decode($email->labels, true);
            $hasInbox = in_array('INBOX', $labels ?? []);
            echo "ID: {$email->id}, Subject: {$email->subject}, Has INBOX: " . ($hasInbox ? 'YES' : 'NO') . "\n";
        }
    }
    
    echo "\n7. Final verification:\n";
    echo "Filter enabled: " . ($settings->gmail_filter_inbox ? 'Yes' : 'No') . "\n";
    echo "Total emails in DB: " . GmailEmail::count() . "\n";
    echo "Emails with INBOX label: " . GmailEmail::whereJsonContains('labels', 'INBOX')->count() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test completed ===\n";
