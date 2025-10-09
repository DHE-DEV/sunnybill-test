<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectAppointmentResource\Pages;
use App\Models\ProjectAppointment;
use App\Models\Project;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectAppointmentResource extends Resource
{
    protected static ?string $model = ProjectAppointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Termine';

    protected static ?string $navigationGroup = 'Projektverwaltung';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->teams()->exists() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Termin-Details')
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
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Typ')
                                    ->options([
                                        'meeting' => 'Meeting',
                                        'deadline' => 'Deadline',
                                        'review' => 'Review',
                                        'milestone_check' => 'Meilenstein-Check',
                                        'inspection' => 'Inspektion',
                                        'training' => 'Schulung',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('meeting'),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'scheduled' => 'Geplant',
                                        'confirmed' => 'Bestätigt',
                                        'cancelled' => 'Abgesagt',
                                        'completed' => 'Abgeschlossen',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->default('scheduled'),
                            ]),
                    ]),

                Forms\Components\Section::make('Zeitplan & Ort')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('start_datetime')
                                    ->label('Startzeit')
                                    ->required()
                                    ->native(false),
                                Forms\Components\DateTimePicker::make('end_datetime')
                                    ->label('Endzeit')
                                    ->native(false)
                                    ->after('start_datetime'),
                            ]),
                        Forms\Components\TextInput::make('location')
                            ->label('Ort')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('reminder_minutes')
                            ->label('Erinnerung (Minuten)')
                            ->numeric()
                            ->default(60)
                            ->suffix(' Min.'),
                    ]),

                Forms\Components\Section::make('Teilnehmer & Wiederholung')
                    ->schema([
                        Forms\Components\TagsInput::make('attendees')
                            ->label('Teilnehmer')
                            ->placeholder('E-Mail-Adressen der Teilnehmer...'),
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Wiederkehrender Termin')
                            ->reactive(),
                        Forms\Components\KeyValue::make('recurring_pattern')
                            ->label('Wiederholungsmuster')
                            ->visible(fn (Forms\Get $get): bool => $get('is_recurring'))
                            ->keyLabel('Eigenschaft')
                            ->valueLabel('Wert')
                            ->default([
                                'frequency' => 'weekly',
                                'interval' => '1',
                                'days' => 'monday',
                            ]),
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
                        'meeting' => 'info',
                        'deadline' => 'danger',
                        'review' => 'warning',
                        'milestone_check' => 'success',
                        'inspection' => 'primary',
                        'training' => 'secondary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'meeting' => 'Meeting',
                        'deadline' => 'Deadline',
                        'review' => 'Review',
                        'milestone_check' => 'Meilenstein-Check',
                        'inspection' => 'Inspektion',
                        'training' => 'Schulung',
                        default => $state,
                    }),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'cancelled' => 'Abgesagt',
                        'completed' => 'Abgeschlossen',
                    ])
                    ->selectablePlaceholder(false),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Startzeit')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_datetime')
                    ->label('Endzeit')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Ort')
                    ->limit(30),
                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Wiederkehrend')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Erstellt von')
                    ->sortable(),
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
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'cancelled' => 'Abgesagt',
                        'completed' => 'Abgeschlossen',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'meeting' => 'Meeting',
                        'deadline' => 'Deadline',
                        'review' => 'Review',
                        'milestone_check' => 'Meilenstein-Check',
                        'inspection' => 'Inspektion',
                        'training' => 'Schulung',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->label('Heute')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereDate('start_datetime', today())
                    ),
                Tables\Filters\Filter::make('this_week')
                    ->label('Diese Woche')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('start_datetime', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ])
                    ),
                Tables\Filters\Filter::make('upcoming')
                    ->label('Bevorstehend')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('start_datetime', '>=', now())
                              ->whereIn('status', ['scheduled', 'confirmed'])
                    ),
                Tables\Filters\TernaryFilter::make('is_recurring')
                    ->label('Wiederkehrend'),
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
                                    'Projekt', 'Titel', 'Typ', 'Status', 'Startzeit', 'Endzeit',
                                    'Ort', 'Wiederkehrend', 'Erinnerung (Min.)', 'Beschreibung',
                                    'Erstellt von', 'Erstellt am'
                                ];

                                foreach ($records as $appointment) {
                                    $csv[] = [
                                        $appointment->project?->name ?? '',
                                        $appointment->title ?? '',
                                        match($appointment->type) {
                                            'meeting' => 'Meeting',
                                            'deadline' => 'Deadline',
                                            'review' => 'Review',
                                            'milestone_check' => 'Meilenstein-Check',
                                            'inspection' => 'Inspektion',
                                            'training' => 'Schulung',
                                            default => $appointment->type
                                        },
                                        match($appointment->status) {
                                            'scheduled' => 'Geplant',
                                            'confirmed' => 'Bestätigt',
                                            'cancelled' => 'Abgesagt',
                                            'completed' => 'Abgeschlossen',
                                            default => $appointment->status
                                        },
                                        $appointment->start_datetime ? $appointment->start_datetime->format('d.m.Y H:i') : '',
                                        $appointment->end_datetime ? $appointment->end_datetime->format('d.m.Y H:i') : '',
                                        $appointment->location ?? '',
                                        $appointment->is_recurring ? 'Ja' : 'Nein',
                                        $appointment->reminder_minutes ?? '',
                                        $appointment->description ?? '',
                                        $appointment->creator?->name ?? '',
                                        $appointment->created_at ? $appointment->created_at->format('d.m.Y H:i') : '',
                                    ];
                                }

                                $filename = 'projekt-termine-' . now()->format('Y-m-d_H-i-s') . '.csv';
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
                        ->modalDescription(fn (Collection $records) => "Möchten Sie die " . $records->count() . " ausgewählten Termine als CSV-Datei exportieren?")
                        ->modalSubmitActionLabel('CSV exportieren')
                        ->modalIcon('heroicon-o-document-arrow-down'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_datetime', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectAppointments::route('/'),
            'create' => Pages\CreateProjectAppointment::route('/create'),
            'edit' => Pages\EditProjectAppointment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('start_datetime', '>=', now())
                                   ->where('start_datetime', '<=', now()->addDays(7))
                                   ->whereIn('status', ['scheduled', 'confirmed'])
                                   ->count();
    }
}
