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
        <x-filament-actions::actions 
            :actions="$this->getHeaderActions()" 
            :alignment="\Filament\Support\Enums\Alignment::Start" />
    </x-slot>

    {{-- Infolist Section --}}
    <div class="fi-infolist">
        {{ $this->infolist }}
    </div>

    {{-- RelationManager Sections --}}
    <div class="space-y-6" data-table-name="{{ $infolistTableName }}">
        {{-- Section 1: Kunden --}}
        <div data-section-id="customers" class="customers-section-gray" style="background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-x-3">
                        <x-heroicon-o-users class="h-6 w-6 text-gray-500" />
                        <span>Kunden</span>
                    </div>
                </x-slot>

                <x-slot name="description">
                    Übersicht der beteiligten Kunden und deren Informationen
                </x-slot>

                <div class="space-y-6">
                    {{-- Beteiligungsübersicht --}}
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center">
                            <div class="text-xl font-bold text-primary-600">{{ $this->record->participations_count }}</div>
                            <div class="text-sm text-gray-600">Anzahl Beteiligte</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold {{ $this->record->total_participation >= 100 ? 'text-green-600' : 'text-yellow-600' }}">
                                {{ number_format($this->record->total_participation, 1, ',', '.') }}%
                            </div>
                            <div class="text-sm text-gray-600">Gesamtbeteiligung</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold {{ $this->record->available_participation > 0 ? 'text-blue-600' : 'text-gray-600' }}">
                                {{ number_format($this->record->available_participation, 1, ',', '.') }}%
                            </div>
                            <div class="text-sm text-gray-600">Verfügbare Beteiligung</div>
                        </div>
                    </div>

                    {{-- Livewire Tabelle --}}
                    @livewire(\App\Livewire\ParticipationsTable::class, ['solarPlant' => $this->record], key('participations-table'))

                    {{-- Fallback: Standard Beteiligungen RelationManager --}}
                    <div data-section-id="customers-participations" class="relation-section">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                            <x-heroicon-o-chart-pie class="h-5 w-5 text-gray-500" />
                            Beteiligungen (Standard)
                        </h3>
                        @if($this->record)
                            {{ $this->getRelationManagerInstance('participations') }}
                        @else
                            <p class="text-gray-500">Daten werden geladen...</p>
                        @endif
                    </div>

                    {{-- Kundenabrechnungen (Index 2) --}}
                    <div data-section-id="customers-billings" class="relation-section">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                            <x-heroicon-o-document-currency-euro class="h-5 w-5 text-gray-500" />
                            Kundenabrechnungen
                        </h3>
                        @if($this->record)
                            {{ $this->getRelationManagerInstance('billings') }}
                        @else
                            <p class="text-gray-500">Daten werden geladen...</p>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Section 2: Kundenabrechnungen --}}
        <div data-section-id="customer-billings" class="customer-billings-section-gray" style="background-color: #f9fafb !important; border-radius: 8px !important; padding: 16px !important; margin: 8px 0 !important; border: 1px solid #e5e7eb !important;">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-x-3">
                        <x-heroicon-o-document-currency-euro class="h-6 w-6 text-gray-500" />
                        <span>Kundenabrechnungen</span>
                    </div>
                </x-slot>

                <x-slot name="description">
                    Abrechnungen und Rechnungen der Kunden für diese Solaranlage
                </x-slot>

                <div class="space-y-6">
                    {{-- Abrechnungsübersicht --}}
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center">
                            <div class="text-xl font-bold text-primary-600">{{ $this->record->billings_count ?? 0 }}</div>
                            <div class="text-sm text-gray-600">Anzahl Abrechnungen</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-green-600">
                                €{{ number_format($this->record->billings()->sum('total_amount') ?? 0, 2, ',', '.') }}
                            </div>
                            <div class="text-sm text-gray-600">Gesamtbetrag</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xl font-bold text-blue-600">
                                {{ $this->record->billings()->where('status', 'paid')->count() ?? 0 }}
                            </div>
                            <div class="text-sm text-gray-600">Bezahlt</div>
                        </div>
                    </div>

                    {{-- Kundenabrechnungen RelationManager --}}
                    <div data-section-id="customer-billings-table" class="relation-section">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                            <x-heroicon-o-table-cells class="h-5 w-5 text-gray-500" />
                            Abrechnungstabelle
                        </h3>
                        @if($this->record)
                            {{ $this->getRelationManagerInstance('billings') }}
                        @else
                            <p class="text-gray-500">Daten werden geladen...</p>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Section 3: Lieferanten --}}
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
                    @if($this->record)
                        {{ $this->getRelationManagerInstance('supplierAssignments') }}
                    @else
                        <p class="text-gray-500">Daten werden geladen...</p>
                    @endif
                </div>

                {{-- Verträge (Index 5) --}}
                <div data-section-id="suppliers-contracts" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-document-text class="h-5 w-5 text-gray-500" />
                        Verträge
                    </h3>
                    @if($this->record)
                        {{ $this->getRelationManagerInstance('contracts') }}
                    @else
                        <p class="text-gray-500">Daten werden geladen...</p>
                    @endif
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
                    @if($this->record)
                        {{ $this->getRelationManagerInstance('documents') }}
                    @else
                        <p class="text-gray-500">Daten werden geladen...</p>
                    @endif
                </div>

                {{-- Artikel (Index 0) --}}
                <div data-section-id="misc-articles" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-squares-plus class="h-5 w-5 text-gray-500" />
                        Artikel
                    </h3>
                    @if($this->record)
                        {{ $this->getRelationManagerInstance('articles') }}
                    @else
                        <p class="text-gray-500">Daten werden geladen...</p>
                    @endif
                </div>

                {{-- Abrechnungen (Index 3) --}}
                <div data-section-id="misc-monthly-results" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-chart-bar class="h-5 w-5 text-gray-500" />
                        Abrechnungen
                    </h3>
                    @if($this->record)
                        {{ $this->getRelationManagerInstance('monthlyResults') }}
                    @else
                        <p class="text-gray-500">Daten werden geladen...</p>
                    @endif
                </div>

                {{-- Termine (Index 7) --}}
                <div data-section-id="misc-milestones" class="relation-section">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-x-2">
                        <x-heroicon-o-calendar-days class="h-5 w-5 text-gray-500" />
                        Termine
                    </h3>
                    @if($this->record)
                        {{ $this->getRelationManagerInstance('milestones') }}
                    @else
                        <p class="text-gray-500">Daten werden geladen...</p>
                    @endif
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

        /* Kunden Section Gray Background - Direct CSS injection */
        .fi-in-section[data-section-id="customers"],
        .customers-section-gray,
        [data-section-id="customers"] {
            background-color: #f9fafb !important;
            border-radius: 8px !important;
            padding: 16px !important;
            margin: 8px 0 !important;
            border: 1px solid #e5e7eb !important;
        }

        .fi-in-section[data-section-id="customers"] > div,
        .fi-in-section[data-section-id="customers"] .fi-in-section-content {
            background-color: #f9fafb !important;
        }
        
        /* Override any background colors for the customers section */
        section[data-section-id="customers"],
        section[data-section-id="customers"] > div {
            background-color: #f9fafb !important;
            border-radius: 8px !important;
            padding: 16px !important;
        }

        /* Kundenabrechnungen Section Gray Background - Direct CSS injection */
        .fi-in-section[data-section-id="customer-billings"],
        .customer-billings-section-gray,
        [data-section-id="customer-billings"] {
            background-color: #f9fafb !important;
            border-radius: 8px !important;
            padding: 16px !important;
            margin: 8px 0 !important;
            border: 1px solid #e5e7eb !important;
        }

        .fi-in-section[data-section-id="customer-billings"] > div,
        .fi-in-section[data-section-id="customer-billings"] .fi-in-section-content {
            background-color: #f9fafb !important;
        }
        
        /* Override any background colors for the customer-billings section */
        section[data-section-id="customer-billings"],
        section[data-section-id="customer-billings"] > div {
            background-color: #f9fafb !important;
            border-radius: 8px !important;
            padding: 16px !important;
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
