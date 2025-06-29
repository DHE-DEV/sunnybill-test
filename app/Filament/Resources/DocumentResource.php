<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'Dokument';

    protected static ?string $pluralModelLabel = 'Dokumente';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dokumentinformationen')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('category')
                            ->label('Kategorie')
                            ->options(Document::getCategories())
                            ->searchable()
                            ->placeholder('Kategorie auswählen...'),

                        Forms\Components\FileUpload::make('path')
                            ->label('Datei')
                            ->disk('documents')
                            ->directory(function (callable $get) {
                                // Dynamische Verzeichnisstruktur basierend auf Dokumenttyp und Jahr
                                $type = $get('documentable_type');
                                $year = date('Y');
                                
                                return match ($type) {
                                    'App\Models\SolarPlant' => "solaranlagen/{$year}",
                                    'App\Models\Customer' => "kunden/{$year}",
                                    'App\Models\Task' => "aufgaben/{$year}",
                                    'App\Models\Invoice' => "rechnungen/{$year}",
                                    'App\Models\Supplier' => "lieferanten/{$year}",
                                    default => "allgemein/{$year}",
                                };
                            })
                            ->storeFileNamesIn('original_name')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/jpg',
                                'image/png',
                                'image/gif',
                                'application/zip',
                                'application/x-rar-compressed',
                            ])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Zuordnung')
                    ->schema([
                        Forms\Components\Select::make('documentable_type')
                            ->label('Dokumenttyp')
                            ->options([
                                'App\Models\SolarPlant' => 'Solaranlage',
                                'App\Models\Customer' => 'Kunde',
                                'App\Models\Task' => 'Aufgabe',
                                'App\Models\Invoice' => 'Rechnung',
                                'App\Models\Supplier' => 'Lieferant',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Select::make('documentable_id')
                            ->label('Zugeordnet zu')
                            ->options(function (callable $get) {
                                $type = $get('documentable_type');
                                if (!$type) return [];

                                return match ($type) {
                                    'App\Models\SolarPlant' => \App\Models\SolarPlant::whereNotNull('name')
                                        ->where('name', '!=', '')
                                        ->get()
                                        ->pluck('name', 'id')
                                        ->filter()
                                        ->toArray(),
                                    'App\Models\Customer' => \App\Models\Customer::whereNotNull('customer_number')
                                        ->whereNotNull('company_name')
                                        ->where('customer_number', '!=', '')
                                        ->where('company_name', '!=', '')
                                        ->get()
                                        ->mapWithKeys(function ($customer) {
                                            return [$customer->id => $customer->customer_number . ' - ' . $customer->company_name];
                                        })
                                        ->toArray(),
                                    'App\Models\Task' => \App\Models\Task::whereNotNull('task_number')
                                        ->whereNotNull('title')
                                        ->where('task_number', '!=', '')
                                        ->where('title', '!=', '')
                                        ->get()
                                        ->mapWithKeys(function ($task) {
                                            return [$task->id => $task->task_number . ' - ' . $task->title];
                                        })
                                        ->toArray(),
                                    'App\Models\Invoice' => \App\Models\Invoice::whereNotNull('invoice_number')
                                        ->where('invoice_number', '!=', '')
                                        ->pluck('invoice_number', 'id')
                                        ->filter()
                                        ->toArray(),
                                    'App\Models\Supplier' => \App\Models\Supplier::whereNotNull('supplier_number')
                                        ->whereNotNull('company_name')
                                        ->where('supplier_number', '!=', '')
                                        ->where('company_name', '!=', '')
                                        ->get()
                                        ->mapWithKeys(function ($supplier) {
                                            return [$supplier->id => $supplier->supplier_number . ' - ' . $supplier->company_name];
                                        })
                                        ->toArray(),
                                    default => [],
                                };
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('icon')
                    ->label('')
                    ->icon(fn (Document $record): string => $record->icon)
                    ->color('primary')
                    ->size('lg'),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),

                TextColumn::make('path')
                    ->label('Speicherort')
                    ->formatStateUsing(fn (string $state): string => dirname($state))
                    ->badge()
                    ->color('gray')
                    ->limit(30)
                    ->toggleable(),

                BadgeColumn::make('category')
                    ->label('Kategorie')
                    ->formatStateUsing(fn (string $state): string => Document::getCategories()[$state] ?? $state)
                    ->colors([
                        'primary' => 'planning',
                        'warning' => 'permits',
                        'success' => 'installation',
                        'info' => 'maintenance',
                        'danger' => 'invoices',
                        'secondary' => fn ($state): bool => !in_array($state, ['planning', 'permits', 'installation', 'maintenance', 'invoices']),
                    ])
                    ->sortable(),

                TextColumn::make('documentable_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\Models\SolarPlant' => 'Solaranlage',
                        'App\Models\Customer' => 'Kunde',
                        'App\Models\Task' => 'Aufgabe',
                        'App\Models\Invoice' => 'Rechnung',
                        'App\Models\Supplier' => 'Lieferant',
                        default => $state,
                    })
                    ->badge()
                    ->sortable(),

                TextColumn::make('documentable.name')
                    ->label('Zugeordnet zu')
                    ->getStateUsing(function (Document $record) {
                        return match ($record->documentable_type) {
                            'App\Models\SolarPlant' => $record->documentable?->name,
                            'App\Models\Customer' => $record->documentable?->company_name,
                            'App\Models\Task' => $record->documentable?->title,
                            'App\Models\Invoice' => $record->documentable?->invoice_number,
                            'App\Models\Supplier' => $record->documentable?->company_name,
                            default => 'Unbekannt',
                        };
                    })
                    ->searchable()
                    ->limit(30),

                TextColumn::make('formatted_size')
                    ->label('Größe')
                    ->sortable(['size']),

                TextColumn::make('uploadedBy.name')
                    ->label('Hochgeladen von')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Hochgeladen am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options(Document::getCategories()),

                SelectFilter::make('documentable_type')
                    ->label('Dokumenttyp')
                    ->options([
                        'App\Models\SolarPlant' => 'Solaranlage',
                        'App\Models\Customer' => 'Kunde',
                        'App\Models\Task' => 'Aufgabe',
                        'App\Models\Invoice' => 'Rechnung',
                        'App\Models\Supplier' => 'Lieferant',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Action::make('download')
                        ->label('Herunterladen')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (Document $record): string => route('documents.download', $record))
                        ->openUrlInNewTab(),
                    Action::make('preview')
                        ->label('Vorschau')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn (Document $record): string => route('documents.preview', $record))
                        ->openUrlInNewTab()
                        ->visible(fn (Document $record): bool => str_contains($record->mime_type, 'image') || str_contains($record->mime_type, 'pdf')),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('Aktionen')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}