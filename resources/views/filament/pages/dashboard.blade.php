<x-filament-panels::page>
    <div class="grid gap-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($this->getWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                Willkommen bei SunnyBill
            </h2>
            <p class="text-gray-600 mb-4">
                Ihr umfassendes Solar Plant Management System mit Lexoffice-Integration.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-medium text-blue-900 mb-2">Solaranlagen</h3>
                    <p class="text-sm text-blue-700">Verwalten Sie Ihre Solaranlagen mit detaillierten technischen und finanziellen Daten.</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="font-medium text-green-900 mb-2">Kundenbeteiligungen</h3>
                    <p class="text-sm text-green-700">Verteilen Sie Anteile an Solaranlagen und berechnen Sie automatisch Gutschriften.</p>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h3 class="font-medium text-yellow-900 mb-2">Monatliche Ergebnisse</h3>
                    <p class="text-sm text-yellow-700">Erfassen Sie Produktions- und Verbrauchsdaten mit 6-stelliger Präzision.</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h3 class="font-medium text-purple-900 mb-2">Lexoffice Integration</h3>
                    <p class="text-sm text-purple-700">Synchronisieren Sie Kunden, Artikel und Rechnungen bidirektional.</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>