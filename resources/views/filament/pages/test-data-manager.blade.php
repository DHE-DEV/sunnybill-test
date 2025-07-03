<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Übersicht -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Test-Datenmanager</h2>
            <p class="text-gray-600 mb-4">
                Hier können Sie eine saubere Testdatenbank mit den Solaranlagen "Aurich 1" und "Aurich 2" 
                sowie allen zugehörigen Daten erstellen oder wiederherstellen.
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-medium text-blue-900 mb-2">Enthaltene Testdaten:</h3>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• 2 Solaranlagen (Aurich 1 & Aurich 2)</li>
                        <li>• 2 Kunden (Stadtwerke Aurich, Energie Nord)</li>
                        <li>• 2 Lieferanten (EWE, Solar Service Nord)</li>
                        <li>• 2 Lieferantenverträge</li>
                        <li>• 8 Abrechnungen (verschiedene Status)</li>
                        <li>• Firmeneinstellungen & Konfiguration</li>
                    </ul>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="font-medium text-yellow-900 mb-2">Zusätzliche Stammdaten:</h3>
                    <ul class="text-sm text-yellow-800 space-y-1">
                        <li>• Steuersätze (19%, 7%, 0%)</li>
                        <li>• Artikel (Stromlieferung, Grundgebühr)</li>
                        <li>• Aufgabentypen (Installation, Wartung, Reparatur)</li>
                        <li>• Speichereinstellungen</li>
                        <li>• Dokumentpfade</li>
                        <li>• Lieferantentypen</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Aktuelle Datenbank-Statistiken -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aktuelle Datenbank-Statistiken</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ \App\Models\SolarPlant::count() }}</div>
                    <div class="text-sm text-gray-600">Solaranlagen</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ \App\Models\Customer::count() }}</div>
                    <div class="text-sm text-gray-600">Kunden</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ \App\Models\Supplier::count() }}</div>
                    <div class="text-sm text-gray-600">Lieferanten</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">{{ \App\Models\SupplierContract::count() }}</div>
                    <div class="text-sm text-gray-600">Verträge</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ \App\Models\SupplierContractBilling::count() }}</div>
                    <div class="text-sm text-gray-600">Abrechnungen</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-indigo-600">{{ \App\Models\Article::count() }}</div>
                    <div class="text-sm text-gray-600">Artikel</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-pink-600">{{ \App\Models\TaxRate::count() }}</div>
                    <div class="text-sm text-gray-600">Steuersätze</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-teal-600">{{ \App\Models\TaskType::count() }}</div>
                    <div class="text-sm text-gray-600">Aufgabentypen</div>
                </div>
            </div>
        </div>

        <!-- Warnhinweise -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Wichtige Hinweise</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong>Testdaten zurücksetzen</strong> löscht ALLE vorhandenen Daten unwiderruflich</li>
                            <li>Der Admin-Benutzer und die Migrationstabelle bleiben erhalten</li>
                            <li>Diese Funktion sollte nur in Entwicklungs- und Testumgebungen verwendet werden</li>
                            <li>Erstellen Sie vor dem Zurücksetzen ein Backup, falls nötig</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Letzte Solaranlagen -->
        @if(\App\Models\SolarPlant::count() > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aktuelle Solaranlagen</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anlagennummer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Standort</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leistung</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach(\App\Models\SolarPlant::latest()->take(10)->get() as $plant)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $plant->plant_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $plant->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $plant->location }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($plant->capacity_kw, 1) }} kW</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $plant->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($plant->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>