<?php

namespace App\Events;

use App\Models\GmailEmail;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewGmailReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GmailEmail $email;
    public array $users;

    /**
     * Create a new event instance.
     */
    public function __construct(GmailEmail $email, array $users)
    {
        $this->email = $email;
        $this->users = $users;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // Erstelle private Channels für jeden berechtigten Benutzer
        foreach ($this->users as $user) {
            $channels[] = new PrivateChannel('gmail-notifications.' . $user['id']);
        }
        
        // Zusätzlich ein allgemeiner Channel für Admins
        $channels[] = new PrivateChannel('gmail-notifications.admin');
        
        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Extrahiere Sender-Information
        $sender = 'Unbekannt';
        if (!empty($this->email->from)) {
            $firstSender = is_array($this->email->from) ? $this->email->from[0] : $this->email->from;
            if (is_array($firstSender)) {
                $sender = $firstSender['name'] ?: $firstSender['email'];
            } else {
                $sender = $firstSender;
            }
        }

        return [
            'email' => [
                'id' => $this->email->id,
                'gmail_id' => $this->email->gmail_id,
                'subject' => $this->email->subject,
                'snippet' => $this->email->snippet,
                'sender' => $sender,
                'from' => $this->email->from,
                'has_attachments' => $this->email->has_attachments,
                'is_important' => $this->email->is_important,
                'is_starred' => $this->email->is_starred,
                'received_at' => $this->email->received_at?->toISOString(),
                'url' => route('filament.admin.resources.gmail-emails.view', $this->email->id),
            ],
            'notification' => [
                'title' => 'Neue Gmail E-Mail',
                'message' => "Neue E-Mail von {$sender}: {$this->email->subject}",
                'timestamp' => now()->toISOString(),
                'user_count' => count($this->users),
            ]
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'gmail.new-email';
    }
}
