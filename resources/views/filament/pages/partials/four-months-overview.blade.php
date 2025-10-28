@php
    $months = $data['months'];
    $plantsData = $data['plantsData'];
@endphp

<div class="space-y-6">
    <!-- Header Information -->
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    4-Monats-Übersicht
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Übersicht über die letzten 4 Monate
                </p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ count($plantsData) }} Anlagen
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem;">
            <h3 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 0.75rem;">Legende:</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <x-heroicon-o-check-circle style="width: 16px; height: 16px; color: #22c55e;" />
                    <span style="font-size: 0.875rem; color: #6b7280;">Vollständig</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <x-heroicon-o-exclamation-triangle style="width: 16px; height: 16px; color: #ef4444;" />
                    <span style="font-size: 0.875rem; color: #6b7280;">Unvollständig</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <x-heroicon-o-minus-circle style="width: 16px; height: 16px; color: #6b7280;" />
                    <span style="font-size: 0.875rem; color: #6b7280;">Keine Verträge</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <x-heroicon-o-document-check style="width: 16px; height: 16px; color: #22c55e;" />
                    <span style="font-size: 0.875rem; color: #6b7280;">Anlagen-Abrechnung vorhanden</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Plants Overview Table -->
    <div class="bg-white dark:bg-gray-900 rounded-lg shadow overflow-hidden">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                    <tr>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #111827; white-space: nowrap;">
                            Anlage
                        </th>
                        @foreach ($months as $monthInfo)
                            <th style="padding: 1rem; text-align: center; font-weight: 600; color: #111827;">
                                {{ $monthInfo['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plantsData as $plantData)
                        @php
                            $plant = $plantData['plant'];
                            $monthsData = $plantData['monthsData'];
                        @endphp
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 1rem; font-weight: 500; color: #111827; white-space: nowrap;">
                                <div style="display: flex; flex-direction: column;">
                                    <span>{{ $plant->plant_number }}</span>
                                    <span style="font-size: 0.875rem; color: #6b7280; font-weight: normal;">{{ $plant->name }}</span>
                                </div>
                            </td>
                            @foreach ($monthsData as $monthIndex => $monthData)
                                @php
                                    $status = $monthData['status'];
                                    $totalContracts = $monthData['totalContracts'];
                                    $missingCount = $monthData['missingCount'];
                                    $hasPlantBillings = $monthData['hasPlantBillings'];
                                    $missingBillings = $monthData['missingBillings'];
                                    $activeContracts = $monthData['activeContracts'];
                                    $year = $monthData['year'];
                                    $monthNumber = $monthData['monthNumber'];

                                    // Berechne erfasste Abrechnungen = Gesamt-Verträge - fehlende Abrechnungen
                                    $completedBillings = $totalContracts - $missingCount;

                                    $cellStyle = match($status) {
                                        'Vollständig' => 'background-color: #dcfce7;',
                                        'Unvollständig' => 'background-color: #fee2e2;',
                                        'Keine Verträge' => 'background-color: #f3f4f6;',
                                        default => 'background-color: #ffffff;',
                                    };

                                    $iconColor = match($status) {
                                        'Vollständig' => '#22c55e',
                                        'Unvollständig' => '#ef4444',
                                        default => '#6b7280',
                                    };

                                    $uniqueId = 'details-' . $plant->id . '-' . $monthIndex;
                                @endphp
                                <td style="padding: 0.75rem; text-align: center; {{ $cellStyle }} position: relative;">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.25rem;">
                                        @if ($status === 'Vollständig')
                                            <x-heroicon-o-check-circle style="width: 20px; height: 20px; color: {{ $iconColor }};" />
                                        @elseif ($status === 'Unvollständig')
                                            <x-heroicon-o-exclamation-triangle style="width: 20px; height: 20px; color: {{ $iconColor }};" />
                                        @else
                                            <x-heroicon-o-minus-circle style="width: 20px; height: 20px; color: {{ $iconColor }};" />
                                        @endif
                                        @if ($totalContracts > 0)
                                            <button
                                                onclick="document.getElementById('{{ $uniqueId }}').classList.toggle('hidden')"
                                                style="font-size: 0.75rem; color: #6b7280; cursor: pointer; border: none; background: none; text-decoration: underline; padding: 0;"
                                                title="Details anzeigen"
                                            >
                                                {{ $completedBillings }} / {{ $totalContracts }}
                                            </button>
                                        @endif
                                        @if ($hasPlantBillings)
                                            <x-heroicon-o-document-check style="width: 16px; height: 16px; color: #22c55e;" title="Anlagen-Abrechnung vorhanden" />
                                        @else
                                            <x-heroicon-o-document-minus style="width: 16px; height: 16px; color: #9ca3af;" title="Keine Anlagen-Abrechnung" />
                                        @endif
                                    </div>

                                    <!-- Details Popup -->
                                    @if ($missingCount > 0 || $totalContracts > 0)
                                        <div
                                            id="{{ $uniqueId }}"
                                            class="hidden"
                                            style="position: absolute; top: 100%; left: 50%; transform: translateX(-50%); z-index: 1000; background-color: white; border: 1px solid #d1d5db; border-radius: 8px; padding: 1rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); min-width: 300px; max-width: 400px; margin-top: 0.5rem;"
                                        >
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                                <h4 style="font-weight: 600; font-size: 0.875rem; color: #111827;">
                                                    {{ $monthData['monthLabel'] }} - Verträge
                                                </h4>
                                                <button
                                                    onclick="document.getElementById('{{ $uniqueId }}').classList.add('hidden')"
                                                    style="border: none; background: none; cursor: pointer; color: #6b7280; padding: 0;"
                                                >
                                                    <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                                                </button>
                                            </div>

                                            @if ($missingCount > 0)
                                                <div style="margin-bottom: 0.75rem;">
                                                    <p style="font-weight: 500; font-size: 0.75rem; color: #ef4444; margin-bottom: 0.5rem;">
                                                        Fehlende Abrechnungen ({{ $missingCount }}):
                                                    </p>
                                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                        @foreach ($missingBillings as $contract)
                                                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background-color: #fef2f2; border-radius: 4px; font-size: 0.75rem;">
                                                                <div style="flex: 1;">
                                                                    <div style="font-weight: 500; color: #111827;">{{ $contract->title }}</div>
                                                                    <div style="color: #6b7280;">{{ $contract->supplier ? $contract->supplier->display_name : 'Unbekannt' }}</div>
                                                                </div>
                                                                <a href="/admin/supplier-contracts/{{ $contract->id }}?activeRelationManager=1"
                                                                   target="_blank"
                                                                   style="padding: 0.25rem 0.5rem; background-color: white; border: 1px solid #d1d5db; border-radius: 4px; text-decoration: none; color: #374151; font-size: 0.625rem;"
                                                                   onclick="event.stopPropagation();"
                                                                >
                                                                    Öffnen
                                                                </a>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if ($completedBillings > 0)
                                                <div>
                                                    <p style="font-weight: 500; font-size: 0.75rem; color: #22c55e; margin-bottom: 0.5rem;">
                                                        Erfasste Abrechnungen ({{ $completedBillings }}):
                                                    </p>
                                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                        @foreach ($activeContracts as $contract)
                                                            @php
                                                                $billing = $contract->billings()
                                                                    ->where('billing_year', $year)
                                                                    ->where('billing_month', $monthNumber)
                                                                    ->first();
                                                            @endphp
                                                            @if ($billing)
                                                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background-color: #f0fdf4; border-radius: 4px; font-size: 0.75rem;">
                                                                    <div style="flex: 1;">
                                                                        <div style="font-weight: 500; color: #111827;">{{ $contract->title }}</div>
                                                                        <div style="color: #6b7280;">{{ $contract->supplier ? $contract->supplier->display_name : 'Unbekannt' }}</div>
                                                                    </div>
                                                                    <a href="/admin/supplier-contract-billings/{{ $billing->id }}"
                                                                       target="_blank"
                                                                       style="padding: 0.25rem 0.5rem; background-color: white; border: 1px solid #d1d5db; border-radius: 4px; text-decoration: none; color: #374151; font-size: 0.625rem;"
                                                                       onclick="event.stopPropagation();"
                                                                    >
                                                                        Beleg
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($months) + 1 }}" style="padding: 3rem; text-align: center;">
                                <x-heroicon-o-document-magnifying-glass style="width: 48px; height: 48px; color: #9ca3af; margin: 0 auto 0.75rem auto; display: block;" />
                                <p style="color: #6b7280;">Keine Solaranlagen gefunden</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
