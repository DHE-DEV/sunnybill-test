# 🎯 Professionelle Projektverwaltung - Implementierungsplan

## 📋 Übersicht

Diese Dokumentation beschreibt den vollständigen Plan zur Implementierung einer professionellen Projektverwaltung für das SunnyBill System. Die Lösung integriert sich nahtlos in die bestehende Aufgabenverwaltung und erweitert sie um Projekte, Meilensteine und Termine.

---

## 🏗️ Datenbank-Struktur

### 1. Projects Tabelle

```sql
CREATE TABLE projects (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_number VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('solar_plant', 'internal', 'customer', 'development', 'maintenance') DEFAULT 'solar_plant',
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    start_date DATE,
    planned_end_date DATE,
    actual_end_date DATE,
    budget DECIMAL(15,2),
    actual_costs DECIMAL(15,2),
    progress_percentage INTEGER DEFAULT 0,
    customer_id UUID REFERENCES customers(id),
    supplier_id UUID REFERENCES suppliers(id),
    solar_plant_id UUID REFERENCES solar_plants(id),
    project_manager_id UUID REFERENCES users(id),
    created_by UUID REFERENCES users(id),
    tags JSON,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_projects_priority ON projects(priority);
CREATE INDEX idx_projects_dates ON projects(start_date, planned_end_date);
CREATE INDEX idx_projects_manager ON projects(project_manager_id);
```

### 2. Project Milestones Tabelle

```sql
CREATE TABLE project_milestones (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('planning', 'approval', 'implementation', 'testing', 'delivery', 'payment', 'review') DEFAULT 'planning',
    planned_date DATE NOT NULL,
    actual_date DATE,
    status ENUM('pending', 'in_progress', 'completed', 'delayed', 'cancelled') DEFAULT 'pending',
    responsible_user_id UUID REFERENCES users(id),
    dependencies JSON,
    completion_percentage INTEGER DEFAULT 0,
    is_critical_path BOOLEAN DEFAULT false,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT check_completion CHECK (completion_percentage BETWEEN 0 AND 100)
);

CREATE INDEX idx_milestones_project ON project_milestones(project_id);
CREATE INDEX idx_milestones_status ON project_milestones(status);
CREATE INDEX idx_milestones_date ON project_milestones(planned_date);
```

### 3. Project Appointments Tabelle

```sql
CREATE TABLE project_appointments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    type ENUM('meeting', 'deadline', 'review', 'milestone_check', 'inspection', 'training') DEFAULT 'meeting',
    start_datetime TIMESTAMP NOT NULL,
    end_datetime TIMESTAMP,
    location VARCHAR(255),
    attendees JSON,
    reminder_minutes INTEGER DEFAULT 60,
    is_recurring BOOLEAN DEFAULT false,
    recurring_pattern JSON,
    status ENUM('scheduled', 'confirmed', 'cancelled', 'completed') DEFAULT 'scheduled',
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_appointments_project ON project_appointments(project_id);
CREATE INDEX idx_appointments_datetime ON project_appointments(start_datetime);
CREATE INDEX idx_appointments_status ON project_appointments(status);
```

### 4. Project-Task Verknüpfungstabelle

```sql
CREATE TABLE project_task (
    project_id UUID REFERENCES projects(id) ON DELETE CASCADE,
    task_id UUID REFERENCES tasks(id) ON DELETE CASCADE,
    PRIMARY KEY (project_id, task_id)
);

CREATE INDEX idx_project_task_project ON project_task(project_id);
CREATE INDEX idx_project_task_task ON project_task(task_id);
```

---

## 🎯 Laravel Models

### 1. Project Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

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
        'is_active'
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
```

### 2. ProjectMilestone Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'description',
        'type',
        'planned_date',
        'actual_date',
        'status',
        'responsible_user_id',
        'dependencies',
        'completion_percentage',
        'is_critical_path',
        'sort_order'
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'completion_percentage' => 'integer',
        'is_critical_path' => 'boolean',
        'dependencies' => 'array',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'in_progress' => 'info',
            'completed' => 'success',
            'delayed' => 'warning',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->planned_date->isPast() && $this->status !== 'completed';
    }

    public function getDaysRemainingAttribute(): int
    {
        return now()->diffInDays($this->planned_date, false);
    }
}
```

---

## 🎨 Filament Resources

### 1. ProjectResource

```php
<?php

namespace App\Filament\Resources;

use App\Models\Project;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Resources\Form;
use Filament\Resources\Table;
use Filament\Resources\Pages\Page;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Projekte';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Projekt-Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->rows(3),
                    Forms\Components\Select::make('type')
                        ->options([
                            'solar_plant' => 'Solaranlage',
                            'internal' => 'Intern',
                            'customer' => 'Kundenprojekt',
                            'development' => 'Entwicklung',
                            'maintenance' => 'Wartung',
                        ])
                        ->required(),
                    Forms\Components\Select::make('priority')
                        ->options([
                            'low' => 'Niedrig',
                            'medium' => 'Mittel',
                            'high' => 'Hoch',
                            'urgent' => 'Dringend',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->options([
                            'planning' => 'Planung',
                            'active' => 'Aktiv',
                            'on_hold' => 'Pausiert',
                            'completed' => 'Abgeschlossen',
                            'cancelled' => 'Abgebrochen',
                        ])
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('Zeitplan & Budget')
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->required(),
                    Forms\Components\DatePicker::make('planned_end_date')
                        ->required(),
                    Forms\Components\TextInput::make('budget')
                        ->numeric()
                        ->prefix('€'),
                ])->columns(3),

            Forms\Components\Section::make('Zuordnungen')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('supplier_id')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('solar_plant_id')
                        ->relationship('solarPlant', 'name')
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('project_manager_id')
                        ->relationship('projectManager', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project_number')
                    ->label('Projekt-Nr.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'planning',
                        'success' => 'active',
                        'warning' => 'on_hold',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                        'danger' => 'urgent',
                    ]),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Fortschritt')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('planned_end_date')
                    ->label('Enddatum')
                    ->date('d.m.Y'),
                Tables\Columns\TextColumn::make('projectManager.name')
                    ->label('Projektleiter'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'planning' => 'Planung',
                        'active' => 'Aktiv',
                        'on_hold' => 'Pausiert',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgebrochen',
                    ]),
                Tables\Filters\SelectFilter::make('priority'),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn ($query) => $query->where('planned_end_date', '<', now())
                        ->where('status', '!=', 'completed')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
            'view' => Pages\ViewProject::route('/{record}'),
        ];
    }
}
```

---

## 🚀 Implementierungs-Schritte

### Phase 1: Datenbank-Migrationen (Tag 1-2)
1. Migration für `projects` Tabelle erstellen
2. Migration für `project_milestones` Tabelle erstellen
3. Migration für `project_appointments` Tabelle erstellen
4. Migration für `project_task` Verknüpfungstabelle erstellen

### Phase 2: Models und Beziehungen (Tag 3)
1. Project Model erstellen
2. ProjectMilestone Model erstellen
3. ProjectAppointment Model erstellen
4. Beziehungen zu bestehenden Models herstellen

### Phase 3: Filament Resources (Tag 4-5)
1. ProjectResource erstellen
2. ProjectMilestoneResource erstellen
3. ProjectAppointmentResource erstellen
4. Navigation und Berechtigungen konfigurieren

### Phase 4: Integration & Testing (Tag 6-7)
1. Task-Integration testen
2. Dashboard-Widgets erstellen
3. Kalender-Integration
4. Benutzer-Tests durchführen

### Phase 5: Erweiterte Features (Tag 8-10)
1. Gantt-Chart Integration
2. E-Mail-Benachrichtigungen
3. Projekt-Vorlagen
4. Mobile App Anpassungen

---

## 📊 Dashboard-Widgets

### 1. Projekt-Übersicht Widget
```php
// Zeigt aktive Projekte mit Fortschritt
```

### 2. Meilenstein-Widget
```php
// Anstehende Meilensteine der nächsten 30 Tage
```

### 3. Termin-Kalender Widget
```php
// Integration mit FullCalendar
```

---

## 🔧 Integration mit bestehenden Systemen

### Task-Integration
- Tasks können Projekten zugeordnet werden
- Projekt-bezogene Filter in Task-Liste
- Bulk-Actions für Projekt-Tasks

### Dokumenten-Integration
- Projekt-Ordner-Struktur
- Automatische Dokumenten-Zuordnung

### Benachrichtigungen
- Projekt-Updates
- Meilenstein-Erinnerungen
- Termin-Benachrichtigungen

---

## 🎯 Nächste Schritte

1. **Migrationen erstellen** und ausführen
2. **Models implementieren** mit allen Beziehungen
3. **Filament Resources** erstellen
4. **Testing** durchführen
5. **Dokumentation** aktualisieren

---

## 📱 Mobile App Erweiterungen

### API Endpoints
- GET /api/projects
- GET /api/projects/{id}
- POST /api/projects/{id}/milestones
- POST /api/projects/{id}/appointments

### Push-Notifications
- Neue Meilenstein-Erinnerungen
- Termin-Benachrichtigungen
- Projekt-Status-Updates

---

## 🔄 Zukünftige Erweiterungen

1. **Risiko-Management**
2. **Ressourcen-Planung**
3. **Budget-Tracking**
4. **Kommunikations-Hub**
5. **Projekt-Vorlagen**
6. **Zeit-Tracking**
7. **Team-Kalender**
8. **Berichts-Generator**

---

**Erstellt am:** 20. Juli 2025  
**Version:** 1.0  
**Autor:** Cline AI Assistant
