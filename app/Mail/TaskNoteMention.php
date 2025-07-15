<?php

namespace App\Mail;

use App\Models\TaskNote;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskNoteMention extends Mailable
{
    use Queueable, SerializesModels;

    public TaskNote $note;
    public User $mentionedUser;

    /**
     * Create a new message instance.
     */
    public function __construct(TaskNote $note, User $mentionedUser)
    {
        $this->note = $note;
        $this->mentionedUser = $mentionedUser;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sie wurden in einer Aufgaben-Notiz erwähnt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.task-note-mention',
            with: [
                'note' => $this->note,
                'mentionedUser' => $this->mentionedUser,
                'task' => $this->note->task,
                'author' => $this->note->user,
                'taskUrl' => $this->getTaskUrl(),
            ],
        );
    }

    /**
     * Get the task URL for the email
     */
    private function getTaskUrl(): string
    {
        $baseUrl = config('app.url');
        return $baseUrl . '/admin/tasks?openNotes=' . $this->note->task_id;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}