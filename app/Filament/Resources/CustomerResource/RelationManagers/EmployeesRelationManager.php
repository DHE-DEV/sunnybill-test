<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
                Forms\Components\Section::make('Mitarbeiter-Daten')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Vorname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Nachname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('position')
                            ->label('Position')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('department')
                            ->label('Abteilung')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_primary_contact')
                            ->label('Hauptansprechpartner')
                            ->default(false)
                            ->helperText('Hinweis: Es kann nur einen Hauptansprechpartner pro Kunde geben. Bei Aktivierung wird der bisherige Hauptansprechpartner automatisch deaktiviert.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                Tables\Columns\IconColumn::make('is_primary_contact')
                    ->label('')
                    ->icon(fn ($state) => $state ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                    ->tooltip(fn ($state) => $state ? 'Hauptansprechpartner' : '')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('department')
                    ->label('Abteilung')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Sicherstellen, dass is_primary_contact korrekt gesetzt wird
                        $data['is_primary_contact'] = $data['is_primary_contact'] ?? false;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Sicherstellen, dass is_primary_contact korrekt gesetzt wird
                        $data['is_primary_contact'] = $data['is_primary_contact'] ?? false;
                        return $data;
                    })
                    ->after(function ($record, array $data) {
                        // Explizit andere Hauptansprechpartner deaktivieren
                        if ($data['is_primary_contact']) {
                            \App\Models\CustomerEmployee::where('customer_id', $record->customer_id)
                                ->where('id', '!=', $record->id)
                                ->update(['is_primary_contact' => false]);
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_primary_contact', 'desc')
            ->emptyStateHeading('Keine Mitarbeiter vorhanden')
            ->emptyStateDescription('Fügen Sie den ersten Mitarbeiter für diesen Kunden hinzu.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        if ($ownerRecord && $ownerRecord->isPrivateCustomer()) {
            return 'Personen';
        }
        
        return 'Mitarbeiter';
    }
}