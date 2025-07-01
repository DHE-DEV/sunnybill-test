<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Services\DocumentUploadConfig;
use App\Services\DocumentFormBuilder;
use App\Services\DocumentTableBuilder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Lieferanten';

    protected static ?string $modelLabel = 'Lieferant';

    protected static ?string $pluralModelLabel = 'Lieferanten';

    protected static ?string $navigationGroup = 'Stammdaten';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Firmendaten')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Firmenname')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('supplier_type_id')
                            ->label('Lieferantentyp')
                            ->options(SupplierType::active()->ordered()->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('contact_person')
                            ->label('Ansprechpartner')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Adresse')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->rows(3)
                            ->placeholder('Straße & Hausnummer, PLZ Ort'),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('PLZ')
                            ->maxLength(10),
                        Forms\Components\TextInput::make('city')
                            ->label('Stadt')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->label('Land')
                            ->default('Deutschland')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Steuerliche Daten')
                    ->schema([
                        Forms\Components\TextInput::make('tax_number')
                            ->label('Steuernummer')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vat_id')
                            ->label('Umsatzsteuer-ID')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Lexoffice-Synchronisation')
                    ->schema([
                        Forms\Components\TextInput::make('lexoffice_id')
                            ->label('Lexoffice-ID')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('lexoffice_synced_at')
                            ->label('Zuletzt synchronisiert')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2)
                    ->visible(fn ($record) => $record?->lexoffice_id),

                Forms\Components\Section::make('Sonstiges')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true),
                    ]),

                // Neue Dokumente Section
                Forms\Components\Section::make('Dokumente')
                    ->schema([
                        // Vorhandene Dokumente anzeigen
                        Forms\Components\Placeholder::make('existing_documents')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->documents()->exists()) {
                                    return 'Keine Dokumente vorhanden.';
                                }
                                
                                $html = '<div class="space-y-2">';
                                foreach ($record->documents as $document) {
                                    $categoryBadge = match($document->category) {
                                        'contract' => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Hauptvertrag</span>',
                                        'amendment' => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Nachtrag</span>',
                                        'annex' => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Anlage</span>',
                                        'invoice' => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded-full">Rechnung</span>',
                                        'correspondence' => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Korrespondenz</span>',
                                        'technical' => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Technische Unterlagen</span>',
                                        'legal' => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">Rechtsdokumente</span>',
                                        'termination' => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Kündigung</span>',
                                        default => '<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Sonstiges</span>',
                                    };
                                    
                                    $size = $document->formatted_size;
                                    $uploadedAt = $document->created_at->format('d.m.Y H:i');
                                    $uploadedBy = $document->uploadedBy?->name ?? 'Unbekannt';
                                    
                                    $html .= '<div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">';
                                    $html .= '<div class="flex items-center space-x-3">';
                                    $html .= '<div class="flex-shrink-0">';
                                    $html .= '<svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>';
                                    $html .= '</div>';
                                    $html .= '<div class="flex-1 min-w-0">';
                                    $html .= '<p class="text-sm font-medium text-gray-900 truncate">' . e($document->name) . '</p>';
                                    $html .= '<p class="text-sm text-gray-500">' . $size . ' • ' . $uploadedAt . ' • ' . e($uploadedBy) . '</p>';
                                    $html .= '</div>';
                                    $html .= '<div class="flex items-center space-x-2">';
                                    $html .= $categoryBadge;
                                    $html .= '</div>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record && $record->documents()->exists()),
                        
                        // Neues Dokument hinzufügen
                        Forms\Components\Repeater::make('documents')
                            ->relationship('documents')
                            ->schema(self::getDocumentFormSchema())
                            ->addActionLabel('Dokument hinzufügen')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Neues Dokument')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-document-text'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier_number')
                    ->label('Lieferanten-Nr.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Firmenname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplierType.name')
                    ->label('Typ')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Ansprechpartner')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->toggleable()
                    ->url(fn ($record) => $record->email ? 'mailto:' . $record->email : null)
                    ->openUrlInNewTab(false),
                Tables\Columns\TextColumn::make('city')
                    ->label('Stadt')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('lexoffice_synced')
                    ->label('Lexoffice')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isSyncedWithLexoffice())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Mitarbeiter')
                    ->counts('employees')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_type_id')
                    ->label('Lieferantentyp')
                    ->options(SupplierType::active()->ordered()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv'),
                Tables\Filters\TernaryFilter::make('lexoffice_synced')
                    ->label('Lexoffice synchronisiert')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('lexoffice_id'),
                        false: fn (Builder $query) => $query->whereNull('lexoffice_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('company_name');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\CustomerResource\RelationManagers\AddressesRelationManager::class,
            RelationManagers\PhoneNumbersRelationManager::class,
            RelationManagers\EmployeesRelationManager::class,
            RelationManagers\ContractsRelationManager::class,
            RelationManagers\FavoriteNotesRelationManager::class,
            RelationManagers\StandardNotesRelationManager::class,
            RelationManagers\SolarPlantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    /**
     * Erstellt das Formular-Schema für Dokumente mit der gewünschten Pfadstruktur
     */
    protected static function getDocumentFormSchema(): array
    {
        return [
            Forms\Components\FileUpload::make('path')
                ->label('Datei')
                ->required()
                ->disk(fn () => \App\Services\DocumentStorageService::getDiskName())
                ->directory(function ($get, $livewire) {
                    // Hole den aktuellen Supplier aus dem Livewire-Kontext
                    $supplier = $livewire->getRecord();
                    
                    if ($supplier) {
                        // Verwende DocumentUploadConfig für die Pfadstruktur
                        $config = DocumentUploadConfig::forSupplierContracts()
                            ->setModel($supplier)
                            ->setAdditionalData([
                                'contract_internal_number' => $get('contract_internal_number') ?? 'general'
                            ]);
                        
                        return $config->getStorageDirectory();
                    }
                    
                    return 'suppliers/documents';
                })
                ->maxSize(10240) // 10MB
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                ])
                ->preserveFilenames(false)
                ->getUploadedFileNameForStorageUsing(function ($file) {
                    $timestamp = now()->format('Y-m-d_H-i-s');
                    $extension = $file->getClientOriginalExtension();
                    $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $cleanName = preg_replace('/[^\w\-_.]/', '-', $name);
                    return $cleanName . '_' . $timestamp . '.' . $extension;
                })
                ->afterStateUpdated(function (Forms\Set $set, $state) {
                    if ($state) {
                        $filePath = is_array($state) ? $state[0] ?? null : $state;
                        if ($filePath) {
                            try {
                                $metadata = \App\Services\DocumentStorageService::extractFileMetadata($filePath);
                                $set('original_name', $metadata['original_name'] ?? '');
                                $set('size', $metadata['size'] ?? 0);
                                $set('mime_type', $metadata['mime_type'] ?? '');
                                $set('disk', $metadata['disk'] ?? '');
                                $set('uploaded_by', auth()->id());
                                
                                // Auto-fill Name wenn leer
                                $set('name', $metadata['original_name'] ?? '');
                            } catch (\Exception $e) {
                                \Log::error('Fehler beim Extrahieren der Metadaten', [
                                    'file_path' => $filePath,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                })
                ->columnSpanFull(),

            Forms\Components\TextInput::make('name')
                ->label('Dokumentname')
                ->required()
                ->maxLength(255)
                ->placeholder('Name des Dokuments'),

            Forms\Components\TextInput::make('contract_internal_number')
                ->label('Vertragsnummer')
                ->maxLength(255)
                ->placeholder('Interne Vertragsnummer (für Pfadstruktur)')
                ->helperText('Wird für die Ordnerstruktur verwendet: suppliers/{supplier_id}/contracts/{contract_internal_number}/'),

            Forms\Components\Select::make('category')
                ->label('Kategorie')
                ->options([
                    'contract' => 'Hauptvertrag',
                    'amendment' => 'Nachtrag',
                    'annex' => 'Anlage',
                    'invoice' => 'Rechnung',
                    'correspondence' => 'Korrespondenz',
                    'technical' => 'Technische Unterlagen',
                    'legal' => 'Rechtsdokumente',
                    'termination' => 'Kündigung',
                    'other' => 'Sonstiges',
                ])
                ->searchable()
                ->default('other'),

            Forms\Components\Textarea::make('description')
                ->label('Beschreibung')
                ->rows(3)
                ->maxLength(1000)
                ->placeholder('Optionale Beschreibung des Dokuments')
                ->columnSpanFull(),

            // Versteckte Metadaten-Felder
            Forms\Components\Hidden::make('original_name'),
            Forms\Components\Hidden::make('disk'),
            Forms\Components\Hidden::make('size'),
            Forms\Components\Hidden::make('mime_type'),
            Forms\Components\Hidden::make('uploaded_by'),
        ];
    }
}