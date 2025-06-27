<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Statistiken -->
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            @php $stats = $this->getStats() @endphp
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-users class="h-8 w-8 text-blue-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Kunden</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['customers'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-building-office class="h-8 w-8 text-orange-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Lieferanten</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['suppliers'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-sun class="h-8 w-8 text-yellow-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Solaranlagen</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['solar_plants'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-link class="h-8 w-8 text-green-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Zuordnungen</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['assignments'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-document-text class="h-8 w-8 text-purple-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Rechnungen</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['invoices'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-cube class="h-8 w-8 text-indigo-600" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Artikel</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['articles'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informationen -->
        @php $info = $this->getTestDataInfo() @endphp
        
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <x-heroicon-o-information-circle class="inline h-5 w-5 mr-2" />
                    Testdaten-Informationen
                </h3>
            </div>
            <div class="p-6">
                <p class="text-gray-600 mb-4">{{ $info['description'] }}</p>
                
                <h4 class="font-medium text-gray-900 mb-3">Erstellt werden:</h4>
                <ul class="space-y-2 text-sm text-gray-600">
                    @foreach($info['features'] as $feature)
                        <li class="flex items-start">
                            <x-heroicon-o-check-circle class="h-4 w-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Reset-Warnung -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Wichtiger Hinweis zum Daten-Reset
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>{{ $info['reset_info'] }}</p>
                        <p class="mt-2 font-medium">Diese Aktion kann nicht rückgängig gemacht werden!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Konsistenz-Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-arrow-path class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">
                        Konsistente Testdaten
                    </h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Die Testdaten werden immer mit den gleichen Einstellungen erstellt:</p>
                        <ul class="mt-2 space-y-1">
                            <li>• Gleiche Kundennamen und E-Mail-Adressen</li>
                            <li>• Identische Solaranlagen-Konfigurationen</li>
                            <li>• Reproduzierbare Zuordnungen und Anteile</li>
                            <li>• Konsistente Artikel und Preise</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>