<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskReadStatus extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'notes_read_at',
        'history_read_at',
    ];

    protected $casts = [
        'notes_read_at' => 'datetime',
        'history_read_at' => 'datetime',
    ];

    /**
     * Get the task that owns the read status.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user that owns the read status.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notes as read for a specific user and task.
     */
    public static function markNotesAsRead(Task $task, $userId): void
    {
        self::updateOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $userId,
            ],
            [
                'notes_read_at' => now(),
            ]
        );
    }

    /**
     * Mark history as read for a specific user and task.
     */
    public static function markHistoryAsRead(Task $task, $userId): void
    {
        self::updateOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $userId,
            ],
            [
                'history_read_at' => now(),
            ]
        );
    }

    /**
     * Mark both notes and history as read for a specific user and task.
     */
    public static function markAllAsRead(Task $task, $userId): void
    {
        self::updateOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $userId,
            ],
            [
                'notes_read_at' => now(),
                'history_read_at' => now(),
            ]
        );
    }

    /**
     * Check if there are unread notes for a user and task.
     */
    public static function hasUnreadNotes(Task $task, $userId): bool
    {
        $readStatus = self::where('task_id', $task->id)
            ->where('user_id', $userId)
            ->first();

        if (!$readStatus || !$readStatus->notes_read_at) {
            return $task->notes()->exists();
        }

        return $task->notes()
            ->where('created_at', '>', $readStatus->notes_read_at)
            ->exists();
    }

    /**
     * Check if there are unread history entries for a user and task.
     */
    public static function hasUnreadHistory(Task $task, $userId): bool
    {
        $readStatus = self::where('task_id', $task->id)
            ->where('user_id', $userId)
            ->first();

        if (!$readStatus || !$readStatus->history_read_at) {
            return $task->history()->exists();
        }

        return $task->history()
            ->where('created_at', '>', $readStatus->history_read_at)
            ->exists();
    }
}
