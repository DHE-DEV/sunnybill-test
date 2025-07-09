<?php

namespace App\Console\Commands;

use App\Models\GmailEmail;
use Illuminate\Console\Command;

class ListGmailEmails extends Command
{
    protected $signature = 'list:gmail-emails {--limit=10}';
    protected $description = 'List Gmail emails in the database';

    public function handle()
    {
        $limit = $this->option('limit');
        $emails = GmailEmail::orderBy('created_at', 'desc')->limit($limit)->get();

        if ($emails->isEmpty()) {
            $this->info('Keine Gmail-E-Mails in der Datenbank gefunden');
            return 0;
        }

        $this->info("=== Gmail E-Mails (Limit: {$limit}) ===");
        
        foreach ($emails as $email) {
            $this->info("ID: {$email->id}");
            $this->info("Subject: {$email->subject}");
            $this->info("Gmail ID: {$email->gmail_id}");
            $this->info("Hat Anhänge: " . ($email->has_attachments ? 'Ja' : 'Nein'));
            $this->info("Anzahl Anhänge: {$email->attachment_count}");
            $this->info("---");
        }

        return 0;
    }
}