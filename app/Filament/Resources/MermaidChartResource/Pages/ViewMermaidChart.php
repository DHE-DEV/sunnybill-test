<?php

namespace App\Filament\Resources\MermaidChartResource\Pages;

use App\Filament\Resources\MermaidChartResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewMermaidChart extends ViewRecord
{
    protected static string $resource = MermaidChartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            
            Actions\Action::make('generate')
                ->label('Code generieren')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->action(function () {
                    try {
                        $generatedCode = $this->record->generateCode();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Code generiert')
                            ->body('Chart-Code wurde erfolgreich generiert und gespeichert!')
                            ->success()
                            ->send();
                            
                        // Seite neu laden um den generierten Code anzuzeigen
                        return redirect()->to(request()->url());
                        
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Fehler')
                            ->body('Fehler beim Generieren: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->hasSolarPlant()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Grunddaten')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Name'),
                                
                                Infolists\Components\TextEntry::make('chart_type')
                                    ->label('Chart-Typ')
                                    ->formatStateUsing(fn (string $state): string => 
                                        \App\Models\MermaidChart::getChartTypes()[$state] ?? $state
                                    )
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'solar_plant' => 'primary',
                                        'customer_overview' => 'success',
                                        'supplier_overview' => 'warning',
                                        'contract_overview' => 'danger',
                                        default => 'secondary',
                                    }),
                            ]),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Beschreibung')
                            ->placeholder('Keine Beschreibung')
                            ->columnSpanFull(),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('solarPlant.name')
                                    ->label('Solaranlage')
                                    ->placeholder('Keine Solaranlage zugewiesen'),
                                
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Status')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Template')
                    ->schema([
                        Infolists\Components\TextEntry::make('template')
                            ->label('Mermaid Template')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                
                Infolists\Components\Section::make('Generierter Code')
                    ->schema([
                        Infolists\Components\TextEntry::make('generated_code')
                            ->label('Generierter Mermaid-Code')
                            ->markdown()
                            ->placeholder('Noch kein Code generiert')
                            ->columnSpanFull(),
                        
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('copy_code')
                                ->label('Code kopieren')
                                ->icon('heroicon-o-clipboard')
                                ->color('secondary')
                                ->action(function () {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Code kopiert')
                                        ->body('Chart-Code wurde in die Zwischenablage kopiert!')
                                        ->success()
                                        ->send();
                                })
                                ->extraAttributes([
                                    'x-on:click' => 'navigator.clipboard.writeText(\'' . 
                                        addslashes($this->record->getChartCode()) . '\')'
                                ])
                                ->visible(fn () => !empty($this->record->getChartCode())),
                        ]),
                    ])
                    ->visible(fn () => !empty($this->record->generated_code))
                    ->collapsible(),
                
                Infolists\Components\Section::make('Metadaten')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt')
                                    ->dateTime('d.m.Y H:i:s'),
                                
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Zuletzt geändert')
                                    ->dateTime('d.m.Y H:i:s'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}