<div class="fi-resource-relation-manager flex flex-col gap-y-6">
    <x-filament-panels::resources.tabs />

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_RELATION_MANAGER_BEFORE, scopes: $this->getRenderHookScopes()) }}

    {{-- Missing Billings Overview --}}
    @if ($missingBillingsOverview['totalMissing'] > 0)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-600 dark:bg-amber-950/30">
            <div class="flex items-center gap-x-2 mb-3">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-amber-500 shrink-0" />
                <h3 class="text-base font-semibold text-amber-800 dark:text-amber-200">
                    Fehlende Abrechnungen ({{ $missingBillingsOverview['totalMissing'] }})
                </h3>
            </div>

            <div class="space-y-3">
                @foreach ($missingBillingsOverview['participations'] as $participation)
                    <div class="rounded-lg border border-amber-200 bg-white p-3 dark:border-amber-700/50 dark:bg-gray-800">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-sm text-gray-900 dark:text-gray-100">
                                {{ $participation['plantNumber'] }} - {{ $participation['plantName'] }} ({{ $participation['percentage'] }}%)
                            </span>
                            <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">
                                {{ $participation['missingCount'] }} ausstehend
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-3 text-xs text-gray-600 dark:text-gray-400 mb-2">
                            @if ($participation['lastBillingLabel'])
                                <span>Letzte Abrechnung: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $participation['lastBillingLabel'] }}</span></span>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                            @endif
                            <span>Nächste fällig: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $participation['nextBillingLabel'] }}</span></span>
                        </div>

                        {{-- Ausstehende Monate als Badges --}}
                        <div class="flex flex-wrap gap-1">
                            @foreach ($participation['pendingMonths'] as $month)
                                @if ($month['status'] === 'ready')
                                    <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/30" title="Kann erstellt werden - alle Lieferantenbelege vorhanden">
                                        {{ $month['label'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/30" title="Blockiert - Lieferantenbelege fehlen">
                                        {{ $month['label'] }}
                                    </span>
                                @endif
                            @endforeach
                        </div>

                        {{-- Fehlende Lieferantenbelege (letzte 6 Monate inline, kompaktes Grid) --}}
                        @if (!empty($participation['recentMissingSupplierBillings']))
                            <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Fehlende Lieferantenbelege:</p>
                                <div class="grid gap-1.5 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                                    @foreach ($participation['recentMissingSupplierBillings'] as $monthLabel => $missingBillings)
                                        <div class="rounded border border-red-200 bg-red-50/50 px-1.5 py-1 dark:border-red-800/50 dark:bg-red-950/20">
                                            <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-0.5">{{ $monthLabel }}</p>
                                            @foreach ($missingBillings as $billing)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 leading-tight truncate" title="{{ $billing['contractTitle'] }} ({{ $billing['supplierName'] }})">{{ $billing['contractTitle'] }}</p>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Ältere fehlende Lieferantenbelege (>6 Monate, per Klick) --}}
                        @if (!empty($participation['olderMissingSupplierBillings']))
                            @php
                                $olderMonthCount = count($participation['olderMissingSupplierBillings']);
                            @endphp
                            <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700" x-data="{ open: false }">
                                <button
                                    type="button"
                                    class="text-xs text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 font-medium flex items-center gap-1"
                                    x-on:click="open = true"
                                >
                                    <x-heroicon-m-eye class="h-3.5 w-3.5" />
                                    {{ $olderMonthCount }} weitere Monate mit fehlenden Belegen anzeigen
                                </button>

                                <template x-teleport="body">
                                    <div
                                        x-show="open"
                                        x-transition.opacity
                                        x-on:keydown.escape.window="open = false"
                                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                        style="display: none;"
                                    >
                                        <div class="absolute inset-0 bg-black/50" x-on:click="open = false"></div>
                                        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col">
                                            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                    Fehlende Lieferantenbelege &mdash; {{ $participation['plantNumber'] }}
                                                </h4>
                                                <button type="button" x-on:click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                    <x-heroicon-m-x-mark class="h-5 w-5" />
                                                </button>
                                            </div>
                                            <div class="p-4 overflow-y-auto">
                                                <div class="grid gap-1.5 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
                                                    @foreach ($participation['olderMissingSupplierBillings'] as $monthLabel => $missingBillings)
                                                        <div class="rounded border border-red-200 bg-red-50/50 px-1.5 py-1 dark:border-red-800/50 dark:bg-red-950/20">
                                                            <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-0.5">{{ $monthLabel }}</p>
                                                            @foreach ($missingBillings as $billing)
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 leading-tight truncate" title="{{ $billing['contractTitle'] }} ({{ $billing['supplierName'] }})">{{ $billing['contractTitle'] }}</p>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @elseif ($missingBillingsOverview['hasParticipations'])
        <div class="rounded-xl border border-green-300 bg-green-50 p-4 dark:border-green-600 dark:bg-green-950/30">
            <div class="flex items-center gap-x-2">
                <x-heroicon-o-check-circle class="h-5 w-5 text-green-500 shrink-0" />
                <span class="text-sm font-medium text-green-800 dark:text-green-200">
                    Alle Abrechnungen vollständig
                </span>
            </div>
        </div>
    @endif

    {{ $this->table }}

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_RELATION_MANAGER_AFTER, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::unsaved-action-changes-alert />
</div>
