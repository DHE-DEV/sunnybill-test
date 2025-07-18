<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaskNote extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'content',
        'mentioned_users',
    ];

    protected $casts = [
        'mentioned_users' => 'array',
    ];

    /**
     * Get the task that owns the note.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user that created the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the mentioned users.
     */
    public function mentionedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_note_mentions', 'task_note_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Extract mentioned usernames from content
     */
    public function extractMentionedUsernames(): array
    {
        preg_match_all('/@(\w+)/', $this->content, $matches);
        return array_unique($matches[1]);
    }

    /**
     * Get mentioned users by parsing content
     */
    public function getMentionedUsersFromContent()
    {
        $usernames = $this->extractMentionedUsernames();
        
        if (empty($usernames)) {
            return collect();
        }

        return User::whereIn('name', $usernames)
            ->orWhere(function ($query) use ($usernames) {
                foreach ($usernames as $username) {
                    $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($username) . '%']);
                }
            })
            ->get();
    }

    /**
     * Boot-Methode für automatische History-Protokollierung
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($taskNote) {
            // Protokolliere das Hinzufügen einer Notiz
            TaskHistory::create([
                'task_id' => $taskNote->task_id,
                'user_id' => $taskNote->user_id,
                'action' => 'note_added',
                'description' => 'Notiz hinzugefügt: ' . \Str::limit($taskNote->content, 100),
            ]);
        });

        static::updated(function ($taskNote) {
            // Protokolliere das Bearbeiten einer Notiz
            TaskHistory::create([
                'task_id' => $taskNote->task_id,
                'user_id' => auth()->id() ?? $taskNote->user_id,
                'action' => 'note_updated',
                'description' => 'Notiz bearbeitet: ' . \Str::limit($taskNote->content, 100),
            ]);
        });

        static::deleted(function ($taskNote) {
            // Protokolliere das Löschen einer Notiz
            TaskHistory::create([
                'task_id' => $taskNote->task_id,
                'user_id' => auth()->id() ?? $taskNote->user_id,
                'action' => 'note_deleted',
                'description' => 'Notiz gelöscht: ' . \Str::limit($taskNote->content, 100),
            ]);
        });
    }
}
