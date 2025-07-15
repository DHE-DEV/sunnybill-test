<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\GmailService;

class TestTaskNotificationsCommand extends Command
{
    protected $signature = 'task:test-notifications';
    protected $description = 'Test Task notification system';

    public function handle()
    {
        $this->info('=== Task Notification System Test ===');
        
        try {
            // 1. Teste TaskAssignedNotification direkt
            $this->info('');
            $this->info('=== Testing TaskAssignedNotification ===');
            
            // Finde einen Task und User
            $task = Task::first();
            $user = User::first();
            
            if (!$task) {
                $this->error('❌ No tasks found in database');
                return 1;
            }
            
            if (!$user) {
                $this->error('❌ No users found in database');
                return 1;
            }
            
            $this->info("✓ Found task: {$task->title} (ID: {$task->id})");
            $this->info("✓ Found user: {$user->name} (Email: {$user->email})");
            
            // 2. Teste Notification direkt
            $this->info('');
            $this->info('Testing TaskAssignedNotification directly...');
            
            $notification = new TaskAssignedNotification($task, $user);
            
            // Teste via() Methode
            $channels = $notification->via($user);
            $this->info('Notification channels: ' . implode(', ', $channels));
            
            // Teste toArray() Methode (für Database-Notifications)
            if (in_array('database', $channels)) {
                $databaseData = $notification->toArray($user);
                $this->info('✓ Database notification data generated');
                $this->info('Task ID: ' . $databaseData['task_id']);
                $this->info('Message: ' . $databaseData['message']);
            }
            
            // Teste toMail() Methode
            if (in_array('mail', $channels)) {
                $mailData = $notification->toMail($user);
                $this->info('✓ Mail notification data generated');
                $this->info('Subject: ' . $mailData->subject);
                $this->info('Greeting: ' . $mailData->greeting);
            }
            
            // 3. Teste E-Mail-Versendung über Notification
            $this->info('');
            $this->info('=== Testing Email via Notification ===');
            
            // Erstelle einen Test-User mit bekannter E-Mail
            $testEmail = 'dh@dhe.de';
            $this->info("Sending notification to: {$testEmail}");
            
            // Sende Notification direkt
            try {
                \Notification::route('mail', $testEmail)
                    ->notify($notification);
                
                $this->info('✓ Notification sent successfully via Laravel Notification system');
            } catch (\Exception $e) {
                $this->error('❌ Notification sending failed: ' . $e->getMessage());
                $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            }
            
            // 4. Teste @mention System
            $this->info('');
            $this->info('=== Testing @mention System ===');
            
            // Simuliere @mention
            $mentionText = "@{$user->username} Dies ist ein Test der @mention Funktionalität";
            $this->info("Testing mention text: {$mentionText}");
            
            // Teste Mention-Parsing (vereinfacht)
            if (preg_match_all('/@(\w+)/', $mentionText, $matches)) {
                $mentionedUsernames = $matches[1];
                $this->info('Found mentions: ' . implode(', ', $mentionedUsernames));
                
                foreach ($mentionedUsernames as $username) {
                    $mentionedUser = User::where('username', $username)->first();
                    if ($mentionedUser) {
                        $this->info("✓ Found mentioned user: {$mentionedUser->name} ({$mentionedUser->email})");
                        
                        // Teste E-Mail-Versendung für @mention
                        $gmailService = new GmailService();
                        $subject = "Task-Notiz: Sie wurden erwähnt in '{$task->title}'";
                        $body = "Hallo {$mentionedUser->name},\n\nSie wurden in einer Task-Notiz erwähnt:\n\n{$mentionText}\n\nTask: {$task->title}\n\nMit freundlichen Grüßen\nIhr SunnyBill Team";
                        
                        $result = $gmailService->sendEmail($mentionedUser->email, $subject, $body);
                        
                        if ($result['success']) {
                            $this->info('✓ @mention email sent successfully!');
                            $this->info('Message ID: ' . ($result['message_id'] ?? 'N/A'));
                        } else {
                            $this->error('❌ @mention email failed: ' . $result['error']);
                        }
                    } else {
                        $this->warn("⚠ Mentioned user not found: {$username}");
                    }
                }
            } else {
                $this->info('No mentions found in text');
            }
            
            $this->info('');
            $this->info('🎉 Task notification tests completed!');
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed with exception: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
        
        return 0;
    }
}