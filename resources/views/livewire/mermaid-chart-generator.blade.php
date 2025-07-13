<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4">
        <!-- Solaranlage auswählen -->
        <div>
            <label for="solar-plant-select" class="block text-sm font-medium text-gray-700 mb-2">
                Solaranlage auswählen
            </label>
            <select
                id="solar-plant-select"
                wire:model="selectedSolarPlantId"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
                <option value="">Wählen Sie eine Solaranlage aus...</option>
                @foreach(\App\Models\SolarPlant::all() as $plant)
                    <option value="{{ $plant->id }}">{{ $plant->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Aktionen -->
        <div class="flex space-x-3">
            <button
                type="button"
                wire:click="generateChart"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                wire:loading.attr="disabled"
                @if(empty($selectedSolarPlantId)) disabled @endif
            >
                <span wire:loading.remove wire:target="generateChart">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Generieren
                </span>
                <span wire:loading wire:target="generateChart" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Generiere...
                </span>
            </button>
            
            @if(!empty($generatedChart))
            <button
                type="button"
                wire:click="copyToClipboard"
                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Kopieren
            </button>
            @endif
        </div>

        <!-- Generierter Code -->
        @if(!empty($generatedChart))
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Generierter Mermaid-Code
            </label>
            <textarea
                readonly
                rows="15"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-xs"
                wire:model="generatedChart"
            ></textarea>
        </div>

        <!-- Vorschau -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Vorschau
            </label>
            <div class="border rounded-lg p-4 bg-gray-50 min-h-[400px]">
                <div id="mermaid-preview" class="text-center">
                    <!-- Mermaid chart will be rendered here -->
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        mermaid.initialize({
            startOnLoad: true,
            theme: 'default',
            flowchart: {
                useMaxWidth: true,
                htmlLabels: true
            }
        });
    });

    // Listen for chart updates
    document.addEventListener('livewire:updated', function() {
        const chartCode = @this.generatedChart;
        if (chartCode) {
            const previewElement = document.getElementById('mermaid-preview');
            if (previewElement) {
                previewElement.innerHTML = '<div class="mermaid">' + chartCode + '</div>';
                mermaid.init(undefined, previewElement.querySelector('.mermaid'));
            }
        }
    });

    // Copy to clipboard functionality
    Livewire.on('copy-to-clipboard', (chartCode) => {
        navigator.clipboard.writeText(chartCode).then(function() {
            console.log('Chart code copied to clipboard');
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
        });
    });
</script>
@endpush

@push('styles')
<style>
    .mermaid {
        font-family: 'Inter', sans-serif;
    }
    
    .mermaid svg {
        max-width: 100%;
        height: auto;
    }
</style>
@endpush