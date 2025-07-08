<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\CompanySetting;
use App\Services\GmailService;
use App\Models\GmailEmail;
use App\Events\NewGmailReceived;

class SyncGmailEmailsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CompanySetting $company,
        public array $options = []
    ) {
        // Set queue based on company priority or use default
        $this->onQueue('gmail-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting Gmail sync job for company: {$this->company->company_name} (ID: {$this->company->id})");
        
        try {
            // Initialize Gmail service with company context
            $gmailService = new GmailService($this->company);
            
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
            $stats = $gmailService->syncEmails($this->options);
            
            // Get new emails after sync
            $emailsAfter = GmailEmail::pluck('gmail_id')->toArray();
            $newEmailIds = array_diff($emailsAfter, $emailsBefore);
            
            // Fire events for new emails
            foreach ($newEmailIds as $gmailId) {
                $email = GmailEmail::findByGmailId($gmailId);
                if ($email) {
                    // Get users who should receive notifications
                    $users = $this->company->users()
                        ->where('gmail_notifications_enabled', true)
                        ->get()
                        ->map(function ($user) {
                            return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
                        })
                        ->toArray();
                    
                    event(new NewGmailReceived($email, $users));
                }
            }
            
            // Send notifications for new emails
            if (count($newEmailIds) > 0) {
                $this->sendNewEmailNotifications(count($newEmailIds));
            }
            
            Log::info("Gmail sync job completed successfully", [
                'company_id' => $this->company->id,
                'company_name' => $this->company->company_name,
                'stats' => $stats,
                'new_emails' => count($newEmailIds),
            ]);
            
        } catch (\Exception $e) {
            Log::error("Gmail sync job failed for company {$this->company->id}: " . $e->getMessage(), [
                'company_id' => $this->company->id,
                'company_name' => $this->company->company_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Update company error status
            $this->company->setGmailLastError($e->getMessage());
            
            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }
    
    /**
     * Send notifications for new emails
     */
    private function sendNewEmailNotifications(int $newEmailCount): void
    {
        try {
            // Get users who should receive notifications
            $users = $this->company->users()
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
                        'company_id' => $this->company->id,
                        'link' => route('filament.admin.resources.gmail-emails.index'),
                    ],
                    'is_read' => false,
                ]);
            }
            
            Log::info("Sent Gmail notifications to {$users->count()} users", [
                'company_id' => $this->company->id,
                'new_email_count' => $newEmailCount,
                'users_notified' => $users->count(),
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to send Gmail notifications: " . $e->getMessage(), [
                'company_id' => $this->company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Gmail sync job permanently failed for company {$this->company->id}", [
            'company_id' => $this->company->id,
            'company_name' => $this->company->company_name,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
        
        // Update company with final error status
        $this->company->setGmailLastError(
            "Sync failed after {$this->attempts()} attempts: " . $exception->getMessage()
        );
    }
    
    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 120, 300]; // 30 seconds, 2 minutes, 5 minutes
    }
    
    /**
     * Determine if the job should be retried based on the exception.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30); // Stop retrying after 30 minutes
    }
}
