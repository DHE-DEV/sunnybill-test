<div x-data x-init="
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            $wire.$refresh();
        }
    })
">
    @php
        $monthNames = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
        ];
    @endphp

    @if(empty($months))
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
            <x-heroicon-o-document-currency-euro class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Keine Abrechnungen vorhanden</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Es wurden noch keine Abrechnungen für diese Solaranlage erstellt.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-400">Monat</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-400">Belege</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-400">Kunden</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-400">Abgerechnete kWp</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-400">Kosten (brutto)</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-400">Erlöse (brutto)</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-400">Nettobetrag</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-400">Status</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600 dark:text-gray-400">Aktion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($months as $m)
                        <tr class="{{ $m['has_billings'] ? 'hover:bg-gray-50 dark:hover:bg-gray-800/50' : 'bg-yellow-50/50 dark:bg-yellow-900/10 hover:bg-yellow-50 dark:hover:bg-yellow-900/20' }}">
                            {{-- Monat --}}
                            <td class="px-4 py-3">
                                <span class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $monthNames[$m['month']] }} {{ $m['year'] }}
                                </span>
                            </td>

                            {{-- Belege-Status --}}
                            <td class="px-4 py-3 text-center">
                                @if($m['total_contracts'] > 0)
                                    @if($m['all_contracts_have_billings'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                            {{ $m['contracts_with_billings'] }}/{{ $m['total_contracts'] }}
                                        </span>
                                    @elseif($m['contracts_with_billings'] > 0)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                            {{ $m['contracts_with_billings'] }}/{{ $m['total_contracts'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            0/{{ $m['total_contracts'] }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Kunden --}}
                            <td class="px-4 py-3 text-center">
                                @if($m['has_billings'])
                                    <span class="inline-flex items-center rounded-full bg-primary-100 px-2.5 py-1 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
                                        {{ $m['billings_count'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Abgerechnete kWp --}}
                            <td class="px-4 py-3 text-right">
                                @if($m['has_billings'])
                                    <span class="inline-flex items-center rounded-full bg-success-100 px-2.5 py-1 text-xs font-medium text-success-800 dark:bg-success-900 dark:text-success-300">
                                        {{ number_format($m['billed_kwp'], 3, ',', '.') }} kWp
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Kosten --}}
                            <td class="px-4 py-3 text-right">
                                @if($m['has_billings'])
                                    <span class="text-red-600 dark:text-red-400">{{ number_format($m['total_costs_sum'], 2, ',', '.') }} &euro;</span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Erlöse --}}
                            <td class="px-4 py-3 text-right">
                                @if($m['has_billings'])
                                    <span class="text-green-600 dark:text-green-400">{{ number_format($m['total_credits_sum'], 2, ',', '.') }} &euro;</span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Nettobetrag --}}
                            <td class="px-4 py-3 text-right">
                                @if($m['has_billings'])
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $m['net_amount_sum'] >= 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                                        {{ number_format($m['net_amount_sum'], 2, ',', '.') }} &euro;
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3 text-center">
                                @if($m['has_billings'])
                                    <div class="flex flex-wrap items-center justify-center gap-1">
                                        @if($m['draft_count'] > 0)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $m['draft_count'] }} Entw.</span>
                                        @endif
                                        @if($m['finalized_count'] > 0)
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">{{ $m['finalized_count'] }} Fin.</span>
                                        @endif
                                        @if($m['sent_count'] > 0)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300">{{ $m['sent_count'] }} Vers.</span>
                                        @endif
                                        @if($m['paid_count'] > 0)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">{{ $m['paid_count'] }} Bez.</span>
                                        @endif
                                        @if($m['cancelled_count'] > 0)
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300">{{ $m['cancelled_count'] }} Stor.</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                        Nicht abgerechnet
                                    </span>
                                @endif
                            </td>

                            {{-- Aktion --}}
                            <td class="px-4 py-3 text-center">
                                <x-filament::dropdown teleport>
                                    <x-slot name="trigger">
                                        <x-filament::button color="gray" icon="heroicon-m-ellipsis-vertical" size="sm">
                                            Aktionen
                                        </x-filament::button>
                                    </x-slot>

                                    <x-filament::dropdown.list>
                                        <x-filament::dropdown.list.item
                                            icon="heroicon-o-eye"
                                            color="info"
                                            wire:click="openDetail({{ $m['year'] }}, {{ $m['month'] }})"
                                        >
                                            Details anzeigen
                                        </x-filament::dropdown.list.item>

                                        @if($m['all_contracts_have_billings'] && $m['finalized_count'] == 0 && $m['sent_count'] == 0 && $m['paid_count'] == 0)
                                            <x-filament::dropdown.list.item
                                                icon="heroicon-o-plus-circle"
                                                color="success"
                                                wire:click="openCreateBilling({{ $m['year'] }}, {{ $m['month'] }})"
                                            >
                                                Abrechnung erstellen
                                            </x-filament::dropdown.list.item>
                                        @endif
                                    </x-filament::dropdown.list>
                                </x-filament::dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Flash Messages --}}
    @if(session()->has('billing-success'))
        <div class="rounded-lg border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 p-4 mt-4">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('billing-success') }}</p>
            </div>
        </div>
    @endif
    @if(session()->has('billing-error'))
        <div class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4 mt-4">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ session('billing-error') }}</p>
            </div>
        </div>
    @endif

    {{-- Create Billing Modal --}}
    @if($showCreateBilling)
        @php
            $createMonthNames = [
                1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
                5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
            ];
        @endphp
        <div style="position: fixed; inset: 0; z-index: 999999; overflow-y: auto;" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                {{-- Backdrop --}}
                <div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 999999;" wire:click="closeCreateBilling"></div>

                {{-- Modal --}}
                <div style="position: relative; z-index: 1000000;" class="w-full max-w-xl transform rounded-xl bg-white dark:bg-gray-900 shadow-2xl transition-all">
                    {{-- Header --}}
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Monatliche Abrechnungen erstellen
                        </h3>
                        <button wire:click="closeCreateBilling" class="rounded-md p-1 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="px-6 py-4 space-y-4">
                        {{-- Solaranlage (readonly) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Solaranlage</label>
                            <div class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                                {{ $solarPlant->name }}
                            </div>
                        </div>

                        {{-- Monat (readonly) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Abrechnungsmonat</label>
                            <div class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                                {{ $createMonthNames[$createBillingMonth] }} {{ $createBillingYear }}
                            </div>
                        </div>

                        {{-- Gesamtleistung (readonly) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gesamtleistung der Anlage</label>
                            <div class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                                {{ number_format($solarPlant->total_capacity_kw, 3, ',', '.') }} kWp
                            </div>
                        </div>

                        {{-- Produzierte Energie --}}
                        <div>
                            <label for="producedEnergyKwh" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Produzierte Energie (kWh)</label>
                            <div class="relative">
                                <input
                                    type="number"
                                    id="producedEnergyKwh"
                                    wire:model="producedEnergyKwh"
                                    step="0.001"
                                    min="0"
                                    placeholder="z.B. 2500.000"
                                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm pr-14"
                                >
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 sm:text-sm">kWh</span>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Gesamte produzierte Energie der Solaranlage für diesen Monat</p>
                        </div>

                        {{-- Bemerkung --}}
                        <div>
                            <label for="billingNotes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bemerkung</label>
                            <textarea
                                id="billingNotes"
                                wire:model="billingNotes"
                                rows="4"
                                maxlength="2000"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Wird auf allen PDF-Abrechnungen unter der Gesamtsumme angezeigt.</p>
                        </div>

                        {{-- Hinweistext Toggle --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <label for="showHints" class="text-sm font-medium text-gray-700 dark:text-gray-300">Hinweistext auf PDF anzeigen</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Wenn deaktiviert, wird der Hinweistext am Ende der PDF nicht angezeigt</p>
                            </div>
                            <button
                                type="button"
                                wire:click="$toggle('showHints')"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 {{ $showHints ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                                role="switch"
                                aria-checked="{{ $showHints ? 'true' : 'false' }}"
                            >
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $showHints ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-3">
                        <button wire:click="closeCreateBilling" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            Abbrechen
                        </button>
                        <button wire:click="createBilling" class="rounded-md bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-700 shadow-sm">
                            <span wire:loading.remove wire:target="createBilling">Abrechnungen erstellen</span>
                            <span wire:loading wire:target="createBilling" class="inline-flex items-center gap-1">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Wird erstellt...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Detail Modal --}}
    @if($showDetail && $detailData)
        <div style="position: fixed; inset: 0; z-index: 999999; overflow-y: auto;" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                {{-- Backdrop --}}
                <div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 999999;" wire:click="closeDetail"></div>

                {{-- Modal --}}
                <div style="position: relative; z-index: 1000000;" class="w-full max-w-5xl transform rounded-xl bg-white dark:bg-gray-900 shadow-2xl transition-all">
                    {{-- Header --}}
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100" id="modal-title">
                            Abrechnungsdetails {{ $monthNames[$detailData['billingMonth']] }} {{ $detailData['billingYear'] }}
                        </h3>
                        <button wire:click="closeDetail" class="rounded-md p-1 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="max-h-[75vh] overflow-y-auto px-6 py-4">
                        @include('livewire.billings-monthly-detail', [
                            'contractData' => $detailData['contractData'],
                            'customerArticlesData' => $detailData['customerArticlesData'],
                            'customerBillings' => $detailData['customerBillings'],
                            'previewBillings' => $detailData['previewBillings'],
                            'participations' => $detailData['participations'],
                            'billingYear' => $detailData['billingYear'],
                            'billingMonth' => $detailData['billingMonth'],
                        ])
                    </div>

                    {{-- Footer --}}
                    <div class="flex justify-end border-t border-gray-200 dark:border-gray-700 px-6 py-3">
                        <button wire:click="closeDetail" class="rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            Schließen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
