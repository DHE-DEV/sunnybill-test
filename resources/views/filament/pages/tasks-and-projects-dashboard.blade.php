<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Filter</h3>
            {{ $this->form }}
        </div>

        <!-- Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach ($this->getWidgets() as $widget)
                @livewire($widget, ['timeFilter' => $this->getTimeFilter()], key($widget))
            @endforeach
        </div>
    </div>

    @script
    <script>
        // Stelle sicher, dass der initiale Filter nach dem Laden der Widgets angewendet wird
        document.addEventListener('livewire:navigated', function () {
            setTimeout(() => {
                $wire.dispatch('timeFilterChanged', { timeFilter: $wire.get('timeFilter') });
            }, 100);
        });
        
        // Für den ersten Seitenaufruf
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(() => {
                $wire.dispatch('timeFilterChanged', { timeFilter: $wire.get('timeFilter') });
            }, 100);
        });
    </script>
    @endscript
</x-filament-panels::page>