<?php

namespace App\Filament\Pages;

use App\Services\DigitalOceanSpacesService;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class DigitalOceanSpacesBrowser extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cloud';
    protected static string $view = 'filament.pages.digital-ocean-spaces-browser';
    protected static ?string $slug = 'digital-ocean-spaces-browser';
    protected static ?string $title = 'DigitalOcean Spaces Browser';
    protected static ?string $navigationLabel = 'Spaces Browser';
    protected static ?int $navigationSort = 100;

    public string $currentPath = '';
    public array $breadcrumbs = [];
    public string $search = '';
    public array $files = [];
    public array $directories = [];
    public ?string $configurationStatus = null;

    private ?DigitalOceanSpacesService $spacesService = null;

    public function mount(): void
    {
        $this->checkConfiguration();
        if ($this->isConfigured()) {
            $this->initializeService();
            $this->loadContents();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('search')
                    ->placeholder('Dateiname suchen...')
                    ->live(debounce: 500)
                    ->afterStateUpdated(fn () => $this->loadContents()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                IconColumn::make('type')
                    ->label('')
                    ->icon(fn (array $record): string => match ($record['type']) {
                        'directory' => 'heroicon-o-folder',
                        'file' => $this->getFileIcon($record['extension'] ?? ''),
                        default => 'heroicon-o-document',
                    })
                    ->color(fn (array $record): string => match ($record['type']) {
                        'directory' => 'warning',
                        'file' => $this->getFileColor($record['extension'] ?? ''),
                        default => 'gray',
                    })
                    ->size('lg'),
                
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                
                TextColumn::make('size')
                    ->label('Größe')
                    ->formatStateUsing(fn (?int $state): string => 
                        $state ? DigitalOceanSpacesService::formatFileSize($state) : '-'
                    )
                    ->alignEnd(),
                
                TextColumn::make('lastModified')
                    ->label('Geändert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                
                TextColumn::make('extension')
                    ->label('Typ')
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? strtoupper($state) : '-'
                    ),
            ])
            ->actions([
                Action::make('open')
                    ->label('Öffnen')
                    ->icon('heroicon-o-eye')
                    ->action(function (array $record) {
                        if ($record['type'] === 'directory') {
                            $this->navigateToDirectory($record['path']);
                        } else {
                            $this->openFile($record);
                        }
                    }),
                
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (array $record): bool => $record['type'] === 'file')
                    ->url(fn (array $record): string => $record['url'])
                    ->openUrlInNewTab(),
                
                Action::make('copyUrl')
                    ->label('URL kopieren')
                    ->icon('heroicon-o-clipboard')
                    ->visible(fn (array $record): bool => $record['type'] === 'file')
                    ->action(function (array $record) {
                        $this->js("
                            navigator.clipboard.writeText('{$record['url']}').then(() => {
                                window.dispatchEvent(new CustomEvent('notify', {
                                    detail: {
                                        message: 'URL wurde in die Zwischenablage kopiert',
                                        type: 'success'
                                    }
                                }));
                            });
                        ");
                    }),
            ])
            ->defaultSort('type', 'asc')
            ->defaultSort('name', 'asc')
            ->striped()
            ->paginated(false);
    }

    protected function getTableQuery()
    {
        $items = collect(array_merge($this->directories, $this->files));
        
        if ($this->search) {
            $items = $items->filter(function ($item) {
                return str_contains(strtolower($item['name']), strtolower($this->search));
            });
        }
        
        return $items;
    }

    public function navigateToDirectory(string $path): void
    {
        if (!$this->isConfigured()) {
            $this->dispatch('notify', [
                'message' => 'DigitalOcean Spaces ist nicht konfiguriert.',
                'type' => 'error'
            ]);
            return;
        }

        $this->currentPath = $path;
        $this->loadContents();
    }

    public function navigateUp(): void
    {
        if (!$this->isConfigured()) {
            $this->dispatch('notify', [
                'message' => 'DigitalOcean Spaces ist nicht konfiguriert.',
                'type' => 'error'
            ]);
            return;
        }

        if ($this->currentPath) {
            $pathParts = explode('/', trim($this->currentPath, '/'));
            array_pop($pathParts);
            $this->currentPath = implode('/', $pathParts);
            $this->loadContents();
        }
    }

    public function navigateToBreadcrumb(string $path): void
    {
        if (!$this->isConfigured()) {
            $this->dispatch('notify', [
                'message' => 'DigitalOcean Spaces ist nicht konfiguriert.',
                'type' => 'error'
            ]);
            return;
        }

        $this->currentPath = $path;
        $this->loadContents();
    }

    private function loadContents(): void
    {
        if (!$this->isConfigured() || !$this->spacesService) {
            $this->directories = [];
            $this->files = [];
            $this->breadcrumbs = [['name' => 'Root', 'path' => '']];
            return;
        }

        try {
            $contents = $this->spacesService->listContents($this->currentPath, false);
            
            $this->directories = array_filter($contents, fn ($item) => $item['type'] === 'directory');
            $this->files = array_filter($contents, fn ($item) => $item['type'] === 'file');
            
            $this->breadcrumbs = DigitalOceanSpacesService::getBreadcrumbs($this->currentPath);
            
        } catch (\Exception $e) {
            $this->directories = [];
            $this->files = [];
            $this->breadcrumbs = [['name' => 'Root', 'path' => '']];
            
            $this->dispatch('notify', [
                'message' => 'Fehler beim Laden der Dateien: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    private function openFile(array $file): void
    {
        $this->js("window.open('{$file['url']}', '_blank')");
    }

    private function getFileIcon(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'heroicon-o-document-text',
            'jpg', 'jpeg', 'png', 'gif', 'svg' => 'heroicon-o-photo',
            'doc', 'docx' => 'heroicon-o-document',
            'xls', 'xlsx' => 'heroicon-o-table-cells',
            'txt' => 'heroicon-o-document-text',
            'zip', 'rar', '7z' => 'heroicon-o-archive-box',
            'mp3', 'wav', 'ogg' => 'heroicon-o-musical-note',
            'mp4', 'avi', 'mkv' => 'heroicon-o-film',
            'csv' => 'heroicon-o-table-cells',
            'xml', 'json' => 'heroicon-o-code-bracket',
            default => 'heroicon-o-document',
        };
    }

    private function getFileColor(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'danger',
            'jpg', 'jpeg', 'png', 'gif', 'svg' => 'success',
            'doc', 'docx' => 'info',
            'xls', 'xlsx' => 'success',
            'txt' => 'gray',
            'zip', 'rar', '7z' => 'warning',
            'mp3', 'wav', 'ogg' => 'purple',
            'mp4', 'avi', 'mkv' => 'pink',
            'csv' => 'success',
            'xml', 'json' => 'indigo',
            default => 'gray',
        };
    }

    public function getCurrentPathDisplay(): string
    {
        return $this->currentPath ?: '/';
    }

    public function getTotalFilesCount(): int
    {
        return count($this->files);
    }

    public function getTotalDirectoriesCount(): int
    {
        return count($this->directories);
    }

    private function checkConfiguration(): void
    {
        try {
            $testService = new DigitalOceanSpacesService();
            $this->configurationStatus = $testService->getConfigurationStatus();
        } catch (\Exception $e) {
            $this->configurationStatus = $e->getMessage();
        }
    }

    private function initializeService(): void
    {
        try {
            $this->spacesService = new DigitalOceanSpacesService();
        } catch (\Exception $e) {
            $this->spacesService = null;
            $this->configurationStatus = $e->getMessage();
        }
    }

    public function isConfigured(): bool
    {
        return $this->configurationStatus === 'OK';
    }

    public function getConfigurationError(): ?string
    {
        return $this->isConfigured() ? null : $this->configurationStatus;
    }

    public function testConnection(): array
    {
        if (!$this->spacesService) {
            return [
                'success' => false,
                'message' => $this->getConfigurationError() ?? 'Service nicht initialisiert'
            ];
        }

        return $this->spacesService->testConnection();
    }

    public function refreshConfiguration(): void
    {
        $this->spacesService = null;
        $this->configurationStatus = null;
        $this->checkConfiguration();
        
        if ($this->isConfigured()) {
            $this->initializeService();
            $this->loadContents();
        }
        
        $this->dispatch('notify', [
            'message' => $this->isConfigured() 
                ? 'Konfiguration erfolgreich aktualisiert' 
                : 'Konfigurationsfehler: ' . $this->getConfigurationError(),
            'type' => $this->isConfigured() ? 'success' : 'error'
        ]);
    }
}
