<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Debug-Informationen</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $debugInfo['billings_count'] ?? 0 }}</div>
                    <div class="text-sm text-blue-800">Abrechnungen</div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ $debugInfo['allocations_count'] ?? 0 }}</div>
                    <div class="text-sm text-green-800">Aufteilungen</div>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">{{ $debugInfo['solar_plants_count'] ?? 0 }}</div>
                    <div class="text-sm text-purple-800">Aktive Solaranlagen</div>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600">
                        {{ $debugInfo['recent_billing']->allocations->count() ?? 0 }}
                    </div>
                    <div class="text-sm text-yellow-800">Aufteilungen (letzte Abrechnung)</div>
                </div>
            </div>
            
            @if($debugInfo['recent_billing'] ?? false)
                <div class="border-t pt-4">
                    <h4 class="font-medium text-gray-900 mb-2">Letzte Abrechnung:</h4>
                    <div class="bg-gray-50 p-4 rounded">
                        <p><strong>Nummer:</strong> {{ $debugInfo['recent_billing']->billing_number }}</p>
                        <p><strong>Titel:</strong> {{ $debugInfo['recent_billing']->title }}</p>
                        <p><strong>Gesamtbetrag:</strong> {{ number_format($debugInfo['recent_billing']->total_amount, 2, ',', '.') }} €</p>
                        <p><strong>Vertrag:</strong> {{ $debugInfo['recent_billing']->supplierContract->title ?? 'Kein Vertrag' }}</p>
                        <p><strong>Aufteilungen:</strong> {{ $debugInfo['recent_billing']->allocations->count() }}</p>
                        
                        @if($debugInfo['recent_billing']->allocations->count() > 0)
                            <div class="mt-4">
                                <h5 class="font-medium mb-2">Aufteilungen:</h5>
                                <div class="space-y-2">
                                    @foreach($debugInfo['recent_billing']->allocations as $allocation)
                                        <div class="bg-white p-3 rounded border">
                                            <p><strong>Solaranlage:</strong> {{ $allocation->solarPlant->name ?? 'Unbekannt' }}</p>
                                            <p><strong>Prozentsatz:</strong> {{ number_format($allocation->percentage, 2, ',', '.') }}%</p>
                                            <p><strong>Betrag:</strong> {{ number_format($allocation->amount, 2, ',', '.') }} €</p>
                                            <p><strong>Aktiv:</strong> {{ $allocation->is_active ? 'Ja' : 'Nein' }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="border-t pt-4">
                    <p class="text-gray-500">Keine Abrechnungen vorhanden.</p>
                </div>
            @endif
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Verfügbare Solaranlagen</h3>
            
            @php
                $solarPlants = \App\Models\SolarPlant::where('is_active', true)->get();
            @endphp
            
            @if($solarPlants->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($solarPlants as $plant)
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900">{{ $plant->name }}</h4>
                            <p class="text-sm text-gray-600">{{ $plant->location }}</p>
                            <p class="text-sm text-gray-600">ID: {{ $plant->id }}</p>
                            @if($plant->total_capacity_kw)
                                <p class="text-sm text-gray-600">{{ number_format($plant->total_capacity_kw, 2, ',', '.') }} kW</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">Keine aktiven Solaranlagen vorhanden.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>