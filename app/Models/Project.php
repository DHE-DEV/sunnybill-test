<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class Project extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_number',
        'name',
        'description',
        'type',
        'status',
        'priority',
        'start_date',
        'planned_end_date',
        'actual_end_date',
        'budget',
        'actual_costs',
        'progress_percentage',
        'customer_id',
        'supplier_id',
        'solar_plant_id',
        'project_manager_id',
        'created_by',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_costs' => 'decimal:2',
        'progress_percentage' => 'integer',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
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

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ProjectAppointment::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'project_task');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'planning' => 'gray',
            'active' => 'success',
            'on_hold' => 'warning',
            'completed' => 'info',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'gray',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->planned_end_date && 
               $this->planned_end_date->isPast() && 
               $this->status !== 'completed';
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->planned_end_date) return null;
        return now()->diffInDays($this->planned_end_date, false);
    }

    public function getOpenMilestonesAttribute()
    {
        return $this->milestones()
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('planned_date')
            ->get();
    }

    public function getCompletedMilestonesAttribute()
    {
        return $this->milestones()
            ->where('status', 'completed')
            ->orderBy('actual_date', 'desc')
            ->get();
    }

    public function getUpcomingAppointmentsAttribute()
    {
        return $this->appointments()
            ->where('start_datetime', '>=', now())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('start_datetime')
            ->limit(5)
            ->get();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (!$project->project_number) {
                $project->project_number = self::generateProjectNumber();
            }
        });
    }

    private static function generateProjectNumber(): string
    {
        $year = date('Y');
        $prefix = "PRJ-{$year}-";
        
        $lastProject = self::withTrashed()
            ->where('project_number', 'like', $prefix . '%')
            ->orderBy('project_number', 'desc')
            ->first();
        
        $lastNumber = $lastProject ? 
            (int) str_replace($prefix, '', $lastProject->project_number) : 0;
        
        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }
}
