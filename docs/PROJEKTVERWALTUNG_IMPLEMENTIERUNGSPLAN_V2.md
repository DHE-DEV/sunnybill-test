# 🎯 Professionelle Projektverwaltung - Moderne Implementierung 2025

## 📋 Übersicht

Diese erweiterte Dokumentation beschreibt eine state-of-the-art Implementierung einer professionellen Projektverwaltung für das VoltMaster System unter Verwendung der neuesten Laravel 12.x und Filament 3.x Features. Die Lösung nutzt moderne Best Practices und aktuelle Framework-Funktionen.

---

## 🏗️ Moderne Datenbank-Struktur (Laravel 12.x)

### 1. Projects Migration mit UUID und modernen Features

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('project_number', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Moderne Enum-Definitionen
            $table->enum('type', [
                'solar_plant', 'internal', 'customer', 
                'development', 'maintenance', 'consulting'
            ])->default('solar_plant');
            
            $table->enum('status', [
                'planning', 'active', 'on_hold', 
                'completed', 'cancelled'
            ])->default('planning');
            
            $table->enum('priority', [
                'low', 'medium', 'high', 'urgent'
            ])->default('medium');
            
            // Datum-Management
            $table->date('start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            
            // Finanz-Management mit Präzision
            $table->decimal('budget', 15, 2)->nullable();
            $table->decimal('actual_costs', 15, 2)->default(0);
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            
            // UUID Foreign Keys für bessere Performance
            $table->uuid('customer_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->uuid('solar_plant_id')->nullable();
            $table->uuid('project_manager_id')->nullable();
            $table->uuid('created_by')->nullable();
            
            // JSON-Felder für flexible Datenstrukturen
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('metadata')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Moderne Index-Strategien
            $table->index(['status', 'priority']);
            $table->index(['start_date', 'planned_end_date']);
            $table->index(['project_manager_id', 'status']);
            $table->index(['customer_id', 'type']);
            
            // Foreign Key Constraints
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('solar_plant_id')->references('id')->on('solar_plants')->onDelete('set null');
            $table->foreign('project_manager_id')->references('users')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
```

### 2. Project Milestones mit erweiterten Features

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('parent_milestone_id')->nullable(); // Hierarchische Meilensteine
            
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->enum('type', [
                'planning', 'approval', 'implementation', 
                'testing', 'delivery', 'payment', 'review', 'milestone'
            ])->default('planning');
            
            $table->date('planned_date');
            $table->date('actual_date')->nullable();
            
            $table->enum('status', [
                'pending', 'in_progress', 'completed', 
                'delayed', 'cancelled', 'blocked'
            ])->default('pending');
            
            $table->uuid('responsible_user_id')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('deliverables')->nullable();
            
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->boolean('is_critical_path')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            
            $table->timestamps();
            
            // Constraints und Indizes
            $table->check('completion_percentage BETWEEN 0 AND 100');
            $table->index(['project_id', 'status']);
            $table->index(['planned_date', 'is_critical_path']);
            $table->index(['responsible_user_id', 'status']);
            
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('parent_milestone_id')->references('id')->on('project_milestones')->onDelete('set null');
            $table->foreign('responsible_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};
```

---

## 🎯 Moderne Laravel 12.x Models

### 1. Erweiterte Project Model mit neuen Features

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use App\Observers\ProjectObserver;

#[ObservedBy([ProjectObserver::class])]
class Project extends Model
{
    use HasUuids, SoftDeletes;

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
        'custom_fields',
        'metadata',
        'is_active'
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'planned_end_date' => 'date',
            'actual_end_date' => 'date',
            'budget' => 'decimal:2',
            'actual_costs' => 'decimal:2',
            'progress_percentage' => 'integer',
            'tags' => AsCollection::class,
            'custom_fields' => AsCollection::class,
            'metadata' => AsCollection::class,
            'is_active' => 'boolean',
        ];
    }

    // Moderne Relationship-Definitionen mit Chaperone
    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withDefault();
    }

    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)
            ->orderBy('planned_date')
            ->chaperone(); // Automatische Parent-Hydratisierung
    }

    public function criticalMilestones(): HasMany
    {
        return $this->milestones()
            ->where('is_critical_path', true)
            ->where('status', '!=', 'completed');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ProjectAppointment::class)
            ->orderBy('start_datetime')
            ->chaperone();
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'project_task')
            ->withTimestamps();
    }

    public function activeTasks(): BelongsToMany
    {
        return $this->tasks()->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Moderne Accessor Pattern
    protected function statusColor(): string
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

    protected function priorityColor(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'gray',
        };
    }

    protected function isOverdue(): bool
    {
        return $this->planned_end_date?->isPast() && 
               !in_array($this->status, ['completed', 'cancelled']);
    }

    protected function daysRemaining(): ?int
    {
        return $this->planned_end_date?->diffInDays(now(), false);
    }

    protected function completionRate(): float
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) return 0;
        
        $completedTasks = $this->tasks()
            ->where('status', 'completed')
            ->count();
            
        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    // Moderne Query Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->where('planned_end_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeByManager($query, $managerId)
    {
        return $query->where('project_manager_id', $managerId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Automatische Projekt-Nummer
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (!$project->project_number) {
                $project->project_number = self::generateProjectNumber();
            }
            
            if (!$project->created_by) {
                $project->created_by = auth()->id();
            }
        });

        // Automatische Fortschrittsberechnung
        static::saving(function ($project) {
            if ($project->isDirty('status') && 
                $project->status === 'completed' && 
                !$project->actual_end_date) {
                $project->actual_end_date = now()->format('Y-m-d');
                $project->progress_percentage = 100;
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

    // Team-Mitglieder über Tasks
    public function teamMembers()
    {
        return User::whereHas('tasks', function ($query) {
            $query->whereHas('projects', function ($projectQuery) {
                $projectQuery->where('id', $this->id);
            });
        })->distinct();
    }
}
```

---

## 🎨 Moderne Filament 4.x Resources

### 1. ProjectResource mit Schema-Pattern

```php
<?php

namespace App\Filament\Resources;

use App\Models\Project;
use App\Filament\Resources\ProjectResource\Schemas\ProjectFormSchema;
use App\Filament\Resources\ProjectResource\Schemas\ProjectTableSchema;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Projekte';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProjectFormSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectTableSchema::configure($table);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Projekt-Details')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\TextEntry::make('project_number')
                                        ->label('Projekt-Nr.')
                                        ->badge()
                                        ->color('primary'),
                                    Infolists\Components\TextEntry::make('name')
                                        ->label('Projektname')
                                        ->size(TextEntry\TextEntrySize::Large)
                                        ->weight(FontWeight::Bold),
                                    Infolists\Components\TextEntry::make('status')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'planning' => 'gray',
                                            'active' => 'success',
                                            'on_hold' => 'warning',
                                            'completed' => 'info',
                                            'cancelled' => 'danger',
                                        }),
                                    Infolists\Components\TextEntry::make('priority')
                                        ->badge()
                                        ->color(function (string $state): string {
                                            return match ($state) {
                                                'low' => 'gray',
                                                'medium' => 'info',
                                                'high' => 'warning',
                                                'urgent' => 'danger',
                                            };
                                        }),
                                ]),
                            Infolists\Components\ImageEntry::make('customer.logo')
                                ->label('Kunde')
                                ->circular()
                                ->size(80),
                        ])->from('lg'),
                    ]),
                
                Infolists\Components\Section::make('Zeitplan & Budget')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('start_date')
                                    ->label('Startdatum')
                                    ->date('d.m.Y')
                                    ->icon('heroicon-m-calendar'),
                                Infolists\Components\TextEntry::make('planned_end_date')
                                    ->label('Geplantes Ende')
                                    ->date('d.m.Y')
                                    ->icon('heroicon-m-calendar-days'),
                                Infolists\Components\TextEntry::make('progress_percentage')
                                    ->label('Fortschritt')
                                    ->suffix('%')
                                    ->icon('heroicon-m-chart-bar'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('budget')
                                    ->label('Budget')
                                    ->money('EUR')
                                    ->icon('heroicon-m-currency-euro'),
                                Infolists\Components\TextEntry::make('actual_costs')
                                    ->label('Ist-Kosten')
                                    ->money('EUR')
                                    ->color(fn ($record) => $record->actual_costs > $record->budget ? 'danger' : 'success'),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProjectResource\RelationManagers\MilestonesRelationManager::class,
            ProjectResource\RelationManagers\AppointmentsRelationManager::class,
            ProjectResource\RelationManagers\TasksRelationManager::class,
            ProjectResource\RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'view' => Pages\ViewProject::route('/{record}'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ProjectResource\Widgets\ProjectOverviewWidget::class,
            ProjectResource\Widgets\ProjectTimelineWidget::class,
        ];
    }
}
```

### 2. Moderne Form Schema Klasse

```php
<?php

namespace App\Filament\Resources\ProjectResource\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectFormSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Projekt-Grunddaten')
                    ->description('Grundlegende Informationen zum Projekt')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Projektname')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $context, $state, $set) {
                                        if ($context === 'create') {
                                            $set('project_number', 'PRJ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT));
                                        }
                                    }),
                                
                                TextInput::make('project_number')
                                    ->label('Projekt-Nr.')
                                    ->disabled()
                                    ->dehydrated()
                                    ->unique(Project::class, 'project_number', ignoreRecord: true),
                            ]),
                        
                        Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->label('Projekttyp')
                                    ->options([
                                        'solar_plant' => 'Solaranlage',
                                        'internal' => 'Intern',
                                        'customer' => 'Kundenprojekt',
                                        'development' => 'Entwicklung',
                                        'maintenance' => 'Wartung',
                                        'consulting' => 'Beratung',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->searchable(),
                                
                                Select::make('priority')
                                    ->label('Priorität')
                                    ->options([
                                        'low' => 'Niedrig',
                                        'medium' => 'Mittel',
                                        'high' => 'Hoch',
                                        'urgent' => 'Dringend',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('medium'),
                                
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'planning' => 'Planung',
                                        'active' => 'Aktiv',
                                        'on_hold' => 'Pausiert',
                                        'completed' => 'Abgeschlossen',
                                        'cancelled' => 'Abgebrochen',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('planning'),
                            ]),
                    ]),

                Section::make('Zeitplan & Budget')
                    ->description('Zeitlicher Rahmen und finanzielle Planung')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Startdatum')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d.m.Y'),
                                
                                DatePicker::make('planned_end_date')
                                    ->label('Geplantes Enddatum')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d.m.Y')
                                    ->after('start_date'),
                                
                                DatePicker::make('actual_end_date')
                                    ->label('Tatsächliches Enddatum')
                                    ->native(false)
                                    ->displayFormat('d.m.Y')
                                    ->visible(fn ($get) => in_array($get('status'), ['completed', 'cancelled'])),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextInput::make('budget')
                                    ->label('Budget')
                                    ->numeric()
                                    ->prefix('€')
                                    ->step(0.01)
                                    ->inputMode('decimal'),
                                
                                TextInput::make('progress_percentage')
                                    ->label('Fortschritt (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(5)
                                    ->default(0),
                            ]),
                    ]),

                Section::make('Zuordnungen')
                    ->description('Personen und Ressourcen-Zuordnungen')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('customer_id')
                                    ->label('Kunde')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('email')
                                            ->email()
                                            ->required(),
                                        TextInput::make('phone')
                                            ->tel()
                                            ->required(),
                                    ]),
                                
                                Select::make('project_manager_id')
                                    ->label('Projektleiter')
                                    ->relationship('projectManager', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                Select::make('supplier_id')
                                    ->label('Lieferant')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload(),
                                
                                Select::make('solar_plant_id')
                                    ->label('Solaranlage')
                                    ->relationship('solarPlant', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Section::make('Zusätzliche Informationen')
                    ->description('Tags und erweiterte Optionen')
                    ->schema([
                        TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Tag hinzufügen...')
                            ->suggestions([
                                'wichtig', 'dringend', 'review', 'dokumentation',
                                'testing', 'deployment', 'wartung'
                            ]),
                        
                        Toggle::make('is_active')
                            ->label('Projekt aktiv')
                            ->default(true)
                            ->helperText('Deaktivierte Projekte werden in Listen ausgeblendet'),
                    ])
                    ->collapsible(),
            ]);
    }
}
```

### 3. Moderne Table Schema Klasse

```php
<?php

namespace App\Filament\Resources\ProjectResource\Schemas;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ProgressColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class ProjectTableSchema
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('project_number')
                            ->label('Projekt-Nr.')
                            ->searchable()
                            ->sortable()
                            ->weight('bold')
                            ->color('primary'),
                        
                        TextColumn::make('name')
                            ->label('Projektname')
                            ->searchable()
                            ->sortable()
                            ->limit(30)
                            ->tooltip(function (TextColumn $column): ?string {
                                $state = $column->getState();
                                return strlen($state) > 30 ? $state : null;
                            }),
                    ]),
                    
                    Stack::make([
                        BadgeColumn::make('status')
                            ->label('Status')
                            ->colors([
                                'gray' => 'planning',
                                'success' => 'active',
                                'warning' => 'on_hold',
                                'info' => 'completed',
                                'danger' => 'cancelled',
                            ])
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'planning' => 'Planung',
                                'active' => 'Aktiv',
                                'on_hold' => 'Pausiert',
                                'completed' => 'Fertig',
                                'cancelled' => 'Abgebrochen',
                                default => $state,
                            }),
                        
                        BadgeColumn::make('priority')
                            ->label('Priorität')
                            ->colors([
                                'gray' => 'low',
                                'info' => 'medium',
                                'warning' => 'high',
                                'danger' => 'urgent',
                            ])
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'low' => 'Niedrig',
                                'medium' => 'Mittel',
                                'high' => 'Hoch',
                                'urgent' => 'Dringend',
                                default => $state,
                            }),
                    ])->alignment('end'),
                ]),
                
                ProgressColumn::make('progress_percentage')
                    ->label('Fortschritt')
                    ->color(function ($state): string {
                        return match (true) {
                            $state < 30 => 'danger',
                            $state < 70 => 'warning',
                            default => 'success',
                        };
                    }),
                
                TextColumn::make('planned_end_date')
                    ->label('Enddatum')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(function ($record): string {
                        if (!$record->planned_end_date) return 'gray';
                        
                        $isOverdue = $record->planned_end_date->isPast() && 
                                   !in_array($record->status, ['completed', 'cancelled']);
                        
                        return $isOverdue ? 'danger' : 'gray';
                    }),
                
                TextColumn::make('projectManager.name')
                    ->label('Projektleiter')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 20 ? $state : null;
                    }),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'planning' => 'Planung',
                        'active' => 'Aktiv',
                        'on_hold' => 'Pausiert',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                    ])
                    ->multiple(),
                
                SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        'low' => 'Niedrig',
                        'medium' => 'Mittel',
                        'high' => 'Hoch',
                        'urgent' => 'Dringend',
                    ])
                    ->multiple(),
                
                SelectFilter::make('type')
                    ->label('Projekttyp')
                    ->options([
                        'solar_plant' => 'Solaranlage',
                        'internal' => 'Intern',
                        'customer' => 'Kundenprojekt',
                        'development' => 'Entwicklung',
                        'maintenance' => 'Wartung',
                        'consulting' => 'Beratung',
                    ])
                    ->multiple(),
                
                SelectFilter::make('project_manager_id')
                    ->label('Projektleiter')
                    ->relationship('projectManager', 'name')
                    ->searchable()
                    ->preload(),
                
                Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('planned_end_date', '<', now())
                              ->whereNotIn('status', ['completed', 'cancelled'])
                    )
                    ->toggle(),
                
                Filter::make('active_projects')
                    ->label('Nur aktive Projekte')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('is_active', true)
                    )
                    ->default(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Anzeigen')
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->label('Bearbeiten')
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Löschen'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh alle 30 Sekunden
    }
}
```

---

## 🔧 Relation Managers

### 1. MilestonesRelationManager

```php
<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MilestonesRelationManager extends RelationManager
{
    protected static string $relationship = 'milestones';
    protected static ?string $recordTitleAttribute = 'title';
    protected static ?string $title = 'Meilensteine';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Meilenstein-Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Titel')
                                    ->required()
                                    ->maxLength(255),
                                
                                Forms\Components\Select::make('type')
                                    ->label('Typ')
                                    ->options([
                                        'planning' => 'Planung',
                                        'approval' => 'Genehmigung',
                                        'implementation' => 'Umsetzung',
                                        'testing' => 'Test',
                                        'delivery' => 'Lieferung',
                                        'payment' => 'Zahlung',
                                        'review' => 'Review',
                                        'milestone' => 'Meilenstein',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('planned_date')
                                    ->label('Geplantes Datum')
                                    ->required()
                                    ->native(false),
                                
                                Forms\Components\DatePicker::make('actual_date')
                                    ->label('Tatsächliches Datum')
                                    ->native(false),
                                
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Ausstehend',
                                        'in_progress' => 'In Bearbeitung',
                                        'completed' => 'Abgeschlossen',
                                        'delayed' => 'Verzögert',
                                        'cancelled' => 'Abgebrochen',
                                        'blocked' => 'Blockiert',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('pending'),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('responsible_user_id')
                                    ->label('Verantwortlich')
                                    ->relationship('responsibleUser', 'name')
                                    ->searchable()
                                    ->preload(),
                                
                                Forms\Components\Toggle::make('is_critical_path')
                                    ->label('Kritischer Pfad')
                                    ->default(false),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('estimated_hours')
                                    ->label('Geschätzte Stunden')
                                    ->numeric()
                                    ->step(0.25)
                                    ->suffix('h'),
                                
                                Forms\Components\TextInput::make('actual_hours')
                                    ->label('Tatsächliche Stunden')
                                    ->numeric()
                                    ->step(0.25)
                                    ->suffix('h'),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'gray' => 'planning',
                        'info' => 'approval',
                        'warning' => 'implementation',
                        'success' => 'testing',
                        'primary' => 'delivery',
                        'danger' => 'payment',
                    ]),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'delayed',
                        'secondary' => 'cancelled',
                        'info' => 'blocked',
                    ]),
                
                Tables\Columns\TextColumn::make('planned_date')
                    ->label('Geplant')
                    ->date('d.m.Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('actual_date')
                    ->label('Tatsächlich')
                    ->date('d.m.Y')
                    ->placeholder('—'),
                
                Tables\Columns\IconColumn::make('is_critical_path')
                    ->label('Kritisch')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('responsibleUser.name')
                    ->label('Verantwortlich')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'delayed' => 'Verzögert',
                        'cancelled' => 'Abgebrochen',
                        'blocked' => 'Blockiert',
                    ]),
                
                Tables\Filters\Filter::make('critical_path')
                    ->label('Nur kritischer Pfad')
                    ->query(fn ($query) => $query->where('is_critical_path', true))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Meilenstein hinzufügen'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('planned_date', 'asc');
    }
}
```

---

## 📊 Dashboard Widgets

### 1. ProjectOverviewWidget

```php
<?php

namespace App\Filament\Resources\ProjectResource\Widgets;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $overdueProjects = Project::overdue()->count();
        
        $totalBudget = Project::sum('budget');
        $totalActualCosts = Project::sum('actual_costs');
        $budgetVariance = $totalBudget - $totalActualCosts;
        
        return [
            Stat::make('Projekte Gesamt', $totalProjects)
                ->description('Alle Projekte im System')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),
            
            Stat::make('Aktive Projekte', $activeProjects)
                ->description($activeProjects > 0 ? 'Projekte in Bearbeitung' : 'Keine aktiven Projekte')
                ->descriptionIcon('heroicon-m-play')
                ->color('success'),
            
            Stat::make('Abgeschlossen', $completedProjects)
                ->description('Erfolgreich beendete Projekte')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),
            
            Stat::make('Überfällig', $overdueProjects)
                ->description($overdueProjects > 0 ? 'Projekte mit überfälligem Enddatum' : 'Alle Projekte im Zeitplan')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($overdueProjects > 0 ? 'danger' : 'success'),
            
            Stat::make('Budget Total', number_format($totalBudget, 2, ',', '.') . ' €')
                ->description('Gesamtbudget aller Projekte')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('warning'),
            
            Stat::make('Budget-Abweichung', 
                ($budgetVariance >= 0 ? '+' : '') . number_format($budgetVariance, 2, ',', '.') . ' €'
            )
                ->description($budgetVariance >= 0 ? 'Budget-Überschuss' : 'Budget-Überschreitung')
                ->descriptionIcon($budgetVariance >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($budgetVariance >= 0 ? 'success' : 'danger'),
        ];
    }
}
```

---

## 🔄 Services und Business Logic

### 1. ProjectService

```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Events\ProjectStatusChanged;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectService
{
    public function createProject(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $project = Project::create($data);
            
            // Standard-Meilensteine erstellen
            $this->createDefaultMilestones($project);
            
            // Event auslösen
            event(new ProjectStatusChanged($project, null, $project->status));
            
            return $project;
        });
    }

    public function updateProjectStatus(Project $project, string $newStatus): Project
    {
        $oldStatus = $project->status;
        
        return DB::transaction(function () use ($project, $newStatus, $oldStatus) {
            $project->update(['status' => $newStatus]);
            
            // Bei Abschluss automatisch Enddatum setzen
            if ($newStatus === 'completed' && !$project->actual_end_date) {
                $project->update([
                    'actual_end_date' => now(),
                    'progress_percentage' => 100
                ]);
            }
            
            // Event auslösen
            event(new ProjectStatusChanged($project, $oldStatus, $newStatus));
            
            return $project->fresh();
        });
    }

    public function calculateProjectProgress(Project $project): float
    {
        $totalMilestones = $project->milestones()->count();
        
        if ($totalMilestones === 0) {
            return 0;
        }
        
        $completedMilestones = $project->milestones()
            ->where('status', 'completed')
            ->count();
            
        return round(($completedMilestones / $totalMilestones) * 100, 2);
    }

    public function getOverdueProjects(): \Illuminate\Database\Eloquent\Collection
    {
        return Project::overdue()
            ->with(['projectManager', 'customer'])
            ->get();
    }

    public function getCriticalPathMilestones(Project $project): \Illuminate\Database\Eloquent\Collection
    {
        return $project->milestones()
            ->where('is_critical_path', true)
            ->orderBy('planned_date')
            ->get();
    }

    public function createDefaultMilestones(Project $project): void
    {
        if ($project->type === 'solar_plant') {
            $this->createSolarPlantMilestones($project);
        } else {
            $this->createGenericMilestones($project);
        }
    }

    private function createSolarPlantMilestones(Project $project): void
    {
        $milestones = [
            [
                'title' => 'Projektplanung',
                'type' => 'planning',
                'planned_date' => $project->start_date,
                'is_critical_path' => true,
                'sort_order' => 1,
                'estimated_hours' => 16,
            ],
            [
                'title' => 'Genehmigungen',
                'type' => 'approval',
                'planned_date' => $project->start_date?->addDays(14),
                'is_critical_path' => true,
                'sort_order' => 2,
                'estimated_hours' => 8,
            ],
            [
                'title' => 'Installation',
                'type' => 'implementation',
                'planned_date' => $project->start_date?->addDays(30),
                'is_critical_path' => true,
                'sort_order' => 3,
                'estimated_hours' => 40,
            ],
            [
                'title' => 'Inbetriebnahme',
                'type' => 'testing',
                'planned_date' => $project->start_date?->addDays(45),
                'is_critical_path' => true,
                'sort_order' => 4,
                'estimated_hours' => 8,
            ],
            [
                'title' => 'Projektabschluss',
                'type' => 'delivery',
                'planned_date' => $project->planned_end_date,
                'is_critical_path' => true,
                'sort_order' => 5,
                'estimated_hours' => 4,
            ],
        ];

        foreach ($milestones as $milestone) {
            $project->milestones()->create($milestone);
        }
    }

    private function createGenericMilestones(Project $project): void
    {
        $duration = $project->start_date?->diffInDays($project->planned_end_date) ?? 30;
        $quarterPoints = [
            floor($duration * 0.25),
            floor($duration * 0.5),
            floor($duration * 0.75),
            $duration
        ];

        $milestoneNames = [
            'Projektstart & Planung',
            'Zwischenergebnis 1',
            'Zwischenergebnis 2',
            'Projektabschluss'
        ];

        foreach ($quarterPoints as $index => $days) {
            $project->milestones()->create([
                'title' => $milestoneNames[$index],
                'type' => $index === 0 ? 'planning' : ($index === 3 ? 'delivery' : 'milestone'),
                'planned_date' => $project->start_date?->addDays($days),
                'is_critical_path' => true,
                'sort_order' => $index + 1,
                'estimated_hours' => 8,
            ]);
        }
    }
}
```

---

## 📋 Implementierungs-Checkliste

### Phase 1: Datenbank & Models ✅
- [x] Moderne Migration-Dateien mit UUID
- [x] Erweiterte Model-Definitionen
- [x] Relationship-Definitionen mit Chaperone
- [x] Accessor & Mutator Patterns
- [x] Observer-Pattern für Business Logic

### Phase 2: Filament Resources ✅
- [x] Schema-Pattern für bessere Code-Organisation
- [x] Moderne UI-Components
- [x] Relation Managers
- [x] Erweiterte Filter & Actions
- [x] Dashboard Widgets

### Phase 3: Business Logic ✅
- [x] Service Layer Implementierung
- [x] Event System
- [x] Automatische Meilenstein-Erstellung
- [x] Fortschrittsberechnung
- [x] Überfälligkeits-Management

### Phase 4: Performance & UX
- [ ] Eager Loading Optimierung
- [ ] Caching Strategien
- [ ] Bulk-Operations
- [ ] Export-Funktionen
- [ ] Mobile Responsiveness

### Phase 5: Testing & Dokumentation
- [ ] Unit Tests
- [ ] Feature Tests
- [ ] API Documentation
- [ ] Benutzer-Handbuch
- [ ] Deployment Guide

---

## 🚀 Deployment & Maintenance

### Artisan Commands für Setup

```bash
# Migrationen ausführen
php artisan migrate

# Filament Assets publizieren
php artisan filament:upgrade

# Cache optimieren
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue Workers starten (für Events)
php artisan queue:work --daemon
```

### Monitoring & Performance

```php
// config/filament.php
'database_notifications' => [
    'enabled' => true,
    'polling_interval' => '30s',
],

'broadcasting' => [
    'echo' => [
        'broadcaster' => 'pusher',
        'key' => env('PUSHER_APP_KEY'),
        'cluster' => env('PUSHER_APP_CLUSTER'),
        'forceTLS' => true,
    ],
],
```

---

## 📈 Erweiterte Features (Zukunft)

### 1. Gantt-Chart Visualisierung
- Timeline-Ansicht für Projekte
- Abhängigkeiten zwischen Meilensteinen
- Drag & Drop Terminplanung

### 2. Ressourcen-Management
- Kapazitätsplanung
- Skill-Matrix Integration
- Workload-Balance

### 3. Reporting & Analytics
- Projekt-Performance Dashboards
- Budget-Tracking
- Team-Produktivität Metriken

### 4. Integration & APIs
- Externe Kalender-Synchronisation
- ERP-System Integration
- Mobile App Unterstützung

---

## 🔧 Technische Spezifikationen

- **Framework**: Laravel 12.x (Neueste Version)
- **UI Framework**: Filament 3.x mit modernen Components
- **Datenbank**: MySQL 8.0+ / PostgreSQL 15+
- **Caching**: Redis für Session & Cache Management
- **Queue System**: Redis/Database für Background Jobs
- **File Storage**: S3-kompatible Speicher-Lösung
- **Monitoring**: Laravel Telescope für Development
- **Testing**: Pest für moderne Test-Syntax

---

Diese erweiterte Implementierung nutzt die neuesten Features von Laravel 12.x und Filament 3.x und bietet eine professionelle, skalierbare Projektverwaltungs-Lösung mit modernen Development-Patterns und Best Practices.
