<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MermaidChartResource\Pages;
use App\Models\MermaidChart;
use App\Models\SolarPlant;
use App\Services\MermaidChartService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class MermaidChartResource extends Resource
{
    protected static ?string $model = MermaidChart::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Mermaid-Charts';
    
    protected static ?string $modelLabel = 'Mermaid-Chart';
    
    protected static ?string $pluralModelLabel = 'Mermaid-Charts';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grunddaten')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('z.B. Solaranlage Aurich 1 - Übersicht'),
                                
                                Forms\Components\Select::make('chart_type')
                                    ->label('Chart-Typ')
                                    ->options(MermaidChart::getChartTypes())
                                    ->required()
                                    ->default('solar_plant')
                                    ->reactive(),
                            ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(2)
                            ->placeholder('Optionale Beschreibung des Charts')
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('solar_plant_id')
                            ->label('Solaranlage')
                            ->options(SolarPlant::all()->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Wählen Sie eine Solaranlage aus...')
                            ->visible(fn ($get) => $get('chart_type') === 'solar_plant')
                            ->reactive(),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktiv')
                            ->default(true)
                            ->helperText('Deaktivierte Charts werden nicht in der Übersicht angezeigt'),
                    ]),
                
                Forms\Components\Section::make('Template')
                    ->schema([
                        Forms\Components\Textarea::make('template')
                            ->label('Mermaid Template')
                            ->rows(20)
                            ->required()
                            ->placeholder('Geben Sie hier Ihr Mermaid-Template ein...')
                            ->helperText('Verwenden Sie Platzhalter für automatische Datenaktualisierung. Klicken Sie auf "Template-Hilfe" für alle verfügbaren Platzhalter. Der Code wird bei jeder Generierung mit aktuellen Daten aus der Datenbank gefüllt.')
                            ->columnSpanFull(),
                        
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('load_default_template')
                                ->label('Standard-Template laden')
                                ->icon('heroicon-o-document-duplicate')
                                ->color('secondary')
                                ->action(function ($set, $get) {
                                    $chartType = $get('chart_type');
                                    if ($chartType === 'solar_plant') {
                                        $set('template', MermaidChart::getDefaultSolarPlantTemplate());
                                        
                                        Notification::make()
                                            ->title('Template geladen')
                                            ->body('Erweitertes Standard-Template für Solaranlagen wurde geladen. Alle Platzhalter werden automatisch mit aktuellen Daten gefüllt.')
                                            ->success()
                                            ->send();
                                    }
                                })
                                ->visible(fn ($get) => $get('chart_type') === 'solar_plant'),
                            
                            Forms\Components\Actions\Action::make('show_template_help')
                                ->label('Template-Hilfe')
                                ->icon('heroicon-o-question-mark-circle')
                                ->color('info')
                                ->modalHeading('Verfügbare Template-Platzhalter')
                                ->modalContent(function () {
                                    $documentation = MermaidChart::getTemplateDocumentation();
                                    $content = '<div class="space-y-4">';
                                    
                                    foreach ($documentation as $category => $placeholders) {
                                        $content .= '<div>';
                                        $content .= '<h3 class="font-semibold text-lg mb-2">' . $category . '</h3>';
                                        $content .= '<div class="grid grid-cols-1 gap-2">';
                                        
                                        foreach ($placeholders as $placeholder => $description) {
                                            $content .= '<div class="flex justify-between items-center p-2 bg-gray-50 rounded">';
                                            $content .= '<code class="text-sm font-mono text-blue-600">' . $placeholder . '</code>';
                                            $content .= '<span class="text-sm text-gray-600">' . $description . '</span>';
                                            $content .= '</div>';
                                        }
                                        
                                        $content .= '</div></div>';
                                    }
                                    
                                    $content .= '</div>';
                                    
                                    return new \Illuminate\Support\HtmlString($content);
                                })
                                ->modalSubmitAction(false)
                                ->modalCancelActionLabel('Schließen'),
                            
                            Forms\Components\Actions\Action::make('generate_preview')
                                ->label('Vorschau generieren')
                                ->icon('heroicon-o-eye')
                                ->color('primary')
                                ->action(function ($record, $get, $set) {
                                    $solarPlantId = $get('solar_plant_id');
                                    $template = $get('template');
                                    
                                    if (!$solarPlantId || !$template) {
                                        Notification::make()
                                            ->title('Fehler')
                                            ->body('Bitte wählen Sie eine Solaranlage aus und geben Sie ein Template ein.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    
                                    try {
                                        $solarPlant = SolarPlant::findOrFail($solarPlantId);
                                        $mermaidService = new MermaidChartService();
                                        $generatedCode = $mermaidService->generateSolarPlantChart($solarPlant, $template);
                                        
                                        $set('generated_code', $generatedCode);
                                        
                                        Notification::make()
                                            ->title('Vorschau generiert')
                                            ->body('Chart-Code wurde erfolgreich generiert!')
                                            ->success()
                                            ->send();
                                            
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Fehler')
                                            ->body('Fehler beim Generieren: ' . $e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->visible(fn ($get) => $get('chart_type') === 'solar_plant' && $get('solar_plant_id')),
                        ]),
                    ]),
                
                Forms\Components\Section::make('Generierter Code')
                    ->schema([
                        Forms\Components\Textarea::make('generated_code')
                            ->label('Generierter Mermaid-Code')
                            ->rows(15)
                            ->placeholder('Hier wird der generierte Code angezeigt...')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => !$record || empty($record->generated_code)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('chart_type')
                    ->label('Typ')
                    ->formatStateUsing(fn (string $state): string => MermaidChart::getChartTypes()[$state] ?? $state)
                    ->colors([
                        'primary' => 'solar_plant',
                        'success' => 'customer_overview',
                        'warning' => 'supplier_overview',
                        'danger' => 'contract_overview',
                        'secondary' => 'custom',
                    ]),
                
                Tables\Columns\TextColumn::make('solarPlant.name')
                    ->label('Solaranlage')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Keine Solaranlage'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Geändert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('chart_type')
                    ->label('Chart-Typ')
                    ->options(MermaidChart::getChartTypes()),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Aktiv')
                    ->falseLabel('Inaktiv'),
                
                Tables\Filters\SelectFilter::make('solar_plant_id')
                    ->label('Solaranlage')
                    ->options(SolarPlant::all()->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('generate')
                        ->label('Code generieren')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->color('primary')
                        ->action(function (MermaidChart $record) {
                            try {
                                $generatedCode = $record->generateCode();
                                
                                Notification::make()
                                    ->title('Code generiert')
                                    ->body('Chart-Code wurde erfolgreich generiert und gespeichert!')
                                    ->success()
                                    ->send();
                                    
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Fehler')
                                    ->body('Fehler beim Generieren: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (MermaidChart $record) => $record->hasSolarPlant()),
                    
                    Tables\Actions\Action::make('copy_code')
                        ->label('Code kopieren')
                        ->icon('heroicon-o-clipboard')
                        ->color('secondary')
                        ->action(function (MermaidChart $record) {
                            $code = $record->getChartCode();
                            
                            // JavaScript für Clipboard wird über Alpine.js ausgeführt
                            Notification::make()
                                ->title('Code kopiert')
                                ->body('Chart-Code wurde in die Zwischenablage kopiert!')
                                ->success()
                                ->send();
                        })
                        ->extraAttributes([
                            'x-on:click' => 'navigator.clipboard.writeText($wire.getChartCode(' . '$record->id' . '))'
                        ]),
                    
                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Aktionen')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title('Charts aktiviert')
                                ->body(count($records) . ' Charts wurden aktiviert.')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('Charts deaktiviert')
                                ->body(count($records) . ' Charts wurden deaktiviert.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMermaidCharts::route('/'),
            'create' => Pages\CreateMermaidChart::route('/create'),
            'view' => Pages\ViewMermaidChart::route('/{record}'),
            'edit' => Pages\EditMermaidChart::route('/{record}/edit'),
        ];
    }

    // Zugriffskontrolle für System-Ressourcen (Administrator + Superadmin Teams)
    public static function canViewAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }
}