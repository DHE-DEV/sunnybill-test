<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gmail_emails', function (Blueprint $table) {
            $table->id();
            $table->string('gmail_id')->unique(); // Gmail Message ID
            $table->string('thread_id')->nullable(); // Gmail Thread ID
            $table->string('subject')->nullable();
            $table->text('snippet')->nullable(); // Gmail snippet
            $table->json('from')->nullable(); // Sender information
            $table->json('to')->nullable(); // Recipients
            $table->json('cc')->nullable(); // CC recipients
            $table->json('bcc')->nullable(); // BCC recipients
            $table->longText('body_text')->nullable(); // Plain text body
            $table->longText('body_html')->nullable(); // HTML body
            $table->json('labels')->nullable(); // Gmail labels
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_important')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->boolean('is_sent')->default(false);
            $table->boolean('is_trash')->default(false);
            $table->boolean('is_spam')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->integer('attachment_count')->default(0);
            $table->json('attachments')->nullable(); // Attachment metadata
            $table->timestamp('gmail_date')->nullable(); // Original Gmail date
            $table->timestamp('received_at')->nullable(); // When we received it
            $table->timestamp('processed_at')->nullable(); // When we processed it
            $table->json('raw_headers')->nullable(); // Raw email headers
            $table->text('message_id_header')->nullable(); // Message-ID header
            $table->text('in_reply_to')->nullable(); // In-Reply-To header
            $table->text('references')->nullable(); // References header
            $table->integer('size_estimate')->nullable(); // Size in bytes
            $table->json('payload')->nullable(); // Full Gmail payload for debugging
            $table->timestamps();
            
            // Indexes
            $table->index('gmail_id');
            $table->index('thread_id');
            $table->index('is_read');
            $table->index('gmail_date');
            $table->index('received_at');
            $table->index(['is_read', 'gmail_date']);
            $table->index(['has_attachments', 'gmail_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmail_emails');
    }
};
