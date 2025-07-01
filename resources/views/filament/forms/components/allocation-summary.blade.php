<div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
    <div>
        <h4 class="font-semibold text-gray-900 mb-2">Gesamtbetrag</h4>
        <div class="space-y-1 text-sm">
            <div><strong>Abrechnung:</strong> {{ number_format($totalAmount, 2, ',', '.') }} €</div>
            <div class="{{ $amountColor }}"><strong>Verteilt:</strong> {{ number_format($totalAllocatedAmount, 2, ',', '.') }} €</div>
            <div><strong>Verfügbar:</strong> {{ number_format($remainingAmount, 2, ',', '.') }} €</div>
        </div>
    </div>
    <div>
        <h4 class="font-semibold text-gray-900 mb-2">Prozentuale Aufteilung</h4>
        <div class="space-y-1 text-sm">
            <div class="{{ $percentageColor }}"><strong>Verteilt:</strong> {{ number_format($totalPercentage, 2, ',', '.') }}%</div>
            <div><strong>Verfügbar:</strong> {{ number_format($remainingPercentage, 2, ',', '.') }}%</div>
            <div class="text-xs text-gray-500 mt-2">
                {{ $allocationsCount }} Aufteilung(en) definiert
            </div>
        </div>
    </div>
</div>