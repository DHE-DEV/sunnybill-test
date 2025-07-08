<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Fixing Gmail Notifications ===\n\n";

// 1. Enable Gmail notifications for company
echo "1. Enabling Gmail notifications for company...\n";
$company = \App\Models\CompanySetting::first();
$company->gmail_notifications_enabled = true;
$company->save();
echo "  ✅ Gmail notifications enabled for: {$company->company_name}\n\n";

// 2. Add users relationship to CompanySetting model if missing
echo "2. Checking CompanySetting model for users relationship...\n";
$companySettingPath = 'app/Models/CompanySetting.php';
$content = file_get_contents($companySettingPath);

if (strpos($content, 'public function users()') === false) {
    echo "  ❌ Users relationship missing in CompanySetting model\n";
    echo "  📝 Adding users relationship...\n";
    
    // Find the end of the class (before the last closing brace)
    $lastBracePos = strrpos($content, '}');
    
    $usersRelationship = "
    /**
     * Get the users that belong to this company
     */
    public function users()
    {
        return \$this->hasMany(User::class, 'company_setting_id');
    }
";
    
    $newContent = substr($content, 0, $lastBracePos) . $usersRelationship . "\n" . substr($content, $lastBracePos);
    file_put_contents($companySettingPath, $newContent);
    echo "  ✅ Users relationship added to CompanySetting model\n";
} else {
    echo "  ✅ Users relationship already exists in CompanySetting model\n";
}

// 3. Check if User model has company_setting_id
echo "\n3. Checking User model for company relationship...\n";
$users = \App\Models\User::all();
$firstUser = $users->first();

if ($firstUser && !isset($firstUser->company_setting_id)) {
    echo "  ❌ Users don't have company_setting_id field\n";
    echo "  📝 Adding company_setting_id to all users...\n";
    
    foreach ($users as $user) {
        $user->company_setting_id = 1; // Assign to first company
        $user->save();
    }
    echo "  ✅ All users assigned to company ID 1\n";
} else {
    echo "  ✅ Users already have company relationships\n";
}

// 4. Register event listeners
echo "\n4. Checking event listeners registration...\n";
$eventServiceProviderPath = 'app/Providers/EventServiceProvider.php';

if (file_exists($eventServiceProviderPath)) {
    $content = file_get_contents($eventServiceProviderPath);
    
    if (strpos($content, 'NewGmailReceived') === false) {
        echo "  ❌ NewGmailReceived event not registered\n";
        echo "  📝 Please add event listeners manually to EventServiceProvider\n";
    } else {
        echo "  ✅ NewGmailReceived event already registered\n";
    }
} else {
    echo "  ❌ EventServiceProvider not found\n";
}

// 5. Test notification creation
echo "\n5. Testing notification creation...\n";
try {
    $testUser = \App\Models\User::first();
    
    $notification = $testUser->notifications()->create([
        'type' => 'gmail_new_emails',
        'title' => 'Test Gmail Notification',
        'message' => 'This is a test notification for Gmail.',
        'data' => [
            'count' => 1,
            'company_id' => 1,
            'link' => '/admin/gmail-emails',
        ],
        'is_read' => false,
    ]);
    
    echo "  ✅ Test notification created successfully (ID: {$notification->id})\n";
} catch (\Exception $e) {
    echo "  ❌ Error creating test notification: " . $e->getMessage() . "\n";
}

echo "\n=== Fix Complete ===\n";
echo "Please run: php debug_gmail_notifications.php to verify fixes\n";
