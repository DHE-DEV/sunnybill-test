<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'due_time',
        'labels',
        'order_index',
        'is_recurring',
        'recurring_pattern',
        'estimated_minutes',
        'actual_minutes',
        'task_type_id',
        'customer_id',
        'supplier_id',
        'solar_plant_id',
        'billing_id',
        'milestone_id',
        'assigned_to',
        'owner_id',
        'created_by',
        'parent_task_id',
        'completed_at',
        'task_number',
        'sort_order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'due_time' => 'datetime:H:i',
        'labels' => 'array',
        'is_recurring' => 'boolean',
        'estimated_minutes' => 'integer',
        'actual_minutes' => 'integer',
        'order_index' => 'integer',
        'sort_order' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Beziehungen
     */
    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    public function billing(): BelongsTo
    {
        return $this->belongsTo(Billing::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TaskNote::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function readStatuses(): HasMany
    {
        return $this->hasMany(TaskReadStatus::class);
    }

    /**
     * Scopes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeDueToday($query)
    {
        return $query->where('due_date', now()->toDateString())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent', 'blocker']);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeMainTasks($query)
    {
        return $query->whereNull('parent_task_id');
    }

    /**
     * Accessor & Mutator
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }
        
        return $this->due_date->isPast();
    }

    public function getIsDueTodayAttribute(): bool
    {
        if (!$this->due_date || in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }
        
        return $this->due_date->isToday();
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            'blocker' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'gray',
            'in_progress' => 'info',
            'waiting_external' => 'warning',
            'waiting_internal' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->status === 'completed') {
            return 100;
        }
        
        if ($this->subtasks()->count() === 0) {
            return match($this->status) {
                'open' => 0,
                'in_progress' => 50,
                'waiting_external' => 25,
                'waiting_internal' => 25,
                default => 0,
            };
        }
        
        $totalSubtasks = $this->subtasks()->count();
        $completedSubtasks = $this->subtasks()->completed()->count();
        
        return $totalSubtasks > 0 ? round(($completedSubtasks / $totalSubtasks) * 100) : 0;
    }

    /**
     * Prüft ob die Aufgabe ein Blocker ist
     */
    public function isBlocker(): bool
    {
        return $this->priority === 'blocker';
    }

    /**
     * Verfügbare Aufgabentypen
     */
    public static function getAvailableTaskTypes(): array
    {
        return [
            'bug' => 'Bug',
            'feature' => 'Feature',
            'improvement' => 'Verbesserung',
            'task' => 'Aufgabe',
            'support' => 'Support',
            'documentation' => 'Dokumentation',
            'maintenance' => 'Wartung',
            'meeting' => 'Meeting',
            'research' => 'Recherche',
            'testing' => 'Testing',
        ];
    }

    /**
     * Gibt die Prioritätsstufe als Nummer zurück (für Sortierung)
     */
    public function getPriorityWeight(): int
    {
        return match($this->priority) {
            'blocker' => 5,
            'urgent' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    /**
     * Methoden
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    public function canBeDeleted(): bool
    {
        return $this->subtasks()->count() === 0;
    }

    /**
     * Dupliziert die Aufgabe inklusive aller Unteraufgaben
     */
    public function duplicate(string $titleSuffix = ' (Kopie)'): Task
    {
        $duplicatedTask = $this->replicate([
            'task_number', // Neue Aufgabennummer wird automatisch generiert
            'completed_at',
            'created_at',
            'updated_at'
        ]);
        $duplicatedTask->title = $this->title . $titleSuffix;
        $duplicatedTask->status = 'open';
        $duplicatedTask->completed_at = null;
        $duplicatedTask->created_by = auth()->id();
        $duplicatedTask->save();
        
        // Subtasks auch duplizieren
        foreach ($this->subtasks as $subtask) {
            $duplicatedSubtask = $subtask->replicate([
                'task_number', // Neue Aufgabennummer wird automatisch generiert
                'completed_at',
                'created_at',
                'updated_at'
            ]);
            $duplicatedSubtask->parent_task_id = $duplicatedTask->id;
            $duplicatedSubtask->title = $subtask->title . $titleSuffix;
            $duplicatedSubtask->status = 'open';
            $duplicatedSubtask->completed_at = null;
            $duplicatedSubtask->created_by = auth()->id();
            $duplicatedSubtask->save();
        }
        
        return $duplicatedTask;
    }

    /**
     * Boot-Methode
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (!$task->created_by) {
                $task->created_by = auth()->id();
            }
            
            // Automatische Generierung der Aufgabennummer
            if (!$task->task_number) {
                $task->task_number = self::generateTaskNumber();
            }
        });
    }

    /**
     * Generiert eine eindeutige Aufgabennummer
     */
    private static function generateTaskNumber(): string
    {
        $year = date('Y');
        $prefix = "TASK-{$year}-";
        
        // Finde die höchste Nummer für das aktuelle Jahr (inklusive gelöschter Aufgaben)
        $lastTask = self::withTrashed()
            ->where('task_number', 'like', $prefix . '%')
            ->orderBy('task_number', 'desc')
            ->first();
        
        if ($lastTask) {
            // Extrahiere die Nummer aus der letzten Aufgabennummer
            $lastNumber = (int) str_replace($prefix, '', $lastTask->task_number);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        // Formatiere mit führenden Nullen (4 Stellen)
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
