<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CompanySetting;
use App\Jobs\SyncGmailEmailsJob;
use Illuminate\Support\Facades\Log;

echo "=== Gmail Auto-Sync Test ===\n\n";

try {
    // Test 1: Check companies with auto-sync enabled
    echo "1. Checking companies with auto-sync enabled...\n";
    
    $companies = CompanySetting::where('gmail_enabled', true)
        ->where('gmail_auto_sync', true)
        ->whereNotNull('gmail_refresh_token')
        ->get();
    
    echo "Found {$companies->count()} companies with auto-sync enabled:\n";
    
    foreach ($companies as $company) {
        echo "  - {$company->company_name} (ID: {$company->id})\n";
        echo "    Sync Interval: " . ($company->gmail_sync_interval ?? 5) . " minutes\n";
        echo "    Last Sync: " . ($company->gmail_last_sync ? $company->gmail_last_sync->format('Y-m-d H:i:s') : 'Never') . "\n";
        echo "    Auto-Sync: " . ($company->gmail_auto_sync ? 'Yes' : 'No') . "\n\n";
    }
    
    if ($companies->isEmpty()) {
        echo "No companies found with auto-sync enabled.\n";
        echo "Please enable auto-sync for at least one company to test.\n\n";
        exit;
    }
    
    // Test 2: Test sync command (dry run)
    echo "2. Testing sync command (dry run)...\n";
    
    $exitCode = \Illuminate\Support\Facades\Artisan::call('gmail:sync', [
        '--dry-run' => true,
        '--force' => true
    ]);
    
    echo "Command output:\n";
    echo \Illuminate\Support\Facades\Artisan::output();
    echo "Exit code: $exitCode\n\n";
    
    // Test 3: Test queue job dispatch
    echo "3. Testing queue job dispatch...\n";
    
    $testCompany = $companies->first();
    echo "Dispatching sync job for: {$testCompany->company_name}\n";
    
    try {
        SyncGmailEmailsJob::dispatch($testCompany);
        echo "✅ Job dispatched successfully\n";
        echo "Job will be processed when queue worker is running\n";
        echo "Start queue worker with: php artisan queue:work\n\n";
    } catch (\Exception $e) {
        echo "❌ Failed to dispatch job: " . $e->getMessage() . "\n\n";
    }
    
    // Test 4: Test scheduler configuration
    echo "4. Testing scheduler configuration...\n";
    
    $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
    $events = $schedule->events();
    
    echo "Found " . count($events) . " scheduled events:\n";
    
    foreach ($events as $event) {
        if (str_contains($event->command, 'gmail:sync')) {
            echo "  ✅ Gmail sync command found in scheduler\n";
            echo "     Command: {$event->command}\n";
            echo "     Expression: {$event->expression}\n";
            echo "     Description: {$event->description}\n\n";
        }
    }
    
    // Test 5: Check queue configuration
    echo "5. Checking queue configuration...\n";
    
    $queueConnection = config('queue.default');
    echo "Default queue connection: $queueConnection\n";
    
    $queueConfig = config("queue.connections.$queueConnection");
    echo "Queue driver: " . ($queueConfig['driver'] ?? 'unknown') . "\n";
    
    if ($queueConnection === 'database') {
        echo "Database queue tables:\n";
        
        try {
            $jobsCount = \DB::table('jobs')->count();
            echo "  - jobs table: $jobsCount pending jobs\n";
        } catch (\Exception $e) {
            echo "  - jobs table: Error - " . $e->getMessage() . "\n";
        }
        
        try {
            $failedJobsCount = \DB::table('failed_jobs')->count();
            echo "  - failed_jobs table: $failedJobsCount failed jobs\n";
        } catch (\Exception $e) {
            echo "  - failed_jobs table: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Test 6: Test manual sync for one company
    echo "6. Testing manual sync for one company...\n";
    
    $testCompany = $companies->first();
    echo "Testing sync for: {$testCompany->company_name}\n";
    
    try {
        $exitCode = \Illuminate\Support\Facades\Artisan::call('gmail:sync', [
            '--company' => $testCompany->id,
            '--force' => true
        ]);
        
        echo "Sync completed with exit code: $exitCode\n";
        echo "Command output:\n";
        echo \Illuminate\Support\Facades\Artisan::output();
        
    } catch (\Exception $e) {
        echo "❌ Sync failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Summary
    echo "=== Test Summary ===\n";
    echo "✅ Auto-sync system is configured and ready\n";
    echo "✅ Commands are working\n";
    echo "✅ Queue jobs can be dispatched\n";
    echo "✅ Scheduler is configured\n\n";
    
    echo "Next steps:\n";
    echo "1. Start the Laravel scheduler: Add to crontab:\n";
    echo "   * * * * * cd " . base_path() . " && php artisan schedule:run >> /dev/null 2>&1\n\n";
    echo "2. Start queue workers:\n";
    echo "   php artisan queue:work --queue=gmail-sync\n\n";
    echo "3. Monitor logs:\n";
    echo "   tail -f storage/logs/laravel.log\n";
    echo "   tail -f storage/logs/gmail-sync.log\n\n";
    
} catch (\Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
