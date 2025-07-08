<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Gmail Notifications Debug ===\n\n";

// 1. Check user notification settings
echo "1. Checking user notification settings...\n";
$users = \App\Models\User::all();
foreach ($users as $user) {
    echo "  User: {$user->name} (ID: {$user->id})\n";
    echo "    Email: {$user->email}\n";
    echo "    Gmail notifications enabled: " . ($user->gmail_notifications_enabled ? 'Yes' : 'No') . "\n";
    echo "    Created: {$user->created_at}\n";
    echo "    Updated: {$user->updated_at}\n\n";
}

// 2. Check company notification settings
echo "2. Checking company notification settings...\n";
$companies = \App\Models\CompanySetting::all();
foreach ($companies as $company) {
    echo "  Company: {$company->company_name} (ID: {$company->id})\n";
    echo "    Gmail enabled: " . ($company->gmail_enabled ? 'Yes' : 'No') . "\n";
    echo "    Gmail auto sync: " . ($company->gmail_auto_sync ? 'Yes' : 'No') . "\n";
    echo "    Gmail notifications enabled: " . ($company->gmail_notifications_enabled ? 'Yes' : 'No') . "\n";
    echo "    Gmail notification email: {$company->gmail_notification_email}\n\n";
}

// 3. Check recent Gmail emails
echo "3. Checking recent Gmail emails...\n";
$recentEmails = \App\Models\GmailEmail::orderBy('created_at', 'desc')->limit(5)->get();
foreach ($recentEmails as $email) {
    echo "  Email: {$email->subject}\n";
    echo "    From: {$email->from_email}\n";
    echo "    Gmail ID: {$email->gmail_id}\n";
    echo "    Created: {$email->created_at}\n";
    echo "    Updated: {$email->updated_at}\n\n";
}

// 4. Check notifications table
echo "4. Checking notifications table...\n";
$notifications = \App\Models\Notification::orderBy('created_at', 'desc')->limit(10)->get();
if ($notifications->isEmpty()) {
    echo "  ❌ No notifications found in database\n\n";
} else {
    foreach ($notifications as $notification) {
        echo "  Notification: {$notification->title}\n";
        echo "    Type: {$notification->type}\n";
        echo "    Message: {$notification->message}\n";
        echo "    User ID: {$notification->user_id}\n";
        echo "    Read: " . ($notification->is_read ? 'Yes' : 'No') . "\n";
        echo "    Created: {$notification->created_at}\n\n";
    }
}

// 5. Test event firing manually
echo "5. Testing event firing manually...\n";
try {
    $testEmail = \App\Models\GmailEmail::first();
    if ($testEmail) {
        $company = \App\Models\CompanySetting::first();
        $users = $company->users()
            ->where('gmail_notifications_enabled', true)
            ->get()
            ->map(function ($user) {
                return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
            })
            ->toArray();
        
        echo "  Test email: {$testEmail->subject}\n";
        echo "  Users to notify: " . count($users) . "\n";
        
        if (count($users) > 0) {
            echo "  Firing NewGmailReceived event...\n";
            event(new \App\Events\NewGmailReceived($testEmail, $users));
            echo "  ✅ Event fired successfully\n";
        } else {
            echo "  ❌ No users with notifications enabled\n";
        }
    } else {
        echo "  ❌ No test email found\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Error firing event: " . $e->getMessage() . "\n";
}

echo "\n6. Checking event listeners...\n";
try {
    $listeners = \Illuminate\Support\Facades\Event::getListeners(\App\Events\NewGmailReceived::class);
    if (empty($listeners)) {
        echo "  ❌ No listeners registered for NewGmailReceived event\n";
    } else {
        echo "  ✅ Found " . count($listeners) . " listeners for NewGmailReceived event\n";
        foreach ($listeners as $listener) {
            echo "    - " . (is_string($listener) ? $listener : get_class($listener)) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking listeners: " . $e->getMessage() . "\n";
}

echo "\n7. Checking queue jobs...\n";
try {
    // Check if there are any failed jobs
    $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
    echo "  Failed jobs: {$failedJobs}\n";
    
    if ($failedJobs > 0) {
        $recentFailedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(3)
            ->get();
        
        foreach ($recentFailedJobs as $job) {
            echo "    Failed job: {$job->payload}\n";
            echo "    Exception: " . substr($job->exception, 0, 200) . "...\n";
            echo "    Failed at: {$job->failed_at}\n\n";
        }
    }
} catch (\Exception $e) {
    echo "  ❌ Error checking queue jobs: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
