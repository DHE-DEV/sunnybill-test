@if(isset($error))
    <div class="text-red-600">{{ $error }}</div>
@else
    <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
        <div>
            <h4 class="font-semibold text-gray-900 mb-2">Abrechnung: {{ $billing->billing_number }}</h4>
            <div class="space-y-1 text-sm">
                <div><strong>Gesamtbetrag:</strong> {{ number_format($totalAmount, 2, ',', '.') }} €</div>
                <div><strong>Status:</strong> {{ $billing->status_label ?? 'Unbekannt' }}</div>
            </div>
        </div>
        <div>
            <h4 class="font-semibold text-gray-900 mb-2">Aufteilung</h4>
            <div class="space-y-1 text-sm">
                <div class="{{ $percentageColor }}"><strong>Verteilt:</strong> {{ number_format($allocatedPercentage, 2, ',', '.') }}%</div>
                <div class="{{ $amountColor }}"><strong>Betrag:</strong> {{ number_format($allocatedAmount, 2, ',', '.') }} €</div>
                <div><strong>Verfügbar:</strong> {{ number_format($remainingPercentage, 2, ',', '.') }}% ({{ number_format($remainingAmount, 2, ',', '.') }} €)</div>
            </div>
        </div>
    </div>
@endif