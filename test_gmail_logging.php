<?php

require_once 'vendor/autoload.php';

use App\Services\GmailService;
use App\Models\CompanySetting;
use App\Models\GmailEmail;
use Illuminate\Support\Facades\Log;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Logging Test ===\n\n";

try {
    $gmailService = new GmailService();
    $settings = CompanySetting::current();
    
    echo "1. Current filter setting: " . ($settings->gmail_filter_inbox ? 'ENABLED' : 'DISABLED') . "\n\n";
    
    // Test mit Filter deaktiviert um E-Mails mit Labels zu sehen
    echo "2. Disabling filter to see emails with INBOX labels...\n";
    $settings->gmail_filter_inbox = false;
    $settings->save();
    
    // Alte E-Mails löschen
    GmailEmail::truncate();
    echo "Cleared existing emails from database\n\n";
    
    echo "3. Syncing emails with detailed logging...\n";
    echo "Check the Laravel log file (storage/logs/laravel.log) for detailed label information!\n\n";
    
    $stats = $gmailService->syncEmails(['maxResults' => 3]);
    echo "Sync completed. Stats: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "4. Emails in database:\n";
    $emails = GmailEmail::select('id', 'subject', 'labels', 'from')->get();
    foreach ($emails as $email) {
        $labels = is_array($email->labels) ? $email->labels : json_decode($email->labels, true);
        $from = is_array($email->from) ? $email->from[0]['email'] ?? 'Unknown' : 'Unknown';
        
        echo "- Subject: {$email->subject}\n";
        echo "  From: {$from}\n";
        echo "  Labels: " . implode(', ', $labels ?? []) . "\n";
        echo "  Has INBOX: " . (in_array('INBOX', $labels ?? []) ? 'YES' : 'NO') . "\n\n";
    }
    
    echo "5. Re-enabling filter...\n";
    $settings->gmail_filter_inbox = true;
    $settings->save();
    echo "Filter re-enabled\n\n";
    
    echo "=== Logging Information ===\n";
    echo "The following information is now logged for each email:\n";
    echo "- Gmail ID\n";
    echo "- Subject\n";
    echo "- From address\n";
    echo "- Total number of labels\n";
    echo "- All labels (complete list)\n";
    echo "- System labels (INBOX, SENT, DRAFT, etc.)\n";
    echo "- Category labels (CATEGORY_PERSONAL, CATEGORY_SOCIAL, etc.)\n";
    echo "- User-defined labels\n";
    echo "- Boolean flags (has_inbox, is_unread, is_important, is_starred)\n";
    echo "- Filter status\n\n";
    
    echo "Check your Laravel log file at: storage/logs/laravel.log\n";
    echo "Look for entries with 'Gmail Email Labels' for detailed information.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test completed ===\n";
