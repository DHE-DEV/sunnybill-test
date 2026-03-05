<div class="space-y-6 p-4">
    @php
        $monthNames = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
            5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
        ];
        $statusLabels = [
            'draft' => 'Entwurf',
            'captured' => 'Erfasst',
            'pending' => 'Ausstehend',
            'approved' => 'Genehmigt',
            'paid' => 'Bezahlt',
            'cancelled' => 'Storniert',
        ];
        $statusColors = [
            'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            'captured' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        ];
        $billingTypeLabels = [
            'invoice' => 'Rechnung',
            'credit_note' => 'Gutschrift',
        ];
        $billingTypeColors = [
            'invoice' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'credit_note' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        ];

        $allComplete = $contractData->every(fn ($item) => $item['has_billings']);
        $totalContracts = $contractData->count();
        $contractsWithBillings = $contractData->where('has_billings', true)->count();
    @endphp

    {{-- Zusammenfassung --}}
    <div class="flex items-center gap-4 rounded-lg border p-4 {{ $allComplete ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950' : 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-950' }}">
        <div class="flex-shrink-0">
            @if($allComplete)
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            @else
                <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            @endif
        </div>
        <div>
            <p class="font-semibold {{ $allComplete ? 'text-green-800 dark:text-green-200' : 'text-yellow-800 dark:text-yellow-200' }}">
                {{ $contractsWithBillings }} von {{ $totalContracts }} Verträgen haben Belege für {{ $monthNames[$billingMonth] }} {{ $billingYear }}
            </p>
            @if(!$allComplete)
                <p class="text-sm {{ 'text-yellow-700 dark:text-yellow-300' }}">
                    Es fehlen noch {{ $totalContracts - $contractsWithBillings }} Beleg(e), um die Kundenabrechnung erstellen zu können.
                </p>
            @endif
        </div>
    </div>

    {{-- Verträge --}}
    <div class="space-y-4">
        @forelse($contractData as $item)
            @php
                $contract = $item['contract'];
                $billings = $item['billings'];
                $hasBillings = $item['has_billings'];
                $percentage = $item['percentage'];
                $supplierName = $contract->supplier?->company_name ?: ($contract->supplier?->name ?? 'Unbekannt');
            @endphp

            <div class="rounded-lg border {{ $hasBillings ? 'border-gray-200 dark:border-gray-700' : 'border-yellow-300 dark:border-yellow-700' }} overflow-hidden">
                {{-- Vertragskopf --}}
                <div class="flex items-center justify-between gap-4 px-4 py-3 {{ $hasBillings ? 'bg-gray-50 dark:bg-gray-800' : 'bg-yellow-50 dark:bg-yellow-900/30' }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex-shrink-0">
                            @if($hasBillings)
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                    <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                                    <svg class="h-4 w-4 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                    </svg>
                                </span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('filament.admin.resources.supplier-contracts.view', $contract) }}"
                                   class="font-semibold text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 hover:underline">
                                    {{ $contract->title ?: $contract->contract_number ?: 'Vertrag' }}
                                </a>
                                @if($contract->contract_number)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        {{ $contract->contract_number }}
                                    </span>
                                @endif
                                @if($percentage)
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                        {{ number_format($percentage, 2, ',', '.') }}% Anteil
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Lieferant: {{ $supplierName }}
                            </p>
                        </div>
                    </div>

                    <div class="flex-shrink-0">
                        @if(!$hasBillings)
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                Beleg fehlt
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-300">
                                {{ $billings->count() }} {{ $billings->count() === 1 ? 'Beleg' : 'Belege' }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Belege --}}
                @if($hasBillings)
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($billings as $billing)
                            <div class="px-4 py-3 hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <a href="{{ route('filament.admin.resources.supplier-contract-billings.view', $billing) }}"
                                                   class="font-medium text-gray-900 hover:text-primary-600 dark:text-gray-100 dark:hover:text-primary-400 hover:underline">
                                                    {{ $billing->billing_number }}
                                                </a>
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $billingTypeColors[$billing->billing_type] ?? $billingTypeColors['invoice'] }}">
                                                    {{ $billingTypeLabels[$billing->billing_type] ?? $billing->billing_type }}
                                                </span>
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$billing->status] ?? $statusColors['draft'] }}">
                                                    {{ $statusLabels[$billing->status] ?? $billing->status }}
                                                </span>
                                            </div>
                                            @if($billing->title || $billing->description)
                                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                    {{ $billing->title ?: $billing->description }}
                                                </p>
                                            @endif
                                            @if($billing->supplier_invoice_number)
                                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                                    Lieferanten-Rechnungsnr.: {{ $billing->supplier_invoice_number }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 flex-shrink-0">
                                        <div class="text-right">
                                            <p class="font-semibold {{ $billing->billing_type === 'credit_note' ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-gray-100' }}">
                                                {{ number_format($billing->total_amount, 2, ',', '.') }} &euro;
                                            </p>
                                            @if($billing->net_amount)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    Netto: {{ number_format($billing->net_amount, 2, ',', '.') }} &euro;
                                                    @if($billing->vat_rate)
                                                        ({{ number_format($billing->vat_rate, 0) }}% MwSt.)
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                        <a href="{{ route('filament.admin.resources.supplier-contract-billings.view', $billing) }}"
                                           class="inline-flex items-center rounded-md bg-primary-50 px-2.5 py-1.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20 hover:bg-primary-100 dark:bg-primary-900/50 dark:text-primary-400 dark:ring-primary-500/30 dark:hover:bg-primary-900">
                                            <svg class="mr-1 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                            </svg>
                                            Öffnen
                                        </a>
                                    </div>
                                </div>

                                {{-- Artikel des Belegs --}}
                                @if($billing->articles && $billing->articles->count() > 0)
                                    <div class="mt-2 ml-0 rounded border border-gray-100 dark:border-gray-700 overflow-hidden">
                                        <table class="w-full text-xs">
                                            <thead class="bg-gray-50 dark:bg-gray-800">
                                                <tr>
                                                    <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Artikel</th>
                                                    <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Menge</th>
                                                    <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Einzelpreis</th>
                                                    <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Gesamt</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                                @foreach($billing->articles as $billingArticle)
                                                    <tr>
                                                        <td class="px-3 py-1.5 text-gray-700 dark:text-gray-300">
                                                            {{ $billingArticle->article?->name ?? $billingArticle->description ?? '-' }}
                                                        </td>
                                                        <td class="px-3 py-1.5 text-right text-gray-600 dark:text-gray-400">
                                                            {{ number_format($billingArticle->quantity, 2, ',', '.') }}
                                                        </td>
                                                        <td class="px-3 py-1.5 text-right text-gray-600 dark:text-gray-400">
                                                            {{ number_format($billingArticle->unit_price, 2, ',', '.') }} &euro;
                                                        </td>
                                                        <td class="px-3 py-1.5 text-right font-medium text-gray-700 dark:text-gray-300">
                                                            {{ number_format($billingArticle->total_price, 2, ',', '.') }} &euro;
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- PDF-Aufschlüsselung pro Kunde --}}
                                    @if(isset($participations) && $participations->count() > 0)
                                        @php
                                            $plantPercentage = $percentage ?? 100;
                                            $plantShare = $plantPercentage / 100;
                                            $vatRate = $billing->vat_rate ?? 19;
                                            $isCredit = $billing->billing_type === 'credit_note' || $billing->total_amount < 0;
                                        @endphp
                                        <div x-data="{ open: false }" class="mt-2 rounded border border-indigo-100 dark:border-indigo-800 overflow-hidden">
                                            <button @click="open = !open" type="button" class="w-full px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 flex items-center gap-2 cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                                                <svg :class="{ 'rotate-90': open }" class="h-3.5 w-3.5 text-indigo-500 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                                </svg>
                                                <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                                </svg>
                                                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">PDF-Aufschlüsselung (Anlagenanteil {{ number_format($plantPercentage, 2, ',', '.') }}%)</span>
                                            </button>
                                            <table x-show="open" x-collapse class="w-full text-xs">
                                                <thead class="bg-gray-50 dark:bg-gray-800">
                                                    <tr>
                                                        <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Kunde</th>
                                                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Anteil</th>
                                                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Netto</th>
                                                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">MwSt. ({{ number_format($vatRate, 0) }}%)</th>
                                                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Brutto</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                                    @foreach($participations as $part)
                                                        @php
                                                            $custName = $part->customer?->customer_type === 'business'
                                                                ? ($part->customer?->company_name ?: $part->customer?->name)
                                                                : $part->customer?->name ?? 'Unbekannt';
                                                            $customerShare = $part->percentage / 100;
                                                            $finalShare = $plantShare * $customerShare;

                                                            $billingNet = abs($billing->net_amount ?? ($billing->total_amount / (1 + $vatRate / 100)));
                                                            $billingGross = abs($billing->total_amount);

                                                            $customerNet = $billingNet * $finalShare;
                                                            $customerGross = $billingGross * $finalShare;
                                                            $customerVat = $customerGross - $customerNet;
                                                        @endphp
                                                        <tr>
                                                            <td class="px-3 py-1.5 text-gray-700 dark:text-gray-300">{{ $custName }}</td>
                                                            <td class="px-3 py-1.5 text-right text-gray-600 dark:text-gray-400">{{ number_format($part->percentage, 2, ',', '.') }}%</td>
                                                            <td class="px-3 py-1.5 text-right {{ $isCredit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-medium">
                                                                {{ number_format($customerNet, 2, ',', '.') }} &euro;
                                                            </td>
                                                            <td class="px-3 py-1.5 text-right text-gray-500 dark:text-gray-400">
                                                                {{ number_format($customerVat, 2, ',', '.') }} &euro;
                                                            </td>
                                                            <td class="px-3 py-1.5 text-right {{ $isCredit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-semibold">
                                                                {{ number_format($customerGross, 2, ',', '.') }} &euro;
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-gray-50 dark:bg-gray-800">
                                                    @php
                                                        $totalPartPercentage = $participations->sum('percentage');
                                                        $totalFinalShare = $plantShare * ($totalPartPercentage / 100);
                                                        $totalNet = abs($billing->net_amount ?? ($billing->total_amount / (1 + $vatRate / 100))) * $totalFinalShare;
                                                        $totalGross = abs($billing->total_amount) * $totalFinalShare;
                                                        $totalVat = $totalGross - $totalNet;
                                                    @endphp
                                                    <tr>
                                                        <td class="px-3 py-1.5 font-semibold text-gray-700 dark:text-gray-300">Gesamt</td>
                                                        <td class="px-3 py-1.5 text-right font-medium text-gray-600 dark:text-gray-400">{{ number_format($totalPartPercentage, 2, ',', '.') }}%</td>
                                                        <td class="px-3 py-1.5 text-right font-semibold {{ $isCredit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                            {{ number_format($totalNet, 2, ',', '.') }} &euro;
                                                        </td>
                                                        <td class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">
                                                            {{ number_format($totalVat, 2, ',', '.') }} &euro;
                                                        </td>
                                                        <td class="px-3 py-1.5 text-right font-semibold {{ $isCredit ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                            {{ number_format($totalGross, 2, ',', '.') }} &euro;
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-4 py-4 text-center">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            Für diesen Vertrag wurde noch kein Beleg für {{ $monthNames[$billingMonth] }} {{ $billingYear }} erfasst.
                        </p>
                        <a href="{{ route('filament.admin.resources.supplier-contract-billings.create') }}?solar_plant_id={{ $this->solarPlant->id }}&supplier_contract_id={{ $contract->id }}&billing_year={{ $billingYear }}&billing_month={{ $billingMonth }}"
                           target="_blank"
                           class="mt-2 inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600">
                            <svg class="mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Beleg erstellen
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="rounded-lg border border-gray-200 p-8 text-center dark:border-gray-700">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9.75m3 0H9.75m0 0v3m3-3V18m-3 3h3m-3 0h-.375a2.625 2.625 0 01-2.625-2.625V15M12 9.75h.008v.008H12V9.75z" />
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Keine Lieferantenverträge für diese Solaranlage hinterlegt.
                </p>
            </div>
        @endforelse
    </div>

    {{-- Kundenabrechnungen / Artikelaufschlüsselung --}}
    @if(isset($customerBillings) && $customerBillings->count() > 0)
        <div class="mt-6">
            <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                Kundenabrechnungen – Artikelaufschlüsselung
                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                    {{ $customerBillings->count() }} {{ $customerBillings->count() === 1 ? 'Abrechnung' : 'Abrechnungen' }}
                </span>
            </h4>
            <div class="mb-[5px]"></div>

            @php
                $billingStatusLabels = [
                    'draft' => 'Entwurf', 'finalized' => 'Finalisiert', 'sent' => 'Versendet',
                    'paid' => 'Bezahlt', 'cancelled' => 'Storniert',
                ];
                $billingStatusColors = [
                    'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    'finalized' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    'sent' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                ];
            @endphp

            <div class="space-y-4 mt-2">
                @foreach($customerBillings as $cBilling)
                    @php
                        $customerName = $cBilling->customer?->customer_type === 'business'
                            ? ($cBilling->customer?->company_name ?: $cBilling->customer?->name)
                            : $cBilling->customer?->name ?? 'Unbekannt';
                    @endphp
                    <div x-data="{ open: false }" class="rounded-lg border border-blue-200 dark:border-blue-800 overflow-hidden">
                        {{-- Kundenkopf --}}
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between gap-4 px-4 py-2.5 bg-blue-50 dark:bg-blue-900/20 cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                            <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                <svg :class="{ 'rotate-90': open }" class="h-4 w-4 text-blue-500 transition-transform duration-200 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $cBilling->invoice_number ?? 'Ohne Nr.' }}</span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $customerName }}</span>
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                    {{ number_format($cBilling->participation_percentage, 2, ',', '.') }}%
                                </span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $billingStatusColors[$cBilling->status] ?? $billingStatusColors['draft'] }}">
                                    {{ $billingStatusLabels[$cBilling->status] ?? $cBilling->status }}
                                </span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-semibold text-sm {{ $cBilling->net_amount >= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ number_format($cBilling->net_amount, 2, ',', '.') }} &euro;
                                </span>
                            </div>
                        </button>

                        <div x-show="open" x-collapse>
                        {{-- Gutschriften --}}
                        @if(!empty($cBilling->credit_breakdown))
                            <div class="px-4 py-2 border-t border-blue-100 dark:border-blue-800/50">
                                <p class="text-xs font-semibold text-green-700 dark:text-green-400 mb-2">Gutschriften / Einnahmen</p>
                                @foreach($cBilling->credit_breakdown as $credit)
                                    <div class="mb-[20px] last:mb-0">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $credit['contract_title'] ?? 'Einnahmen' }}</span>
                                            <span class="text-green-600 dark:text-green-400 font-medium">{{ number_format($credit['customer_share'] ?? 0, 2, ',', '.') }} &euro;</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $credit['supplier_name'] ?? '' }}</p>
                                        @if(isset($credit['articles']) && !empty($credit['articles']))
                                            <div x-data="{ open: false }" class="mt-2 mb-4 ml-2">
                                                <button @click="open = !open" type="button" class="flex items-center gap-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mb-1 cursor-pointer">
                                                    <svg :class="{ 'rotate-90': open }" class="h-3 w-3 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                    Artikel-Aufschlüsselung ({{ count($credit['articles']) }})
                                                </button>
                                                <div x-show="open" x-collapse class="space-y-2">
                                                    @foreach($credit['articles'] as $article)
                                                        @php
                                                            $articleModel = isset($article['article_id']) ? \App\Models\Article::find($article['article_id']) : null;
                                                            $decimalPlaces = $articleModel ? $articleModel->getDecimalPlaces() : 2;
                                                            $totalDecimalPlaces = $articleModel ? $articleModel->getTotalDecimalPlaces() : 2;
                                                            $netPrice = $article['total_price_net'] ?? (($article['quantity'] ?? 0) * ($article['unit_price'] ?? 0));
                                                            $taxRate = $article['tax_rate'] ?? 0.19;
                                                            $taxAmount = $article['tax_amount'] ?? ($netPrice * $taxRate);
                                                            $grossPrice = $article['total_price_gross'] ?? ($netPrice + $taxAmount);
                                                            $taxRatePercent = $taxRate <= 1 ? $taxRate * 100 : $taxRate;
                                                        @endphp
                                                        <div class="rounded border border-green-100 dark:border-green-800 bg-white dark:bg-gray-900 p-2">
                                                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $article['article_name'] ?? 'Unbekannt' }}</p>
                                                            @if(isset($article['description']) && $article['description'] !== ($article['article_name'] ?? '') && !empty($article['description']))
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $article['description'] }}</p>
                                                            @endif
                                                            <table class="w-full text-xs mt-1">
                                                                <thead>
                                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                                        <th class="px-1 py-0.5 text-left font-medium text-gray-500 dark:text-gray-400">Menge</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Einzelpreis</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Netto</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">USt. ({{ number_format($taxRatePercent, 1, ',', '.') }}%)</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Brutto</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="px-1 py-0.5 text-gray-700 dark:text-gray-300">{{ number_format($article['quantity'] ?? 0, 3, ',', '.') }} {{ $article['unit'] ?? 'Stk.' }}</td>
                                                                        <td class="px-1 py-0.5 text-right text-gray-600 dark:text-gray-400">{{ number_format($article['unit_price'] ?? 0, $decimalPlaces, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-green-600 dark:text-green-400">{{ number_format($netPrice, $totalDecimalPlaces, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-gray-500 dark:text-gray-400">{{ number_format($taxAmount, 2, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-green-600 dark:text-green-400 font-semibold">{{ number_format($grossPrice, 2, ',', '.') }} &euro;</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                            @if(isset($article['detailed_description']) && !empty($article['detailed_description']))
                                                                <div class="mt-1.5 px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                                                    <span class="font-semibold">Hinweis:</span><br>
                                                                    {!! nl2br(e($article['detailed_description'])) !!}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                <div class="mt-3 pt-2 flex items-center justify-between text-xs">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">Gesamt Gutschriften / Einnahmen</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($cBilling->total_credits, 2, ',', '.') }} &euro;</span>
                                </div>
                            </div>
                        @endif

                        {{-- Kosten --}}
                        @if(!empty($cBilling->cost_breakdown))
                            <div class="px-4 py-2 border-t border-blue-100 dark:border-blue-800/50">
                                <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-2">Kosten</p>
                                @foreach($cBilling->cost_breakdown as $cost)
                                    <div class="mb-[20px] last:mb-0">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $cost['contract_title'] ?? 'Kosten' }}</span>
                                            <span class="text-red-600 dark:text-red-400 font-medium">-{{ number_format($cost['customer_share'] ?? 0, 2, ',', '.') }} &euro;</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $cost['supplier_name'] ?? '' }}</p>
                                        @if(isset($cost['articles']) && !empty($cost['articles']))
                                            <div x-data="{ open: false }" class="mt-2 mb-4 ml-2">
                                                <button @click="open = !open" type="button" class="flex items-center gap-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mb-1 cursor-pointer">
                                                    <svg :class="{ 'rotate-90': open }" class="h-3 w-3 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                    Artikel-Aufschlüsselung ({{ count($cost['articles']) }})
                                                </button>
                                                <div x-show="open" x-collapse class="space-y-2">
                                                    @foreach($cost['articles'] as $article)
                                                        @php
                                                            $articleModel = isset($article['article_id']) ? \App\Models\Article::find($article['article_id']) : null;
                                                            $decimalPlaces = $articleModel ? $articleModel->getDecimalPlaces() : 2;
                                                            $totalDecimalPlaces = $articleModel ? $articleModel->getTotalDecimalPlaces() : 2;
                                                            $netPrice = $article['total_price_net'] ?? (($article['quantity'] ?? 0) * ($article['unit_price'] ?? 0));
                                                            $taxRate = $article['tax_rate'] ?? 0.19;
                                                            $taxAmount = $article['tax_amount'] ?? ($netPrice * $taxRate);
                                                            $grossPrice = $article['total_price_gross'] ?? ($netPrice + $taxAmount);
                                                            $taxRatePercent = $taxRate <= 1 ? $taxRate * 100 : $taxRate;
                                                        @endphp
                                                        <div class="rounded border border-red-100 dark:border-red-800 bg-white dark:bg-gray-900 p-2">
                                                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $article['article_name'] ?? 'Unbekannt' }}</p>
                                                            @if(isset($article['description']) && $article['description'] !== ($article['article_name'] ?? '') && !empty($article['description']))
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $article['description'] }}</p>
                                                            @endif
                                                            <table class="w-full text-xs mt-1">
                                                                <thead>
                                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                                        <th class="px-1 py-0.5 text-left font-medium text-gray-500 dark:text-gray-400">Menge</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Einzelpreis</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Netto</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">USt. ({{ number_format($taxRatePercent, 1, ',', '.') }}%)</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Brutto</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="px-1 py-0.5 text-gray-700 dark:text-gray-300">{{ number_format($article['quantity'] ?? 0, 3, ',', '.') }} {{ $article['unit'] ?? 'Stk.' }}</td>
                                                                        <td class="px-1 py-0.5 text-right text-gray-600 dark:text-gray-400">{{ number_format($article['unit_price'] ?? 0, $decimalPlaces, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-red-600 dark:text-red-400">{{ number_format($netPrice, $totalDecimalPlaces, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-gray-500 dark:text-gray-400">{{ number_format($taxAmount, 2, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-red-600 dark:text-red-400 font-semibold">{{ number_format($grossPrice, 2, ',', '.') }} &euro;</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                            @if(isset($article['detailed_description']) && !empty($article['detailed_description']))
                                                                <div class="mt-1.5 px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                                                    <span class="font-semibold">Hinweis:</span><br>
                                                                    {!! nl2br(e($article['detailed_description'])) !!}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                <div class="mt-3 pt-2 flex items-center justify-between text-xs">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">Gesamt Kosten</span>
                                    <span class="font-semibold text-red-600 dark:text-red-400">-{{ number_format($cBilling->total_costs, 2, ',', '.') }} &euro;</span>
                                </div>
                            </div>
                        @endif
                        </div>{{-- /x-collapse --}}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Voraussichtliche Kundenabrechnungen (Preview) --}}
    @if(isset($previewBillings) && $previewBillings->count() > 0)
        <div class="mt-6">
            <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                <svg class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Voraussichtliche Kundenabrechnungen – Artikelaufschlüsselung
                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-300">
                    {{ $previewBillings->count() }} {{ $previewBillings->count() === 1 ? 'Kunde' : 'Kunden' }}
                </span>
            </h4>
            <div class="mb-[5px]"></div>

            <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-900/10 p-3 mt-3 mb-3">
                <p class="text-xs text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                    Dies ist eine Vorschau. Die tatsächlichen Betr&auml;ge k&ouml;nnen bei der Abrechnung abweichen.
                </p>
            </div>

            @php
                $billingStatusLabelsPreview = [
                    'draft' => 'Entwurf', 'finalized' => 'Finalisiert', 'sent' => 'Versendet',
                    'paid' => 'Bezahlt', 'cancelled' => 'Storniert',
                ];
                $billingStatusColorsPreview = [
                    'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    'finalized' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    'sent' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                ];
            @endphp

            <div class="space-y-4 mt-2">
                @foreach($previewBillings as $preview)
                    @php
                        $previewCustomer = $preview['customer'];
                        $previewCustomerName = $previewCustomer->customer_type === 'business'
                            ? ($previewCustomer->company_name ?: $previewCustomer->name)
                            : $previewCustomer->name ?? 'Unbekannt';
                    @endphp
                    <div x-data="{ open: false }" class="rounded-lg border border-amber-200 dark:border-amber-800 overflow-hidden">
                        {{-- Kundenkopf --}}
                        <button @click="open = !open" type="button" class="w-full flex items-center justify-between gap-4 px-4 py-2.5 bg-amber-50 dark:bg-amber-900/20 cursor-pointer hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                            <div class="flex items-center gap-2 min-w-0 flex-wrap">
                                <svg :class="{ 'rotate-90': open }" class="h-4 w-4 text-amber-500 transition-transform duration-200 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $previewCustomerName }}</span>
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                    {{ number_format($preview['participation_percentage'], 2, ',', '.') }}%
                                </span>
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                                    Vorschau
                                </span>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <span class="font-semibold text-sm {{ $preview['net_amount'] >= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                    {{ number_format($preview['net_amount'], 2, ',', '.') }} &euro;
                                </span>
                            </div>
                        </button>

                        <div x-show="open" x-collapse>
                        {{-- Gutschriften --}}
                        @if(!empty($preview['credit_breakdown']))
                            <div class="px-4 py-2 border-t border-amber-100 dark:border-amber-800/50">
                                <p class="text-xs font-semibold text-green-700 dark:text-green-400 mb-2">Gutschriften / Einnahmen</p>
                                @foreach($preview['credit_breakdown'] as $credit)
                                    <div class="mb-[20px] last:mb-0">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $credit['contract_title'] ?? 'Einnahmen' }}</span>
                                            <span class="text-green-600 dark:text-green-400 font-medium">{{ number_format($credit['customer_share'] ?? 0, 2, ',', '.') }} &euro;</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $credit['supplier_name'] ?? '' }}</p>
                                        @if(isset($credit['articles']) && !empty($credit['articles']))
                                            <div x-data="{ open: false }" class="mt-2 mb-4 ml-2">
                                                <button @click="open = !open" type="button" class="flex items-center gap-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mb-1 cursor-pointer">
                                                    <svg :class="{ 'rotate-90': open }" class="h-3 w-3 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                    Artikel-Aufschlüsselung ({{ count($credit['articles']) }})
                                                </button>
                                                <div x-show="open" x-collapse class="space-y-2">
                                                    @foreach($credit['articles'] as $article)
                                                        @php
                                                            $articleModel = isset($article['article_id']) ? \App\Models\Article::find($article['article_id']) : null;
                                                            $decimalPlaces = $articleModel ? $articleModel->getDecimalPlaces() : 2;
                                                            $totalDecimalPlaces = $articleModel ? $articleModel->getTotalDecimalPlaces() : 2;
                                                            $netPrice = $article['total_price_net'] ?? (($article['quantity'] ?? 0) * ($article['unit_price'] ?? 0));
                                                            $taxRate = $article['tax_rate'] ?? 0.19;
                                                            $taxAmount = $article['tax_amount'] ?? ($netPrice * $taxRate);
                                                            $grossPrice = $article['total_price_gross'] ?? ($netPrice + $taxAmount);
                                                            $taxRatePercent = $taxRate <= 1 ? $taxRate * 100 : $taxRate;
                                                        @endphp
                                                        <div class="rounded border border-green-100 dark:border-green-800 bg-white dark:bg-gray-900 p-2">
                                                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $article['article_name'] ?? 'Unbekannt' }}</p>
                                                            @if(isset($article['description']) && $article['description'] !== ($article['article_name'] ?? '') && !empty($article['description']))
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $article['description'] }}</p>
                                                            @endif
                                                            <table class="w-full text-xs mt-1">
                                                                <thead>
                                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                                        <th class="px-1 py-0.5 text-left font-medium text-gray-500 dark:text-gray-400">Menge</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Einzelpreis</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Netto</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">USt. ({{ number_format($taxRatePercent, 1, ',', '.') }}%)</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Brutto</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="px-1 py-0.5 text-gray-700 dark:text-gray-300">{{ number_format($article['quantity'] ?? 0, 3, ',', '.') }} {{ $article['unit'] ?? 'Stk.' }}</td>
                                                                        <td class="px-1 py-0.5 text-right text-gray-600 dark:text-gray-400">{{ number_format($article['unit_price'] ?? 0, $decimalPlaces, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-green-600 dark:text-green-400">{{ number_format($netPrice, $totalDecimalPlaces, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-gray-500 dark:text-gray-400">{{ number_format($taxAmount, 2, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-green-600 dark:text-green-400 font-semibold">{{ number_format($grossPrice, 2, ',', '.') }} &euro;</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                            @if(isset($article['detailed_description']) && !empty($article['detailed_description']))
                                                                <div class="mt-1.5 px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                                                    <span class="font-semibold">Hinweis:</span><br>
                                                                    {!! nl2br(e($article['detailed_description'])) !!}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                <div class="mt-3 pt-2 flex items-center justify-between text-xs">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">Gesamt Gutschriften / Einnahmen</span>
                                    <span class="font-semibold text-green-600 dark:text-green-400">{{ number_format($preview['total_credits'], 2, ',', '.') }} &euro;</span>
                                </div>
                            </div>
                        @endif

                        {{-- Kosten --}}
                        @if(!empty($preview['cost_breakdown']))
                            <div class="px-4 py-2 border-t border-amber-100 dark:border-amber-800/50">
                                <p class="text-xs font-semibold text-red-700 dark:text-red-400 mb-2">Kosten</p>
                                @foreach($preview['cost_breakdown'] as $cost)
                                    <div class="mb-[20px] last:mb-0">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $cost['contract_title'] ?? 'Kosten' }}</span>
                                            <span class="text-red-600 dark:text-red-400 font-medium">-{{ number_format($cost['customer_share'] ?? 0, 2, ',', '.') }} &euro;</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $cost['supplier_name'] ?? '' }}</p>
                                        @if(isset($cost['articles']) && !empty($cost['articles']))
                                            <div x-data="{ open: false }" class="mt-2 mb-4 ml-2">
                                                <button @click="open = !open" type="button" class="flex items-center gap-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mb-1 cursor-pointer">
                                                    <svg :class="{ 'rotate-90': open }" class="h-3 w-3 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                                    Artikel-Aufschlüsselung ({{ count($cost['articles']) }})
                                                </button>
                                                <div x-show="open" x-collapse class="space-y-2">
                                                    @foreach($cost['articles'] as $article)
                                                        @php
                                                            $articleModel = isset($article['article_id']) ? \App\Models\Article::find($article['article_id']) : null;
                                                            $decimalPlaces = $articleModel ? $articleModel->getDecimalPlaces() : 2;
                                                            $totalDecimalPlaces = $articleModel ? $articleModel->getTotalDecimalPlaces() : 2;
                                                            $netPrice = $article['total_price_net'] ?? (($article['quantity'] ?? 0) * ($article['unit_price'] ?? 0));
                                                            $taxRate = $article['tax_rate'] ?? 0.19;
                                                            $taxAmount = $article['tax_amount'] ?? ($netPrice * $taxRate);
                                                            $grossPrice = $article['total_price_gross'] ?? ($netPrice + $taxAmount);
                                                            $taxRatePercent = $taxRate <= 1 ? $taxRate * 100 : $taxRate;
                                                        @endphp
                                                        <div class="rounded border border-red-100 dark:border-red-800 bg-white dark:bg-gray-900 p-2">
                                                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $article['article_name'] ?? 'Unbekannt' }}</p>
                                                            @if(isset($article['description']) && $article['description'] !== ($article['article_name'] ?? '') && !empty($article['description']))
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $article['description'] }}</p>
                                                            @endif
                                                            <table class="w-full text-xs mt-1">
                                                                <thead>
                                                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                                                        <th class="px-1 py-0.5 text-left font-medium text-gray-500 dark:text-gray-400">Menge</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Einzelpreis</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Netto</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">USt. ({{ number_format($taxRatePercent, 1, ',', '.') }}%)</th>
                                                                        <th class="px-1 py-0.5 text-right font-medium text-gray-500 dark:text-gray-400">Brutto</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="px-1 py-0.5 text-gray-700 dark:text-gray-300">{{ number_format($article['quantity'] ?? 0, 3, ',', '.') }} {{ $article['unit'] ?? 'Stk.' }}</td>
                                                                        <td class="px-1 py-0.5 text-right text-gray-600 dark:text-gray-400">{{ number_format($article['unit_price'] ?? 0, $decimalPlaces, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-red-600 dark:text-red-400">{{ number_format($netPrice, $totalDecimalPlaces, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-gray-500 dark:text-gray-400">{{ number_format($taxAmount, 2, ',', '.') }} &euro;</td>
                                                                        <td class="px-1 py-0.5 text-right text-red-600 dark:text-red-400 font-semibold">{{ number_format($grossPrice, 2, ',', '.') }} &euro;</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                            @if(isset($article['detailed_description']) && !empty($article['detailed_description']))
                                                                <div class="mt-1.5 px-2 py-1.5 bg-gray-50 dark:bg-gray-800 rounded text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                                                    <span class="font-semibold">Hinweis:</span><br>
                                                                    {!! nl2br(e($article['detailed_description'])) !!}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                <div class="mt-3 pt-2 flex items-center justify-between text-xs">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">Gesamt Kosten</span>
                                    <span class="font-semibold text-red-600 dark:text-red-400">-{{ number_format($preview['total_costs'], 2, ',', '.') }} &euro;</span>
                                </div>
                            </div>
                        @endif

                        @if($preview['previous_month_outstanding'] > 0)
                            <div class="px-4 py-2 border-t border-amber-100 dark:border-amber-800/50">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">Offener Betrag Vormonat</span>
                                    <span class="font-medium text-red-600 dark:text-red-400">{{ number_format($preview['previous_month_outstanding'], 2, ',', '.') }} &euro;</span>
                                </div>
                            </div>
                        @endif
                        </div>{{-- /x-collapse --}}
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Pflicht-Kundenartikel --}}
    @if(isset($customerArticlesData) && $customerArticlesData->count() > 0)
        <div class="mt-6">
            <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                <svg class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                Pflicht-Kundenartikel in dieser Abrechnung
                <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                    {{ $customerArticlesData->count() }} {{ $customerArticlesData->count() === 1 ? 'Kunde' : 'Kunden' }}
                </span>
            </h4>

            <div class="space-y-3">
                @foreach($customerArticlesData as $customerData)
                    @php
                        $customer = $customerData['customer'];
                        $customerName = $customer->customer_type === 'business'
                            ? ($customer->company_name ?: $customer->name)
                            : $customer->name;
                    @endphp
                    <div class="rounded-lg border border-purple-200 dark:border-purple-800 overflow-hidden">
                        {{-- Kundenkopf --}}
                        <div class="flex items-center justify-between gap-4 px-4 py-2.5 bg-purple-50 dark:bg-purple-900/20">
                            <div class="flex items-center gap-2 min-w-0">
                                <svg class="h-4 w-4 text-purple-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                </svg>
                                <a href="{{ route('filament.admin.resources.customers.view', $customer) }}"
                                   class="font-semibold text-purple-700 hover:text-purple-900 dark:text-purple-300 dark:hover:text-purple-200 hover:underline">
                                    {{ $customerName }}
                                </a>
                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                                    {{ number_format($customerData['participation_percentage'], 2, ',', '.') }}%
                                </span>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                                {{ $customerData['articles']->count() }} {{ $customerData['articles']->count() === 1 ? 'Artikel' : 'Artikel' }}
                            </span>
                        </div>

                        {{-- Artikeltabelle --}}
                        <div class="overflow-hidden">
                            <table class="w-full text-xs">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-3 py-1.5 text-left font-medium text-gray-500 dark:text-gray-400">Artikel</th>
                                        <th class="px-3 py-1.5 text-center font-medium text-gray-500 dark:text-gray-400">Typ</th>
                                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Menge</th>
                                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Einzelpreis</th>
                                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Netto</th>
                                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">MwSt.</th>
                                        <th class="px-3 py-1.5 text-right font-medium text-gray-500 dark:text-gray-400">Brutto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                    @foreach($customerData['articles'] as $articleItem)
                                        @php
                                            $isCredit = $articleItem['billing_type'] === 'credit';
                                        @endphp
                                        <tr>
                                            <td class="px-3 py-1.5 text-gray-700 dark:text-gray-300">
                                                {{ $articleItem['article']->name }}
                                                @if($articleItem['article']->description)
                                                    <span class="block text-xs text-gray-400">{{ Str::limit($articleItem['article']->description, 60) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-1.5 text-center">
                                                @if($isCredit)
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">
                                                        Gutschrift
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">
                                                        Rechnung
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-1.5 text-right text-gray-600 dark:text-gray-400">
                                                {{ number_format($articleItem['quantity'], 2, ',', '.') }}
                                            </td>
                                            <td class="px-3 py-1.5 text-right text-gray-600 dark:text-gray-400">
                                                {{ number_format($articleItem['unit_price'], 2, ',', '.') }} &euro;
                                            </td>
                                            <td class="px-3 py-1.5 text-right {{ $isCredit ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ number_format($articleItem['net_total'], 2, ',', '.') }} &euro;
                                            </td>
                                            <td class="px-3 py-1.5 text-right text-gray-500 dark:text-gray-400">
                                                {{ number_format($articleItem['tax_rate'] * 100, 0) }}%
                                            </td>
                                            <td class="px-3 py-1.5 text-right font-medium {{ $isCredit ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ number_format($articleItem['gross_total'], 2, ',', '.') }} &euro;
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        @if(isset($customerArticlesData))
            <div class="mt-6 flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                Keine Pflicht-Kundenartikel für diesen Monat hinterlegt.
            </div>
        @endif
    @endif
</div>
