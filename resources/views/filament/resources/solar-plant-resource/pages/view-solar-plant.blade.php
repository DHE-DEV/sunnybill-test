<x-filament-panels::page>
    @if (filled($this->getTitle()))
        <x-slot name="title">
            {{ $this->getTitle() }}
        </x-slot>
    @endif

    @if (filled($this->getSubheading()))
        <x-slot name="subheading">
            {{ $this->getSubheading() }}
        </x-slot>
    @endif

    {{-- Header Actions --}}
    <x-slot name="headerActions">
        {{ $this->getHeaderActionsPosition() }}
    </x-slot>

    {{-- Infolist Section --}}
    <div class="fi-infolist">
        {{ $this->infolist }}
    </div>

    {{-- RelationManager Sections --}}
    <div class="space-y-6" data-table-name="{{ $infolistTableName }}">
        {{-- Section 1: Kunden --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-x-3">
                    <x-heroicon-o-users class="h-6 w-6 text-gray-500" />
                    <span>Kunden</span>
                </div>
            </x-slot>

            <x-slot name="description">
                Beteiligungen und Abrechnungen der Kunden
            </x-slot>

            <x-slot name="headerActions">
                {{-- Optional: Header actions for this section --}}
            </x-slot>

            <div class="space-y-6">
                {{-- Beteiligungen (Index 1) --}}
                <div data-section-id="customers-participations" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-chart-pie class="h-5 w-5 text-gray-500" />
                        Beteiligungen
                    </h3>
                    {{ $this->getRelationManagerInstance('participations') }}
                </div>

                {{-- Kundenabrechnungen (Index 2) --}}
                <div data-section-id="customers-billings" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-document-currency-euro class="h-5 w-5 text-gray-500" />
                        Kundenabrechnungen
                    </h3>
                    {{ $this->getRelationManagerInstance('billings') }}
                </div>
            </div>
        </x-filament::section>

        {{-- Section 2: Lieferanten --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-x-3">
                    <x-heroicon-o-building-office-2 class="h-6 w-6 text-gray-500" />
                    <span>Lieferanten</span>
                </div>
            </x-slot>

            <x-slot name="description">
                Lieferanten und deren Verträge
            </x-slot>

            <div class="space-y-6">
                {{-- Lieferanten (Index 6) --}}
                <div data-section-id="suppliers-list" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-building-office class="h-5 w-5 text-gray-500" />
                        Lieferanten
                    </h3>
                    {{ $this->getRelationManagerInstance('supplierAssignments') }}
                </div>

                {{-- Verträge (Index 5) --}}
                <div data-section-id="suppliers-contracts" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-document-text class="h-5 w-5 text-gray-500" />
                        Verträge
                    </h3>
                    {{ $this->getRelationManagerInstance('contracts') }}
                </div>
            </div>
        </x-filament::section>

        {{-- Section 3: Weitere --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-x-3">
                    <x-heroicon-o-squares-2x2 class="h-6 w-6 text-gray-500" />
                    <span>Weitere</span>
                </div>
            </x-slot>

            <x-slot name="description">
                Dokumente, Artikel, Abrechnungen und weitere Informationen
            </x-slot>

            <div class="space-y-6">
                {{-- Dokumente (Index 4) --}}
                <div data-section-id="misc-documents" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-document-arrow-down class="h-5 w-5 text-gray-500" />
                        Dokumente
                    </h3>
                    {{ $this->getRelationManagerInstance('documents') }}
                </div>

                {{-- Artikel (Index 0) --}}
                <div data-section-id="misc-articles" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-squares-plus class="h-5 w-5 text-gray-500" />
                        Artikel
                    </h3>
                    {{ $this->getRelationManagerInstance('articles') }}
                </div>

                {{-- Abrechnungen (Index 3) --}}
                <div data-section-id="misc-monthly-results" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-chart-bar class="h-5 w-5 text-gray-500" />
                        Abrechnungen
                    </h3>
                    {{ $this->getRelationManagerInstance('monthlyResults') }}
                </div>

                {{-- Termine (Index 7) --}}
                <div data-section-id="misc-milestones" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-calendar-days class="h-5 w-5 text-gray-500" />
                        Termine
                    </h3>
                    {{ $this->getRelationManagerInstance('milestones') }}
                </div>

                {{-- Notizen (Favoriten) --}}
                @if(method_exists($this, 'getRelationManagerInstance') && $this->getRelationManagerInstance('favoriteNotes'))
                <div data-section-id="misc-favorite-notes" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-star class="h-5 w-5 text-yellow-500" />
                        Notizen (Favoriten)
                    </h3>
                    {{ $this->getRelationManagerInstance('favoriteNotes') }}
                </div>
                @endif

                {{-- Notizen (Standard) --}}
                @if(method_exists($this, 'getRelationManagerInstance') && $this->getRelationManagerInstance('notes'))
                <div data-section-id="misc-notes" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-document-text class="h-5 w-5 text-gray-500" />
                        Notizen (Standard)
                    </h3>
                    {{ $this->getRelationManagerInstance('notes') }}
                </div>
                @endif
            </div>
        </x-filament::section>
    </div>

    {{-- Custom Styles for Relation Sections --}}
    <style>
        .relation-section {
            background-color: var(--gray-50);
            border-radius: 0.75rem;
            padding: 1.5rem;
            border: 1px solid var(--gray-200);
        }

        .dark .relation-section {
            background-color: var(--gray-900);
            border-color: var(--gray-700);
        }

        .relation-section h3 {
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .dark .relation-section h3 {
            border-bottom-color: var(--gray-700);
        }

        /* Section spacing improvements */
        .space-y-6 > * + * {
            margin-top: 1.5rem;
        }

        /* Ensure proper section collapsibility */
        [data-section-id] {
            transition: all 0.2s ease-in-out;
        }
    </style>

    {{-- JavaScript for section state persistence --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize infolist state management if the script is loaded
            if (typeof initializeInfolistState === 'function') {
                initializeInfolistState();
            }
        });
    </script>
</x-filament-panels::page>
