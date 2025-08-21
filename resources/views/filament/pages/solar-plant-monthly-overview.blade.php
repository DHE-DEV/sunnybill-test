<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $selectedMonth = $data['selectedMonth'];
        $monthLabel = $data['monthLabel'];
        $plantsData = $data['plantsData'];
        $allPlantsStats = $data['allPlantsStats'];
        $statusFilter = $data['statusFilter'] ?? 'all';
        $year = (int) substr($selectedMonth, 0, 4);
        $monthNumber = (int) substr($selectedMonth, 5, 2);
        
        $filterLabel = match($statusFilter) {
            'incomplete' => 'Nur Unvollständige',
            'complete' => 'Nur Vollständige', 
            'no_contracts' => 'Nur ohne Verträge',
            default => 'Alle Anlagen'
        };
    @endphp

    <div class="space-y-6">
        <!-- Header Information -->
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Abrechnungsübersicht für {{ $monthLabel }}
                    </h2>
                    <div class="flex items-center space-x-2 mt-1">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $filterLabel }}
                        </p>
                        @if ($statusFilter !== 'all')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/20 dark:text-blue-400">
                                Gefiltert
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $allPlantsStats['total'] }} Anlagen
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <!-- {{ $allPlantsStats['incomplete'] }} unvollständig -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-minus-circle class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-300">
                            {{ $allPlantsStats['no_contracts'] }}
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-400">
                            Anlagen ohne Lieferantenverträge
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-lg font-semibold text-red-900 dark:text-red-300">
                            {{ $allPlantsStats['incomplete'] }}
                        </div>
                        <div class="text-sm text-red-700 dark:text-red-400">
                            Anlagen mit fehlende Lieferantenbelegen
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-white" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-lg font-semibold text-green-900 dark:text-green-300">
                            {{ $allPlantsStats['complete'] }}
                        </div>
                        <div class="text-sm text-green-700 dark:text-green-400">
                            Anlagen mit komplett erfassten Lieferantenbelegen
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Solar Plants List -->
        <div class="space-y-4">
            @forelse ($plantsData as $plantData)
                @php
                    $plant = $plantData['plant'];
                    $status = $plantData['status'];
                    $missingBillings = $plantData['missingBillings'];
                    $activeContracts = $plantData['activeContracts'];
                    $totalContracts = $plantData['totalContracts'];
                    $missingCount = $plantData['missingCount'];
                    
                    $statusConfig = match($status) {
                        'Vollständig' => [
                            'color' => 'green',
                            'icon' => 'check-circle',
                            'bg' => 'bg-green-50 dark:bg-green-900/20',
                            'border' => 'border-green-200 dark:border-green-800',
                        ],
                        'Unvollständig' => [
                            'color' => 'red',
                            'icon' => 'exclamation-triangle',
                            'bg' => 'bg-red-50 dark:bg-red-900/20',
                            'border' => 'border-red-200 dark:border-red-800',
                        ],
                        'Keine Verträge' => [
                            'color' => 'gray',
                            'icon' => 'minus-circle',
                            'bg' => 'bg-gray-50 dark:bg-gray-900',
                            'border' => 'border-gray-200 dark:border-gray-700',
                        ],
                        default => [
                            'color' => 'gray',
                            'icon' => 'question-mark-circle',
                            'bg' => 'bg-gray-50 dark:bg-gray-900',
                            'border' => 'border-gray-200 dark:border-gray-700',
                        ],
                    };
                @endphp

                <div class="rounded-lg shadow {{ $statusConfig['bg'] }} {{ $statusConfig['border'] }} border">
                    <!-- Plant Header -->
                    <div class="p-6 border-b {{ $statusConfig['border'] }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">a
                                    <div class="w-10 h-10 bg-{{ $statusConfig['color'] }}-500 rounded-full flex items-center justify-center">
                                        @if ($statusConfig['icon'] === 'check-circle')
                                            <x-heroicon-o-check-circle class="w-6 h-6 text-white" />
                                        @elseif ($statusConfig['icon'] === 'exclamation-triangle')
                                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-white" />
                                        @elseif ($statusConfig['icon'] === 'minus-circle')
                                            <x-heroicon-o-minus-circle class="w-6 h-6 text-white" />
                                        @else
                                            <x-heroicon-o-question-mark-circle class="w-6 h-6 text-white" />
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $plant->plant_number }} - {{ $plant->name }}
                                    </h3>
                                    <div class="flex items-center space-x-4 mt-1">
                                        @if ($plant->location)
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                <x-heroicon-o-map-pin class="w-4 h-4 inline" />
                                                {{ $plant->location }}
                                            </p>
                                        @endif
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            <x-heroicon-o-document-text class="w-4 h-4 inline" />
                                            {{ $totalContracts }} Verträge
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                    @if($status === 'Vollständig') bg-green-100 text-green-800 dark:bg-green-800/20 dark:text-green-400
                                    @elseif($status === 'Unvollständig') bg-red-100 text-red-800 dark:bg-red-800/20 dark:text-red-400
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-800/20 dark:text-gray-400 @endif">
                                    {{ $status }}
                                </div>
                                @if ($missingCount > 0)
                                    <div class="text-sm text-red-600 dark:text-red-400 mt-1">
                                        {{ $missingCount }} fehlende Abrechnung{{ $missingCount !== 1 ? 'en' : '' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Contract Details -->
                    @if ($totalContracts > 0)
                        <div class="p-6">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                                Vertragsdetails für {{ $monthLabel }}
                            </h4>
                            <div class="space-y-3">
                                @foreach ($activeContracts as $contract)
                                    @php
                                        $hasBilling = $contract->billings()
                                            ->where('billing_year', $year)
                                            ->where('billing_month', $monthNumber)
                                            ->exists();
                                        $contractUrl = '/admin/supplier-contracts/' . $contract->id . '?activeRelationManager=1';
                                        $supplierName = $contract->supplier ? $contract->supplier->display_name : 'Unbekannt';
                                    @endphp
                                    
                                    <div class="flex items-center justify-between p-3 rounded-md 
                                        @if($hasBilling) bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800
                                        @else bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 @endif">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                @if ($hasBilling)
                                                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                                                @else
                                                    <x-heroicon-o-x-circle class="w-5 h-5 text-red-500" />
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white">
                                                    {{ $contract->title }}
                                                </p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $supplierName }} • Nr: {{ $contract->contract_number }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            @if ($hasBilling)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/20 dark:text-green-400">
                                                    Abrechnung vorhanden
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/20 dark:text-red-400">
                                                    Abrechnung fehlt
                                                </span>
                                            @endif
                                            <a href="{{ $contractUrl }}" target="_blank"
                                               class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                                <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3 mr-2" />
                                                &nbsp; Lieferantenvertrag
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="p-6">
                            <div class="text-center py-8">
                                <x-heroicon-o-document-minus class="w-8 h-8 text-gray-400 dark:text-gray-600 mx-auto mb-3" />
                                <p class="text-gray-600 dark:text-gray-400">
                                    Keine aktiven Lieferantenverträge vorhanden
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-12 bg-white dark:bg-gray-900 rounded-lg shadow">
                    <x-heroicon-o-document-magnifying-glass class="w-16 h-16 text-gray-400 dark:text-gray-600 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        Keine Solaranlagen gefunden
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Es wurden keine Solaranlagen in der Datenbank gefunden.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
