<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header with Path Navigation -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium text-gray-900">
                    DigitalOcean Spaces Browser
                </h2>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">
                        {{ $this->getTotalDirectoriesCount() }} Ordner, {{ $this->getTotalFilesCount() }} Dateien
                    </span>
                </div>
            </div>
            
            <!-- Breadcrumb Navigation -->
            <nav class="flex items-center space-x-2 mb-4">
                @foreach($this->breadcrumbs as $index => $breadcrumb)
                    @if($index > 0)
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                    @if($index === count($this->breadcrumbs) - 1)
                        <span class="text-gray-900 font-medium">{{ $breadcrumb['name'] }}</span>
                    @else
                        <button 
                            wire:click="navigateToBreadcrumb('{{ $breadcrumb['path'] }}')"
                            class="text-blue-600 hover:text-blue-800 hover:underline"
                        >
                            {{ $breadcrumb['name'] }}
                        </button>
                    @endif
                @endforeach
            </nav>

            <!-- Navigation Controls -->
            <div class="flex items-center space-x-2">
                @if($this->currentPath)
                    <button 
                        wire:click="navigateUp"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Zurück
                    </button>
                @endif
                
                <!-- Current Path Display -->
                <div class="flex-1 bg-gray-50 rounded-md px-3 py-2 text-sm text-gray-700">
                    <span class="font-medium">Aktueller Pfad:</span>
                    {{ $this->getCurrentPathDisplay() }}
                </div>
            </div>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            {{ $this->form }}
        </div>

        <!-- Files Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            {{ $this->table }}
        </div>
    </div>

    @push('scripts')
    <script>
        // Listen for custom notify events
        window.addEventListener('notify', function(event) {
            if (event.detail.type === 'success') {
                window.$wireui.notify({
                    title: 'Erfolg',
                    description: event.detail.message,
                    icon: 'success'
                });
            } else if (event.detail.type === 'error') {
                window.$wireui.notify({
                    title: 'Fehler',
                    description: event.detail.message,
                    icon: 'error'
                });
            }
        });

        // Handle file double-click to open directories
        document.addEventListener('DOMContentLoaded', function() {
            // Add double-click listener to table rows
            const table = document.querySelector('[wire\\:id]');
            if (table) {
                table.addEventListener('dblclick', function(event) {
                    const row = event.target.closest('tr');
                    if (row && row.dataset.recordKey) {
                        const openButton = row.querySelector('[wire\\:click*="open"]');
                        if (openButton) {
                            openButton.click();
                        }
                    }
                });
            }
        });
    </script>
    @endpush

    @push('styles')
    <style>
        /* Custom styles for better file browser experience */
        .filament-tables-table tbody tr {
            cursor: pointer;
        }
        
        .filament-tables-table tbody tr:hover {
            background-color: rgb(249 250 251);
        }

        /* File type icons styling */
        .filament-tables-icon-column {
            width: 48px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .filament-tables-table .hidden.sm\\:table-cell {
                display: none !important;
            }
        }

        /* Breadcrumb styling */
        nav button:hover {
            text-decoration: underline;
        }

        /* Search input styling */
        .fi-fo-text-input input {
            width: 100%;
        }

        /* Loading state */
        [wire\\:loading] {
            opacity: 0.6;
        }
    </style>
    @endpush
</x-filament-panels::page>
