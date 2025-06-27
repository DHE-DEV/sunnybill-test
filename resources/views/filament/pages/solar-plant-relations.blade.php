<div class="space-y-6">
    <!-- Projektmanagement & Verwaltung Section -->
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-clipboard-document-list class="w-5 h-5 text-gray-500" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Projektmanagement & Verwaltung
                </h3>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- Projekttermine -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        <x-heroicon-o-calendar class="w-4 h-4" />
                        <span>Projekttermine</span>
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @livewire(\App\Filament\Resources\SolarPlantResource\RelationManagers\MilestonesRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>

                <!-- Notizen - Favoriten -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        <x-heroicon-o-star class="w-4 h-4" />
                        <span>Notizen - Favoriten</span>
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @livewire(\App\Filament\Resources\SolarPlantResource\RelationManagers\FavoriteNotesRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>

                <!-- Notizen - Standard -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        <x-heroicon-o-document-text class="w-4 h-4" />
                        <span>Notizen - Standard</span>
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @livewire(\App\Filament\Resources\SolarPlantResource\RelationManagers\StandardNotesRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>

                <!-- Lieferanten & Dienstleister -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        <x-heroicon-o-building-office class="w-4 h-4" />
                        <span>Lieferanten & Dienstleister</span>
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @livewire(\App\Filament\Resources\SolarPlantResource\RelationManagers\SuppliersRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>

                <!-- Kundenbeteiligungen -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        <x-heroicon-o-users class="w-4 h-4" />
                        <span>Kundenbeteiligungen</span>
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @livewire(\App\Filament\Resources\SolarPlantResource\RelationManagers\ParticipationsRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>

                <!-- Monatliche Ergebnisse -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        <x-heroicon-o-chart-bar class="w-4 h-4" />
                        <span>Monatliche Ergebnisse</span>
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @livewire(\App\Filament\Resources\SolarPlantResource\RelationManagers\MonthlyResultsRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hardware-Komponenten Section -->
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-cpu-chip class="w-5 h-5 text-gray-500" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Hardware-Komponenten
                </h3>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Wechselrichter -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        <x-heroicon-o-bolt class="w-4 h-4" />
                        <span>Wechselrichter</span>
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @livewire(\App\Filament\Resources\SolarPlantResource\RelationManagers\InvertersRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>

                <!-- Solarpanels -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                        <span>Solarpanels</span>
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @livewire(\App\Filament\Resources\SolarPlantResource\RelationManagers\PanelsRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>

                <!-- Batterien -->
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        <x-heroicon-o-battery-100 class="w-4 h-4" />
                        <span>Batterien</span>
                    </h4>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        @livewire(\App\Filament\Resources\SolarPlantResource\RelationManagers\BatteriesRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>