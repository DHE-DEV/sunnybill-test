<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompanySetting;
use App\Jobs\SyncGmailEmailsJob;
use App\Services\GmailService;
use App\Models\GmailEmail;
use App\Events\NewGmailReceived;
use Illuminate\Support\Facades\Log;

class GmailSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gmail:sync 
                            {--company= : Specific company ID to sync}
                            {--force : Force sync regardless of interval}
                            {--dry-run : Show what would be synced without actually syncing}
                            {--queue : Dispatch sync jobs to queue instead of running synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize Gmail emails for companies with auto-sync enabled';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Gmail synchronization...');
        
        $companyId = $this->option('company');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $useQueue = $this->option('queue');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actual synchronization will be performed');
        }
        
        if ($useQueue) {
            $this->info('QUEUE MODE - Jobs will be dispatched to queue');
        }
        
        // Get companies to sync
        $companies = $this->getCompaniesToSync($companyId, $force);
        
        if ($companies->isEmpty()) {
            $this->info('No companies need synchronization at this time.');
            return 0;
        }
        
        $this->info("Found {$companies->count()} companies to sync:");
        
        if ($useQueue) {
            return $this->dispatchQueueJobs($companies, $dryRun);
        }
        
        $totalStats = [
            'companies_processed' => 0,
            'companies_success' => 0,
            'companies_failed' => 0,
            'total_new_emails' => 0,
            'total_updated_emails' => 0,
            'total_errors' => 0,
        ];
        
        foreach ($companies as $company) {
            $this->line("Processing company: {$company->company_name} (ID: {$company->id})");
            
            if ($dryRun) {
                $this->info("  Would sync with interval: {$company->gmail_sync_interval} minutes");
                $this->info("  Last sync: " . ($company->gmail_last_sync ? $company->gmail_last_sync->format('Y-m-d H:i:s') : 'Never'));
                continue;
            }
            
            try {
                $stats = $this->syncCompanyEmails($company);
                
                $this->info("  ✅ Success: {$stats['new']} new, {$stats['updated']} updated, {$stats['errors']} errors");
                
                $totalStats['companies_success']++;
                $totalStats['total_new_emails'] += $stats['new'];
                $totalStats['total_updated_emails'] += $stats['updated'];
                $totalStats['total_errors'] += $stats['errors'];
                
                // Send notifications for new emails
                if ($stats['new'] > 0) {
                    $this->sendNewEmailNotifications($company, $stats['new']);
                }
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed: " . $e->getMessage());
                Log::error("Gmail sync failed for company {$company->id}: " . $e->getMessage());
                
                $totalStats['companies_failed']++;
                
                // Update company error status
                $company->setGmailLastError($e->getMessage());
            }
            
            $totalStats['companies_processed']++;
        }
        
        // Display summary
        $this->displaySummary($totalStats, $dryRun);
        
        return 0;
    }
    
    /**
     * Dispatch queue jobs for companies
     */
    private function dispatchQueueJobs($companies, bool $dryRun): int
    {
        $jobsDispatched = 0;
        
        foreach ($companies as $company) {
            $this->line("Dispatching job for company: {$company->company_name} (ID: {$company->id})");
            
            if ($dryRun) {
                $this->info("  Would dispatch sync job with interval: {$company->gmail_sync_interval} minutes");
                $this->info("  Last sync: " . ($company->gmail_last_sync ? $company->gmail_last_sync->format('Y-m-d H:i:s') : 'Never'));
                continue;
            }
            
            try {
                // Dispatch the sync job
                SyncGmailEmailsJob::dispatch($company);
                
                $this->info("  ✅ Job dispatched successfully");
                $jobsDispatched++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to dispatch job: " . $e->getMessage());
                Log::error("Failed to dispatch Gmail sync job for company {$company->id}: " . $e->getMessage());
            }
        }
        
        $this->newLine();
        $this->info("=== Queue Jobs Summary ===");
        
        if ($dryRun) {
            $this->warn('DRY RUN - No jobs were actually dispatched');
            $this->info("Would have dispatched {$companies->count()} jobs");
        } else {
            $this->info("Successfully dispatched {$jobsDispatched} out of {$companies->count()} jobs");
            
            if ($jobsDispatched > 0) {
                $this->info("Jobs are now queued and will be processed by queue workers");
                $this->info("Monitor job progress with: php artisan queue:work");
            }
        }
        
        return 0;
    }
    
    /**
     * Get companies that need synchronization
     */
    private function getCompaniesToSync(?string $companyId, bool $force)
    {
        $query = CompanySetting::query()
            ->where('gmail_enabled', true)
            ->where('gmail_auto_sync', true)
            ->whereNotNull('gmail_refresh_token');
        
        // Filter by specific company if provided
        if ($companyId) {
            $query->where('id', $companyId);
        }
        
        $companies = $query->get();
        
        // Filter by sync interval if not forced
        if (!$force) {
            $companies = $companies->filter(function ($company) {
                return $this->shouldSyncCompany($company);
            });
        }
        
        return $companies;
    }
    
    /**
     * Check if a company should be synced based on its interval
     */
    private function shouldSyncCompany(CompanySetting $company): bool
    {
        $syncInterval = $company->gmail_sync_interval ?? 5; // Default 5 minutes
        $lastSync = $company->gmail_last_sync;
        
        if (!$lastSync) {
            return true; // Never synced before
        }
        
        $nextSyncTime = $lastSync->addMinutes($syncInterval);
        
        return now()->gte($nextSyncTime);
    }
    
    /**
     * Sync emails for a specific company
     */
    private function syncCompanyEmails(CompanySetting $company): array
    {
        // Initialize Gmail service with company context
        $gmailService = new GmailService($company);
        
        // Verify configuration
        if (!$gmailService->isConfigured()) {
            throw new \Exception('Gmail is not properly configured');
        }
        
        // Test connection
        $connectionTest = $gmailService->testConnection();
        if (!$connectionTest['success']) {
            throw new \Exception('Gmail connection failed: ' . $connectionTest['error']);
        }
        
        // Get emails before sync to detect new ones
        $emailsBefore = GmailEmail::pluck('gmail_id')->toArray();
        
        // Perform synchronization
        $stats = $gmailService->syncEmails();
        
        // Get new emails after sync
        $emailsAfter = GmailEmail::pluck('gmail_id')->toArray();
        $newEmailIds = array_diff($emailsAfter, $emailsBefore);
        
        // Fire events for new emails
        foreach ($newEmailIds as $gmailId) {
            $email = GmailEmail::findByGmailId($gmailId);
            if ($email) {
                // Get users who should receive notifications
                $users = $company->users()
                    ->where('gmail_notifications_enabled', true)
                    ->get()
                    ->map(function ($user) {
                        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
                    })
                    ->toArray();
                
                event(new NewGmailReceived($email, $users));
            }
        }
        
        return $stats;
    }
    
    /**
     * Send notifications for new emails
     */
    private function sendNewEmailNotifications(CompanySetting $company, int $newEmailCount): void
    {
        try {
            // Get users who should receive notifications
            $users = $company->users()
                ->where('gmail_notifications_enabled', true)
                ->get();
            
            foreach ($users as $user) {
                // Create notification
                $user->notifications()->create([
                    'type' => 'gmail_new_emails',
                    'title' => "Neue E-Mails erhalten",
                    'message' => "Es sind {$newEmailCount} neue E-Mails eingegangen.",
                    'data' => [
                        'count' => $newEmailCount,
                        'company_id' => $company->id,
                        'link' => route('filament.admin.resources.gmail-emails.index'),
                    ],
                    'is_read' => false,
                ]);
            }
            
            $this->info("  📧 Sent notifications to {$users->count()} users");
            
        } catch (\Exception $e) {
            Log::error("Failed to send Gmail notifications: " . $e->getMessage());
        }
    }
    
    /**
     * Display synchronization summary
     */
    private function displaySummary(array $stats, bool $dryRun): void
    {
        $this->newLine();
        $this->info('=== Gmail Synchronization Summary ===');
        
        if ($dryRun) {
            $this->warn('DRY RUN - No actual changes were made');
        }
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Companies Processed', $stats['companies_processed']],
                ['Companies Success', $stats['companies_success']],
                ['Companies Failed', $stats['companies_failed']],
                ['Total New Emails', $stats['total_new_emails']],
                ['Total Updated Emails', $stats['total_updated_emails']],
                ['Total Errors', $stats['total_errors']],
            ]
        );
        
        if ($stats['companies_failed'] > 0) {
            $this->warn("⚠️  {$stats['companies_failed']} companies failed to sync. Check logs for details.");
        }
        
        if ($stats['total_new_emails'] > 0) {
            $this->info("📧 {$stats['total_new_emails']} new emails synchronized and notifications sent.");
        }
        
        $this->info('Gmail synchronization completed.');
    }
}
