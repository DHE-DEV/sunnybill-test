<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskHistory extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'description',
    ];

    /**
     * Get the task that owns the history entry.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user that made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a history entry for task creation.
     */
    public static function logTaskCreation(Task $task, $userId): void
    {
        self::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'action' => 'created',
            'description' => 'Aufgabe wurde erstellt',
        ]);
    }

    /**
     * Create a history entry for field changes.
     */
    public static function logFieldChange(Task $task, $userId, $fieldName, $oldValue, $newValue): void
    {
        self::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'action' => 'field_changed',
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => "Feld '{$fieldName}' wurde geändert",
        ]);
    }

    /**
     * Create a history entry for note addition.
     */
    public static function logNoteAdded(Task $task, $userId): void
    {
        self::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'action' => 'note_added',
            'description' => 'Notiz wurde hinzugefügt',
        ]);
    }
}
