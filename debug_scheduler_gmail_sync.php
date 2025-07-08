<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "=== Gmail Sync Scheduler Debug ===\n\n";

// 1. Test direct command call
echo "1. Testing direct command call...\n";
try {
    $exitCode = $kernel->call('gmail:sync');
    echo "✅ Direct call successful, exit code: $exitCode\n";
} catch (Exception $e) {
    echo "❌ Direct call failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// 2. Test scheduler call
echo "2. Testing scheduler call...\n";
try {
    $exitCode = $kernel->call('schedule:run');
    echo "✅ Scheduler call successful, exit code: $exitCode\n";
} catch (Exception $e) {
    echo "❌ Scheduler call failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// 3. Check scheduled commands
echo "3. Checking scheduled commands...\n";
try {
    $schedule = $app->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = $schedule->events();
    
    echo "Found " . count($events) . " scheduled events:\n";
    foreach ($events as $event) {
        echo "  - Command: " . $event->command . "\n";
        echo "    Expression: " . $event->expression . "\n";
        echo "    Description: " . $event->description . "\n";
        echo "    Timezone: " . ($event->timezone ?? 'default') . "\n";
        echo "    Without overlapping: " . ($event->withoutOverlapping ? 'Yes' : 'No') . "\n";
        echo "    Run in background: " . ($event->runInBackground ? 'Yes' : 'No') . "\n";
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Failed to check scheduled commands: " . $e->getMessage() . "\n";
}

// 4. Test company settings
echo "4. Checking company settings...\n";
try {
    $companies = \App\Models\CompanySetting::where('gmail_auto_sync', true)->get();
    echo "Found " . $companies->count() . " companies with auto-sync enabled:\n";
    
    foreach ($companies as $company) {
        echo "  - {$company->company_name} (ID: {$company->id})\n";
        echo "    Auto-sync: " . ($company->gmail_auto_sync ? 'Yes' : 'No') . "\n";
        echo "    Interval: {$company->gmail_sync_interval} minutes\n";
        echo "    Last sync: " . ($company->gmail_last_sync ?? 'Never') . "\n";
        echo "    Gmail configured: " . ($company->gmail_access_token ? 'Yes' : 'No') . "\n";
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Failed to check company settings: " . $e->getMessage() . "\n";
}

// 5. Test Gmail service initialization
echo "5. Testing Gmail service initialization...\n";
try {
    $company = \App\Models\CompanySetting::where('gmail_auto_sync', true)->first();
    if ($company) {
        $gmailService = new \App\Services\GmailService();
        $gmailService->initializeForCompany($company);
        echo "✅ Gmail service initialized successfully for company: {$company->company_name}\n";
        
        // Test getting messages
        $messages = $gmailService->getMessages(10);
        echo "✅ Retrieved " . count($messages) . " messages\n";
    } else {
        echo "❌ No company with auto-sync enabled found\n";
    }
} catch (Exception $e) {
    echo "❌ Gmail service initialization failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug completed ===\n";
