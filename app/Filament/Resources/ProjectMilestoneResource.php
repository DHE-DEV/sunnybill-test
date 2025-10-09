<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectMilestoneResource\Pages;
use App\Models\ProjectMilestone;
use App\Models\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectMilestoneResource extends Resource
{
    protected static ?string $model = ProjectMilestone::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Meilensteine';

    protected static ?string $navigationGroup = 'Projektverwaltung';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Meilenstein-Details')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('Projekt')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Typ')
                                    ->options([
                                        'planning' => 'Planung',
                                        'approval' => 'Genehmigung',
                                        'implementation' => 'Umsetzung',
                                        'testing' => 'Testing',
                                        'delivery' => 'Lieferung',
                                        'payment' => 'Zahlung',
                                        'review' => 'Review',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('planning'),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Ausstehend',
                                        'in_progress' => 'In Bearbeitung',
                                        'completed' => 'Abgeschlossen',
                                        'delayed' => 'Verzögert',
                                        'cancelled' => 'Abgebrochen',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('pending'),
                                Forms\Components\Select::make('responsible_user_id')
                                    ->label('Verantwortlicher')
                                    ->relationship('responsibleUser', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Forms\Components\Section::make('Zeitplan & Fortschritt')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('planned_date')
                                    ->label('Geplantes Datum')
                                    ->required()
                                    ->native(false),
                                Forms\Components\DatePicker::make('actual_date')
                                    ->label('Tatsächliches Datum')
                                    ->native(false),
                            ]),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('completion_percentage')
                                    ->label('Fertigstellung (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->default(0),
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Reihenfolge')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('is_critical_path')
                                    ->label('Kritischer Pfad')
                                    ->default(false),
                            ]),
                    ]),

                Forms\Components\Section::make('Abhängigkeiten')
                    ->schema([
                        Forms\Components\Select::make('dependencies')
                            ->label('Abhängige Meilensteine')
                            ->multiple()
                            ->options(function (Forms\Get $get, ?ProjectMilestone $record) {
                                $projectId = $get('project_id');
                                if (!$projectId) {
                                    return [];
                                }
                                
                                $query = \App\Models\ProjectMilestone::where('project_id', $projectId);
                                
                                // Exclude current milestone from options when editing
                                if ($record && $record->id) {
                                    $query->where('id', '!=', $record->id);
                                }
                                
                                return $query->pluck('title', 'id')->toArray();
                            })
                            ->searchable()
                            ->placeholder('Abhängige Meilensteine auswählen...')
                            ->helperText('Meilensteine, die vor diesem Meilenstein abgeschlossen werden müssen.')
                            ->reactive(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Projekt')
                    ->sortable()
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planning' => 'gray',
                        'approval' => 'warning',
                        'implementation' => 'info',
                        'testing' => 'success',
                        'delivery' => 'primary',
                        'payment' => 'danger',
                        'review' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planning' => 'Planung',
                        'approval' => 'Genehmigung',
                        'implementation' => 'Umsetzung',
                        'testing' => 'Testing',
                        'delivery' => 'Lieferung',
                        'payment' => 'Zahlung',
                        'review' => 'Review',
                        default => $state,
                    }),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'delayed' => 'Verzögert',
                        'cancelled' => 'Abgebrochen',
                    ])
                    ->selectablePlaceholder(false),
                Tables\Columns\TextColumn::make('planned_date')
                    ->label('Geplant')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_date')
                    ->label('Tatsächlich')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('Fortschritt')
                    ->suffix('%')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('responsibleUser.name')
                    ->label('Verantwortlicher')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_critical_path')
                    ->label('Kritisch')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('dependencies')
                    ->label('Abhängigkeiten')
                    ->getStateUsing(function (ProjectMilestone $record): string {
                        if (!$record->dependencies || empty($record->dependencies)) {
                            return '-';
                        }
                        
                        $dependentMilestones = ProjectMilestone::whereIn('id', $record->dependencies)
                            ->pluck('title')
                            ->toArray();
                            
                        return implode(', ', $dependentMilestones);
                    })
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Projekt')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'in_progress' => 'In Bearbeitung',
                        'completed' => 'Abgeschlossen',
                        'delayed' => 'Verzögert',
                        'cancelled' => 'Abgebrochen',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'planning' => 'Planung',
                        'approval' => 'Genehmigung',
                        'implementation' => 'Umsetzung',
                        'testing' => 'Testing',
                        'delivery' => 'Lieferung',
                        'payment' => 'Zahlung',
                        'review' => 'Review',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Überfällig')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('planned_date', '<', now())
                              ->where('status', '!=', 'completed')
                    ),
                Tables\Filters\TernaryFilter::make('is_critical_path')
                    ->label('Kritischer Pfad'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_csv')
                        ->label('CSV Export')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            try {
                                $csv = [];
                                $csv[] = [
                                    'Projekt', 'Titel', 'Typ', 'Status', 'Geplantes Datum', 'Tatsächliches Datum',
                                    'Fertigstellung (%)', 'Verantwortlicher', 'Kritischer Pfad', 'Reihenfolge',
                                    'Beschreibung', 'Erstellt am'
                                ];

                                foreach ($records as $milestone) {
                                    $csv[] = [
                                        $milestone->project?->name ?? '',
                                        $milestone->title ?? '',
                                        match($milestone->type) {
                                            'planning' => 'Planung',
                                            'approval' => 'Genehmigung',
                                            'implementation' => 'Umsetzung',
                                            'testing' => 'Testing',
                                            'delivery' => 'Lieferung',
                                            'payment' => 'Zahlung',
                                            'review' => 'Review',
                                            default => $milestone->type
                                        },
                                        match($milestone->status) {
                                            'pending' => 'Ausstehend',
                                            'in_progress' => 'In Bearbeitung',
                                            'completed' => 'Abgeschlossen',
                                            'delayed' => 'Verzögert',
                                            'cancelled' => 'Abgebrochen',
                                            default => $milestone->status
                                        },
                                        $milestone->planned_date ? $milestone->planned_date->format('d.m.Y') : '',
                                        $milestone->actual_date ? $milestone->actual_date->format('d.m.Y') : '',
                                        $milestone->completion_percentage ?? '0',
                                        $milestone->responsibleUser?->name ?? '',
                                        $milestone->is_critical_path ? 'Ja' : 'Nein',
                                        $milestone->sort_order ?? '0',
                                        $milestone->description ?? '',
                                        $milestone->created_at ? $milestone->created_at->format('d.m.Y H:i') : '',
                                    ];
                                }

                                $filename = 'projekt-meilensteine-' . now()->format('Y-m-d_H-i-s') . '.csv';
                                $tempPath = 'temp/csv-exports/' . $filename;
                                \Storage::disk('public')->makeDirectory('temp/csv-exports');
                                $output = fopen('php://temp', 'r+');
                                fputs($output, "\xEF\xBB\xBF");
                                foreach ($csv as $row) {
                                    fputcsv($output, $row, ';');
                                }
                                rewind($output);
                                \Storage::disk('public')->put($tempPath, stream_get_contents($output));
                                fclose($output);

                                session(['csv_download_path' => $tempPath, 'csv_download_filename' => $filename]);

                                Notification::make()
                                    ->title('CSV-Export erfolgreich')
                                    ->body('Klicken Sie auf den Button, um die Datei herunterzuladen.')
                                    ->success()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('download')
                                            ->label('Datei herunterladen')
                                            ->url(route('admin.download-csv'))
                                            ->openUrlInNewTab()
                                            ->button()
                                    ])
                                    ->persistent()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Fehler beim CSV-Export')
                                    ->body('Ein Fehler ist aufgetreten: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('CSV Export')
                        ->modalDescription(fn (Collection $records) => "Möchten Sie die " . $records->count() . " ausgewählten Meilensteine als CSV-Datei exportieren?")
                        ->modalSubmitActionLabel('CSV exportieren')
                        ->modalIcon('heroicon-o-document-arrow-down'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('planned_date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectMilestones::route('/'),
            'create' => Pages\CreateProjectMilestone::route('/create'),
            'edit' => Pages\EditProjectMilestone::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['pending', 'in_progress'])->count();
    }
}
