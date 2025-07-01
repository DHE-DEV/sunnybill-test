<div class="document-upload-component">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            {{ $config['title'] ?? 'Dokumente' }}
        </h3>
        
        @if($config['showUploadForm'] ?? true)
            <x-filament::button
                wire:click="openCreateModal"
                icon="heroicon-o-plus"
                size="sm"
            >
                {{ $config['createButtonLabel'] ?? 'Dokument hinzufügen' }}
            </x-filament::button>
        @endif
    </div>

    {{-- Tabelle --}}
    @if($config['showTable'] ?? true)
        <div class="document-table">
            {{ $this->table }}
        </div>
    @endif

    {{-- Upload Modal --}}
    <x-filament::modal
        id="document-upload-modal"
        :visible="$showForm"
        width="{{ $config['modalWidth'] ?? '4xl' }}"
        :close-by-clicking-away="false"
    >
        <x-slot name="header">
            <div class="flex items-center gap-x-3">
                <x-filament::icon
                    icon="heroicon-o-document-plus"
                    class="h-6 w-6 text-gray-400"
                />
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $config['createButtonLabel'] ?? 'Dokument hinzufügen' }}
                </h2>
            </div>
        </x-slot>

        <form wire:submit="create" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end gap-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <x-filament::button
                    type="button"
                    color="gray"
                    wire:click="closeCreateModal"
                >
                    Abbrechen
                </x-filament::button>

                <x-filament::button
                    type="submit"
                    icon="heroicon-o-cloud-arrow-up"
                >
                    Hochladen
                </x-filament::button>
            </div>
        </form>
    </x-filament::modal>

    {{-- Drag & Drop Zone (Optional) --}}
    @if($config['enableDragDrop'] ?? false)
        <div 
            class="mt-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
            x-data="documentDragDrop()"
            x-on:drop.prevent="handleDrop($event)"
            x-on:dragover.prevent
            x-on:dragenter.prevent
            :class="{ 'border-primary-500 bg-primary-50 dark:bg-primary-900/20': isDragging }"
            x-on:dragenter="isDragging = true"
            x-on:dragleave="isDragging = false"
            x-on:drop="isDragging = false"
        >
            <x-filament::icon
                icon="heroicon-o-cloud-arrow-up"
                class="mx-auto h-12 w-12 text-gray-400"
            />
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Dateien hier hineinziehen oder 
                <button 
                    type="button" 
                    class="text-primary-600 hover:text-primary-500 font-medium"
                    wire:click="openCreateModal"
                >
                    durchsuchen
                </button>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Maximale Dateigröße: {{ number_format(($config['maxSize'] ?? 10240) / 1024, 1) }}MB
            </p>
        </div>

        <script>
            function documentDragDrop() {
                return {
                    isDragging: false,
                    handleDrop(event) {
                        const files = Array.from(event.dataTransfer.files);
                        if (files.length > 0) {
                            // Hier könnte eine Bulk-Upload-Funktionalität implementiert werden
                            @this.call('openCreateModal');
                        }
                    }
                }
            }
        </script>
    @endif

    {{-- Quick Stats (Optional) --}}
    @if($config['showStats'] ?? false)
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <x-filament::icon
                        icon="heroicon-o-document"
                        class="h-5 w-5 text-gray-400 mr-2"
                    />
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            Gesamt
                        </p>
                        <p class="text-lg font-semibold text-gray-600 dark:text-gray-300">
                            {{ $this->getRelationship()->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <x-filament::icon
                        icon="heroicon-o-clock"
                        class="h-5 w-5 text-gray-400 mr-2"
                    />
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            Heute
                        </p>
                        <p class="text-lg font-semibold text-gray-600 dark:text-gray-300">
                            {{ $this->getRelationship()->whereDate('created_at', today())->count() }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <x-filament::icon
                        icon="heroicon-o-archive-box"
                        class="h-5 w-5 text-gray-400 mr-2"
                    />
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            Größe
                        </p>
                        <p class="text-lg font-semibold text-gray-600 dark:text-gray-300">
                            {{ $this->getFormattedTotalSize() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.document-upload-component .fi-ta-table {
    @apply border border-gray-200 dark:border-gray-700 rounded-lg;
}

.document-upload-component .fi-ta-header {
    @apply bg-gray-50 dark:bg-gray-800/50;
}

.document-upload-component .fi-ta-row:hover {
    @apply bg-gray-50 dark:bg-gray-800/50;
}
</style>