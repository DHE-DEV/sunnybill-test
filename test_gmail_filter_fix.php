<?php

require_once 'vendor/autoload.php';

use App\Services\GmailService;
use App\Models\CompanySetting;
use App\Models\GmailEmail;

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail Filter Fix Test ===\n\n";

try {
    $gmailService = new GmailService();
    
    // Test 1: Aktuelle Konfiguration prüfen
    echo "1. Checking current configuration...\n";
    $settings = CompanySetting::current();
    echo "Gmail enabled: " . ($settings->isGmailEnabled() ? 'Yes' : 'No') . "\n";
    echo "Gmail filter enabled: " . ($settings->gmail_filter_inbox ? 'Yes' : 'No') . "\n\n";
    
    // Test 2: Aktuelle E-Mails in DB anzeigen
    echo "2. Current emails in database:\n";
    $emails = GmailEmail::select('id', 'subject', 'labels')->get();
    foreach ($emails as $email) {
        $labels = is_array($email->labels) ? $email->labels : json_decode($email->labels, true);
        $hasInbox = in_array('INBOX', $labels ?? []);
        echo "ID: {$email->id}, Subject: {$email->subject}, Has INBOX: " . ($hasInbox ? 'YES' : 'NO') . "\n";
    }
    echo "\n";
    
    // Test 3: Gmail API mit Filter testen
    echo "3. Testing Gmail API with filter...\n";
    
    // Filter aktivieren
    $settings->gmail_filter_inbox = true;
    $settings->save();
    
    // E-Mails mit Filter abrufen
    $options = [
        'maxResults' => 10,
        'q' => '-in:inbox' // Gmail Query um INBOX auszuschließen
    ];
    
    $messages = $gmailService->getMessages($options);
    echo "Found " . count($messages['messages'] ?? []) . " messages without INBOX label\n";
    
    foreach ($messages['messages'] ?? [] as $messageInfo) {
        $messageData = $gmailService->getMessage($messageInfo['id']);
        if ($messageData) {
            $labels = $messageData['labelIds'] ?? [];
            $hasInbox = in_array('INBOX', $labels);
            $subject = '';
            
            // Subject extrahieren
            foreach ($messageData['payload']['headers'] ?? [] as $header) {
                if ($header['name'] === 'Subject') {
                    $subject = $header['value'];
                    break;
                }
            }
            
            echo "Message: {$subject}, Labels: " . implode(', ', $labels) . ", Has INBOX: " . ($hasInbox ? 'YES' : 'NO') . "\n";
        }
    }
    
    echo "\n4. Testing sync with filter...\n";
    
    // Sync mit Filter
    $stats = $gmailService->syncEmails($options);
    echo "Sync stats: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";
    
    // Prüfen was in DB gespeichert wurde
    echo "\n5. Checking database after filtered sync...\n";
    $newEmails = GmailEmail::orderBy('id', 'desc')->limit(5)->get();
    foreach ($newEmails as $email) {
        $labels = is_array($email->labels) ? $email->labels : json_decode($email->labels, true);
        $hasInbox = in_array('INBOX', $labels ?? []);
        echo "ID: {$email->id}, Subject: {$email->subject}, Has INBOX: " . ($hasInbox ? 'YES' : 'NO') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test completed ===\n";
