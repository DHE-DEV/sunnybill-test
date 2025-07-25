<div x-data="{ expanded: false }" class="w-full">
    {{-- Toggle Button --}}
    <button 
        @click.stop="expanded = !expanded"
        class="flex items-center space-x-2 text-sm text-gray-600 hover:text-gray-900 transition-colors duration-200"
        type="button"
    >
        <span>
            @if($getRecord()->cost_breakdown && count($getRecord()->cost_breakdown) > 0)
                {{ count($getRecord()->cost_breakdown) }} Kostenposition{{ count($getRecord()->cost_breakdown) > 1 ? 'en' : '' }}
            @endif
            @if($getRecord()->credit_breakdown && count($getRecord()->credit_breakdown) > 0)
                @if($getRecord()->cost_breakdown && count($getRecord()->cost_breakdown) > 0), @endif
                {{ count($getRecord()->credit_breakdown) }} Gutschrift{{ count($getRecord()->credit_breakdown) > 1 ? 'en' : '' }}
            @endif
            @if((!$getRecord()->cost_breakdown || count($getRecord()->cost_breakdown) === 0) && (!$getRecord()->credit_breakdown || count($getRecord()->credit_breakdown) === 0))
                Keine Details
            @endif
        </span>
        <svg 
            x-bind:class="expanded ? 'rotate-180' : 'rotate-0'" 
            class="w-4 h-4 transition-transform duration-200" 
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Expandable Content --}}
    <div 
        x-show="expanded" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        @click.stop
        class="mt-4 space-y-4"
        style="display: none;"
    >
        {{-- Kosten-Breakdown --}}
        @if($getRecord()->cost_breakdown && count($getRecord()->cost_breakdown) > 0)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-red-800 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    Kostenpositionen
                </h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-red-200">
                        <thead class="bg-red-100">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-red-800 uppercase tracking-wider">Bezeichnung</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-red-800 uppercase tracking-wider">Anlagen-Anteil</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-red-800 uppercase tracking-wider">Kunden-Anteil</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-red-800 uppercase tracking-wider">Betrag</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-red-200">
                            @foreach($getRecord()->cost_breakdown as $item)
                                <tr class="hover:bg-red-25">
                                    <td class="px-3 py-2">
                                        <div class="text-sm font-medium text-gray-900">{{ $item['contract_title'] }}</div>
                                        <div class="text-xs text-gray-500">({{ $item['supplier_name'] }})</div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-900">
                                        @php
                                            // Hole den korrekten Beteiligungsprozentsatz aus dem Vertrag
                                            $contract = \App\Models\SupplierContract::find($item['supplier_contract_id'] ?? null);
                                            $participationPercentage = 100.0; // Fallback
                                            
                                            if ($contract) {
                                                $solarPlantId = $getRecord()->solar_plant_id;
                                                $pivotData = $contract->solarPlants()
                                                    ->wherePivot('solar_plant_id', $solarPlantId)
                                                    ->first();
                                                
                                                if ($pivotData && $pivotData->pivot->participation_percentage) {
                                                    $participationPercentage = $pivotData->pivot->participation_percentage;
                                                }
                                            }
                                        @endphp
                                        <div class="flex flex-col items-end">
                                            <span class="text-green-700 font-medium flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                {{ number_format($participationPercentage, 2, ',', '.') }}%
                                            </span>
                                            <span class="text-xs text-gray-500 flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                                Beteiligung (Vertrag)
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-900">
                                        {{ number_format($getRecord()->participation_percentage, 2, ',', '.') }}%
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium text-gray-900">
                                        {{ number_format($item['customer_share'], 2, ',', '.') }} €
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-red-100">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right text-sm font-semibold text-red-800">
                                    Gesamtkosten:
                                </td>
                                <td class="px-3 py-2 text-right text-sm font-bold text-red-800">
                                    {{ number_format(array_sum(array_column($getRecord()->cost_breakdown, 'customer_share')), 2, ',', '.') }} €
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        {{-- Gutschriften-Breakdown --}}
        @if($getRecord()->credit_breakdown && count($getRecord()->credit_breakdown) > 0)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-green-800 mb-3 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    Gutschriftenpositionen
                </h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-green-200">
                        <thead class="bg-green-100">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-green-800 uppercase tracking-wider">Bezeichnung</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-green-800 uppercase tracking-wider">Anlagen-Anteil</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-green-800 uppercase tracking-wider">Kunden-Anteil</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-green-800 uppercase tracking-wider">Betrag</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-green-200">
                            @foreach($getRecord()->credit_breakdown as $item)
                                <tr class="hover:bg-green-25">
                                    <td class="px-3 py-2">
                                        <div class="text-sm font-medium text-gray-900">{{ $item['contract_title'] }}</div>
                                        <div class="text-xs text-gray-500">({{ $item['supplier_name'] }})</div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-900">
                                        @php
                                            // Hole den korrekten Beteiligungsprozentsatz aus dem Vertrag
                                            $contract = \App\Models\SupplierContract::find($item['supplier_contract_id'] ?? null);
                                            $participationPercentage = 100.0; // Fallback
                                            
                                            if ($contract) {
                                                $solarPlantId = $getRecord()->solar_plant_id;
                                                $pivotData = $contract->solarPlants()
                                                    ->wherePivot('solar_plant_id', $solarPlantId)
                                                    ->first();
                                                
                                                if ($pivotData && $pivotData->pivot->participation_percentage) {
                                                    $participationPercentage = $pivotData->pivot->participation_percentage;
                                                }
                                            }
                                        @endphp
                                        <div class="flex flex-col items-end">
                                            <span class="text-green-700 font-medium flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                {{ number_format($participationPercentage, 2, ',', '.') }}%
                                            </span>
                                            <span class="text-xs text-gray-500 flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                                Beteiligung (Vertrag)
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm text-gray-900">
                                        {{ number_format($getRecord()->participation_percentage, 2, ',', '.') }}%
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium text-gray-900">
                                        {{ number_format($item['customer_share'], 2, ',', '.') }} €
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-green-100">
                            <tr>
                                <td colspan="3" class="px-3 py-2 text-right text-sm font-semibold text-green-800">
                                    Gesamtgutschriften:
                                </td>
                                <td class="px-3 py-2 text-right text-sm font-bold text-green-800">
                                    {{ number_format(array_sum(array_column($getRecord()->credit_breakdown, 'customer_share')), 2, ',', '.') }} €
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif

        {{-- Gesamtübersicht --}}
        @if(($getRecord()->cost_breakdown && count($getRecord()->cost_breakdown) > 0) || ($getRecord()->credit_breakdown && count($getRecord()->credit_breakdown) > 0))
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-800">Nettobetrag:</span>
                    <span class="text-sm font-bold text-gray-900">
                        {{ $getRecord()->formatted_net_amount }}
                    </span>
                </div>
            </div>
        @endif
    </div>
</div>
