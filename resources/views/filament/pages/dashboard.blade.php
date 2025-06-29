<x-filament-panels::page>
    <div class="grid gap-6">
        <!-- Willkommensbereich -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                Willkommen bei VoltMaster
            </h2>
            <p class="text-gray-600 mb-4">
                Ihr umfassendes Solar Plant Management System mit Lexoffice-Integration.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                <div class="bg-orange-50 p-4 rounded-lg">
                    <h3 class="font-medium text-orange-900 mb-2">Aufgaben</h3>
                    <p class="text-sm text-orange-700">Verwalten Sie Aufgaben mit Prioritäten, Terminen und Zuweisungen für effiziente Projektabwicklung.</p>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg">
                    <h3 class="font-medium text-indigo-900 mb-2">Projekte</h3>
                    <p class="text-sm text-indigo-700">Überwachen Sie Projektmeilensteine und verfolgen Sie den Fortschritt Ihrer Solaranlagen-Projekte.</p>
                </div>
            </div>
        </div>

        <!-- Aufgaben -->
        <div class="bg-white rounded-lg shadow" x-data="{ open: false }">
            <div class="p-6 border-b border-gray-200">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h3 class="text-lg font-semibold text-gray-900">Aufgaben</h3>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @livewire(\App\Filament\Widgets\TasksTodayWidget::class)
                    @livewire(\App\Filament\Widgets\TasksThisWeekWidget::class)
                    @livewire(\App\Filament\Widgets\TasksThisMonthWidget::class)
                </div>
            </div>
        </div>

        <!-- Solaranlagen -->
        <div class="bg-white rounded-lg shadow" x-data="{ open: false }">
            <div class="p-6 border-b border-gray-200">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h3 class="text-lg font-semibold text-gray-900">Solaranlagen</h3>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="p-6">
                <div class="grid grid-cols-1 gap-4">
                    @livewire(\App\Filament\Widgets\SolarPlantStatsWidget::class)
                </div>
            </div>
        </div>

        <!-- Kunden -->
        <div class="bg-white rounded-lg shadow" x-data="{ open: false }">
            <div class="p-6 border-b border-gray-200">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h3 class="text-lg font-semibold text-gray-900">Kunden</h3>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="p-6">
                <div class="grid grid-cols-1 gap-4">
                    @livewire(\App\Filament\Widgets\CustomerStatsWidget::class)
                </div>
            </div>
        </div>

        <!-- Lieferanten -->
        <div class="bg-white rounded-lg shadow" x-data="{ open: false }">
            <div class="p-6 border-b border-gray-200">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h3 class="text-lg font-semibold text-gray-900">Lieferanten</h3>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="p-6">
                <div class="grid grid-cols-1 gap-4">
                    @livewire(\App\Filament\Widgets\SupplierStatsWidget::class)
                </div>
            </div>
        </div>

        <!-- Rechnungen -->
        <div class="bg-white rounded-lg shadow" x-data="{ open: false }">
            <div class="p-6 border-b border-gray-200">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h3 class="text-lg font-semibold text-gray-900">Rechnungen</h3>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="p-6">
                <div class="grid grid-cols-1 gap-4">
                    @livewire(\App\Filament\Widgets\InvoiceStatsWidget::class)
                </div>
            </div>
        </div>

        <!-- Artikel -->
        <div class="bg-white rounded-lg shadow" x-data="{ open: false }">
            <div class="p-6 border-b border-gray-200">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h3 class="text-lg font-semibold text-gray-900">Artikel</h3>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="p-6">
                <div class="grid grid-cols-1 gap-4">
                    @livewire(\App\Filament\Widgets\ArticleStatsWidget::class)
                </div>
            </div>
        </div>

        <!-- Diagramme & Analysen -->
        <div class="bg-white rounded-lg shadow" x-data="{ open: false }">
            <div class="p-6 border-b border-gray-200">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h3 class="text-lg font-semibold text-gray-900">Diagramme & Analysen</h3>
                    <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
            </div>
            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95" class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    @livewire(\App\Filament\Widgets\InvoiceRevenueChartWidget::class)
                    @livewire(\App\Filament\Widgets\SolarPlantCapacityChartWidget::class)
                    @livewire(\App\Filament\Widgets\CustomerGrowthChartWidget::class)
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>