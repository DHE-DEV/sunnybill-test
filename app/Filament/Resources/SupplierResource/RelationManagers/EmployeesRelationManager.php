<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    protected static ?string $title = 'Mitarbeiter';

    protected static ?string $modelLabel = 'Mitarbeiter';

    protected static ?string $pluralModelLabel = 'Mitarbeiter';

    protected static ?string $icon = 'heroicon-o-users';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Persönliche Daten')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Vorname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Nachname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('position')
                            ->label('Position/Abteilung')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Telefonnummern')
                    ->schema([
                        Forms\Components\Repeater::make('phoneNumbers')
                            ->label('Telefonnummern')
                            ->relationship('phoneNumbers')
                            ->schema([
                                Forms\Components\TextInput::make('phone_number')
                                    ->label('Telefonnummer')
                                    ->required()
                                    ->tel(),
                                Forms\Components\Select::make('type')
                                    ->label('Typ')
                                    ->options([
                                        'business' => 'Geschäftlich',
                                        'private' => 'Privat',
                                        'mobile' => 'Mobil',
                                    ])
                                    ->default('business')
                                    ->required(),
                                Forms\Components\TextInput::make('label')
                                    ->label('Beschreibung')
                                    ->maxLength(255),
                                Forms\Components\Toggle::make('is_primary')
                                    ->label('Hauptnummer'),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                ($state['phone_number'] ?? '') . 
                                (isset($state['type']) ? ' (' . match($state['type']) {
                                    'business' => 'Geschäftlich',
                                    'private' => 'Privat',
                                    'mobile' => 'Mobil',
                                    default => $state['type']
                                } . ')' : '')
                            ),
                    ]),

                Forms\Components\Section::make('Sonstiges')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_primary_contact')
                            ->label('Hauptansprechpartner'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->toggleable()
                    ->url(fn ($record) => $record->email ? 'mailto:' . $record->email : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('primary_phone')
                    ->label('Telefon')
                    ->url(fn ($record) => $record->primary_phone ? 'tel:' . preg_replace('/[\s\-\/]/', '', $record->primary_phone) : null)
                    ->openUrlInNewTab(false)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_primary_contact')
                    ->label('Hauptkontakt')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                Tables\Filters\TernaryFilter::make('is_primary_contact')
                    ->label('Hauptansprechpartner'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Mitarbeiter hinzufügen')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\Section::make('Persönliche Daten')
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->label('Vorname')
                                    ->disabled(),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('Nachname')
                                    ->disabled(),
                                Forms\Components\TextInput::make('position')
                                    ->label('Position/Abteilung')
                                    ->disabled(),
                                Forms\Components\TextInput::make('email')
                                    ->label('E-Mail')
                                    ->disabled(),
                            ])->columns(2),

                        Forms\Components\Section::make('Telefonnummern')
                            ->schema([
                                Forms\Components\Repeater::make('phoneNumbers')
                                    ->label('Telefonnummern')
                                    ->relationship('phoneNumbers')
                                    ->schema([
                                        Forms\Components\TextInput::make('phone_number')
                                            ->label('Telefonnummer')
                                            ->disabled(),
                                        Forms\Components\TextInput::make('type')
                                            ->label('Typ')
                                            ->formatStateUsing(fn ($state) => match($state) {
                                                'business' => 'Geschäftlich',
                                                'private' => 'Privat',
                                                'mobile' => 'Mobil',
                                                default => $state
                                            })
                                            ->disabled(),
                                        Forms\Components\TextInput::make('label')
                                            ->label('Beschreibung')
                                            ->disabled(),
                                        Forms\Components\Toggle::make('is_primary')
                                            ->label('Hauptnummer')
                                            ->disabled(),
                                    ])
                                    ->columns(2)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false),
                            ]),

                        Forms\Components\Section::make('Status & Notizen')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notizen')
                                    ->disabled()
                                    ->rows(3),
                                Forms\Components\Toggle::make('is_primary_contact')
                                    ->label('Hauptansprechpartner')
                                    ->disabled(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktiv')
                                    ->disabled(),
                            ]),
                    ])
                    ->modalWidth('4xl'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_name')
            ->recordAction('view')
            ->recordUrl(null);
    }
}