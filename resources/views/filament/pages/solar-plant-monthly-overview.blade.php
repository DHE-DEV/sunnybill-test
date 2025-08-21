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
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
            <div style="background-color: #f9fafb; border: 1px solid #d1d5db; border-radius: 8px; padding: 1rem;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 32px; height: 32px; background-color: #6b7280; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                        <x-heroicon-o-minus-circle style="width: 20px; height: 20px; color: white;" />
                    </div>
                    <div>
                        <div style="font-size: 1.125rem; font-weight: 600; color: #111827;">
                            {{ $allPlantsStats['no_contracts'] }}
                        </div>
                        <div style="font-size: 0.875rem; color: #6b7280;">
                            Anlagen ohne Lieferantenverträge
                        </div>
                    </div>
                </div>
            </div>
            <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 1rem;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 32px; height: 32px; background-color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                        <x-heroicon-o-exclamation-triangle style="width: 20px; height: 20px; color: white;" />
                    </div>
                    <div>
                        <div style="font-size: 1.125rem; font-weight: 600; color: #7f1d1d;">
                            {{ $allPlantsStats['incomplete'] }}
                        </div>
                        <div style="font-size: 0.875rem; color: #b91c1c;">
                            Anlagen mit fehlende Lieferantenbelegen
                        </div>
                    </div>
                </div>
            </div>

            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 1rem;">
                <div style="display: flex; align-items: center;">
                    <div style="width: 32px; height: 32px; background-color: #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 1rem;">
                        <x-heroicon-o-check-circle style="width: 20px; height: 20px; color: white;" />
                    </div>
                    <div>
                        <div style="font-size: 1.125rem; font-weight: 600; color: #14532d;">
                            {{ $allPlantsStats['complete'] }}
                        </div>
                        <div style="font-size: 0.875rem; color: #16a34a;">
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

                @php
                    $cardStyle = match($status) {
                        'Vollständig' => 'background-color: #f0fdf4; border: 1px solid #bbf7d0;',
                        'Unvollständig' => 'background-color: #fef2f2; border: 1px solid #fecaca;',
                        'Keine Verträge' => 'background-color: #f9fafb; border: 1px solid #d1d5db;',
                        default => 'background-color: #f9fafb; border: 1px solid #d1d5db;',
                    };
                    $borderStyle = match($status) {
                        'Vollständig' => 'border-color: #bbf7d0;',
                        'Unvollständig' => 'border-color: #fecaca;',
                        'Keine Verträge' => 'border-color: #d1d5db;',
                        default => 'border-color: #d1d5db;',
                    };
                @endphp

                <div style="border-radius: 8px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); {{ $cardStyle }}">
                    <!-- Plant Header -->
                    <div style="padding: 1.5rem; border-bottom: 1px solid; {{ $borderStyle }}">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div>
                                    @if ($status === 'Vollständig')
                                        <div style="width: 40px; height: 40px; background-color: #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <x-heroicon-o-check-circle style="width: 24px; height: 24px; color: white;" />
                                        </div>
                                    @elseif ($status === 'Unvollständig')
                                        <div style="width: 40px; height: 40px; background-color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <x-heroicon-o-exclamation-triangle style="width: 24px; height: 24px; color: white;" />
                                        </div>
                                    @elseif ($status === 'Keine Verträge')
                                        <div style="width: 40px; height: 40px; background-color: #6b7280; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <x-heroicon-o-minus-circle style="width: 24px; height: 24px; color: white;" />
                                        </div>
                                    @else
                                        <div style="width: 40px; height: 40px; background-color: #6b7280; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <x-heroicon-o-question-mark-circle style="width: 24px; height: 24px; color: white;" />
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #111827;">
                                        {{ $plant->plant_number }} - {{ $plant->name }}
                                    </h3>
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.25rem;">
                                        @if ($plant->location)
                                            <p style="font-size: 0.875rem; color: #6b7280;">
                                                <x-heroicon-o-map-pin style="width: 16px; height: 16px; display: inline; margin-right: 0.25rem;" />
                                                {{ $plant->location }}
                                            </p>
                                        @endif
                                        <p style="font-size: 0.875rem; color: #6b7280;">
                                            <x-heroicon-o-document-text style="width: 16px; height: 16px; display: inline; margin-right: 0.25rem;" />
                                            {{ $totalContracts }} Verträge
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                @php
                                    $badgeStyle = match($status) {
                                        'Vollständig' => 'background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0;',
                                        'Unvollständig' => 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;',
                                        default => 'background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db;',
                                    };
                                @endphp
                                <div style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; {{ $badgeStyle }}">
                                    {{ $status }}
                                </div>
                                @if ($missingCount > 0)
                                    <div style="font-size: 0.875rem; color: #dc2626; margin-top: 0.25rem;">
                                        {{ $missingCount }} fehlende Abrechnung{{ $missingCount !== 1 ? 'en' : '' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Contract Details -->
                    @if ($totalContracts > 0)
                        <div style="padding: 1.5rem;">
                            <h4 style="font-size: 1rem; font-weight: 500; color: #111827; margin-bottom: 1rem;">
                                Vertragsdetails für {{ $monthLabel }}
                            </h4>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                @foreach ($activeContracts as $contract)
                                    @php
                                        $billing = $contract->billings()
                                            ->where('billing_year', $year)
                                            ->where('billing_month', $monthNumber)
                                            ->first();
                                        $hasBilling = $billing !== null;
                                        $contractUrl = '/admin/supplier-contracts/' . $contract->id . '?activeRelationManager=1';
                                        $billingUrl = $hasBilling ? '/admin/supplier-contract-billings/' . $billing->id : null;
                                        $supplierName = $contract->supplier ? $contract->supplier->display_name : 'Unbekannt';
                                        
                                        $contractStyle = $hasBilling 
                                            ? 'background-color: #f0fdf4; border: 1px solid #bbf7d0;'
                                            : 'background-color: #fef2f2; border: 1px solid #fecaca;';
                                    @endphp
                                    
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; border-radius: 6px; {{ $contractStyle }}">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div>
                                                @if ($hasBilling)
                                                    <x-heroicon-o-check-circle style="width: 20px; height: 20px; color: #22c55e;" />
                                                @else
                                                    <x-heroicon-o-x-circle style="width: 20px; height: 20px; color: #ef4444;" />
                                                @endif
                                            </div>
                                            <div>
                                                <p style="font-weight: 500; color: #111827;">
                                                    {{ $contract->title }}
                                                </p>
                                                <p style="font-size: 0.875rem; color: #6b7280;">
                                                    {{ $supplierName }} • Nr: {{ $contract->contract_number }}
                                                </p>
                                            </div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            @if ($hasBilling)
                                                <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #dcfce7; color: #166534;">
                                                    Abrechnung vorhanden
                                                </span>
                                            @else
                                                <span style="display: inline-flex; align-items: center; padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background-color: #fee2e2; color: #991b1b;">
                                                    Abrechnung fehlt
                                                </span>
                                            @endif
                                            <a href="{{ $contractUrl }}" target="_blank"
                                               style="display: inline-flex; align-items: center; padding: 0.375rem 0.75rem; border: 1px solid #d1d5db; font-size: 0.75rem; font-weight: 500; border-radius: 4px; color: #374151; background-color: #ffffff; text-decoration: none; transition: all 0.2s;"
                                               onmouseover="this.style.backgroundColor='#f9fafb';" 
                                               onmouseout="this.style.backgroundColor='#ffffff';">
                                                <x-heroicon-o-arrow-top-right-on-square style="width: 12px; height: 12px; margin-right: 0.5rem;" />
                                                Lieferantenvertrag
                                            </a>
                                            @if ($hasBilling && $billingUrl)
                                                <a href="{{ $billingUrl }}" target="_blank"
                                                   style="display: inline-flex; align-items: center; padding: 0.375rem 0.75rem; border: 1px solid #22c55e; font-size: 0.75rem; font-weight: 500; border-radius: 4px; color: #166534; background-color: #f0fdf4; text-decoration: none; transition: all 0.2s;"
                                                   onmouseover="this.style.backgroundColor='#dcfce7';" 
                                                   onmouseout="this.style.backgroundColor='#f0fdf4';">
                                                    <x-heroicon-o-document-text style="width: 12px; height: 12px; margin-right: 0.5rem;" />
                                                    Beleg
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div style="padding: 1.5rem;">
                            <div style="text-align: center; padding: 2rem 0;">
                                <x-heroicon-o-document-minus style="width: 32px; height: 32px; color: #9ca3af; margin: 0 auto 0.75rem auto; display: block;" />
                                <p style="color: #6b7280;">
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
