<div class="space-y-6">
    {{-- Header mit wichtigsten Informationen --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 rounded-lg border border-blue-200">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $billing->billing_number }}</h2>
                <p class="text-sm text-gray-600 mt-1">{{ $billing->title }}</p>
                @if($billing->supplier_invoice_number)
                    <p class="text-sm text-blue-600 mt-1">
                        <span class="font-medium">Anbieter-Rechnung:</span> {{ $billing->supplier_invoice_number }}
                    </p>
                @endif
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-gray-900">
                    €{{ number_format($billing->total_amount, 2, ',', '.') }}
                </div>
                <div class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($billing->status === 'paid') bg-green-100 text-green-800
                        @elseif($billing->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($billing->status === 'cancelled') bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ \App\Models\SupplierContractBilling::getStatusOptions()[$billing->status] ?? $billing->status }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Grundinformationen --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                Abrechnungsdetails
            </h3>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Abrechnungstyp:</span>
                    <span class="text-sm text-gray-900">
                        {{ \App\Models\SupplierContractBilling::getBillingTypeOptions()[$billing->billing_type] ?? $billing->billing_type }}
                    </span>
                </div>
                
                @if($billing->billing_period)
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Abrechnungsperiode:</span>
                    <span class="text-sm text-gray-900">{{ $billing->billing_period }}</span>
                </div>
                @endif
                
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Abrechnungsdatum:</span>
                    <span class="text-sm text-gray-900">{{ $billing->billing_date?->format('d.m.Y') ?? '—' }}</span>
                </div>
                
                @if($billing->due_date)
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Fälligkeitsdatum:</span>
                    <span class="text-sm text-gray-900 
                        @if($billing->due_date->isPast() && $billing->status !== 'paid') text-red-600 font-medium @endif">
                        {{ $billing->due_date->format('d.m.Y') }}
                        @if($billing->due_date->isPast() && $billing->status !== 'paid')
                            <span class="text-xs">(überfällig)</span>
                        @endif
                    </span>
                </div>
                @endif
                
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Währung:</span>
                    <span class="text-sm text-gray-900">{{ $billing->currency ?? 'EUR' }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
                Vertragsinformationen
            </h3>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Vertragsnummer:</span>
                    <span class="text-sm text-gray-900">{{ $billing->supplierContract?->contract_number ?? '—' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Lieferant:</span>
                    <span class="text-sm text-gray-900">{{ $billing->supplierContract?->supplier?->company_name ?? '—' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm font-medium text-gray-500">Vertragstyp:</span>
                    <span class="text-sm text-gray-900">{{ $billing->supplierContract?->supplier?->supplierType?->name ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Kostenträger-Aufteilung --}}
    @if($billing->allocations->isNotEmpty())
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
            Kostenträger-Aufteilung
        </h3>
        
        <div class="bg-gray-50 p-4 rounded-lg">
            <div class="grid grid-cols-3 gap-4 text-sm mb-4">
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900">{{ $billing->allocations->count() }}</div>
                    <div class="text-gray-600">Kostenträger</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900">{{ $billing->allocations->sum('percentage') }}%</div>
                    <div class="text-gray-600">Aufgeteilt</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-900">€{{ number_format($billing->allocations->sum('amount'), 2, ',', '.') }}</div>
                    <div class="text-gray-600">Gesamtbetrag</div>
                </div>
            </div>
        </div>
        
        <div class="space-y-2">
            @foreach($billing->allocations as $allocation)
            <div class="flex justify-between items-center p-3 bg-white border rounded-lg hover:bg-gray-50 transition-colors">
                <div class="flex-1">
                    <div class="font-medium text-gray-900">
                        {{ $allocation->solarPlant?->name ?? 'Unbekannte Anlage' }}
                    </div>
                    @if($allocation->solarPlant?->plant_number)
                    <div class="text-sm text-gray-500">
                        Anlagen-Nr.: {{ $allocation->solarPlant->plant_number }}
                    </div>
                    @endif
                </div>
                <div class="text-right">
                    <div class="font-semibold text-gray-900">
                        €{{ number_format($allocation->amount, 2, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $allocation->percentage }}%
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        @php
            $totalPercentage = $billing->allocations->sum('percentage');
            $remainingPercentage = 100 - $totalPercentage;
        @endphp
        
        @if($remainingPercentage > 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm text-yellow-800">
                    <strong>Unvollständige Aufteilung:</strong> {{ $remainingPercentage }}% (€{{ number_format(($billing->total_amount * $remainingPercentage) / 100, 2, ',', '.') }}) noch nicht aufgeteilt
                </span>
            </div>
        </div>
        @endif
    </div>
    @else
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
            Kostenträger-Aufteilung
        </h3>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <svg class="w-8 h-8 text-red-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <p class="text-sm text-red-800">
                <strong>Keine Kostenträger-Aufteilung vorhanden</strong><br>
                Diese Abrechnung wurde noch nicht auf Kostenträger aufgeteilt.
            </p>
        </div>
    </div>
    @endif

    {{-- Beschreibung und Notizen --}}
    @if($billing->description || $billing->notes)
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
            Zusätzliche Informationen
        </h3>
        
        @if($billing->description)
        <div>
            <h4 class="text-sm font-medium text-gray-700 mb-2">Beschreibung:</h4>
            <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{{ $billing->description }}</p>
        </div>
        @endif
        
        @if($billing->notes)
        <div>
            <h4 class="text-sm font-medium text-gray-700 mb-2">Notizen:</h4>
            <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{{ $billing->notes }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Dokumente --}}
    @if($billing->documents->isNotEmpty())
    <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 border-b border-gray-200 pb-2">
            Dokumente ({{ $billing->documents->count() }})
        </h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($billing->documents as $document)
            <div class="flex items-center p-3 bg-white border rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $document->title }}</p>
                    <p class="text-xs text-gray-500">{{ $document->created_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Zeitstempel --}}
    <div class="border-t border-gray-200 pt-4">
        <div class="grid grid-cols-2 gap-4 text-xs text-gray-500">
            <div>
                <strong>Erstellt:</strong> {{ $billing->created_at->format('d.m.Y H:i') }}
            </div>
            <div>
                <strong>Zuletzt geändert:</strong> {{ $billing->updated_at->format('d.m.Y H:i') }}
            </div>
        </div>
    </div>
</div>