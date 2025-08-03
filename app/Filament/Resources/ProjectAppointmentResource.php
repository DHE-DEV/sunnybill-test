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

    protected static ?int $navigationSort = -1;

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
