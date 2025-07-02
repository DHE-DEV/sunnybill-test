<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Hauptinformationen (Infolist) --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            {{ $this->infolist }}
        </div>

        {{-- Accordion-Abschnitte für RelationManagers --}}
        <div class="space-y-4" x-data="{ 
            openSections: {},
            toggleSection(section) {
                this.openSections[section] = !this.openSections[section];
            },
            isOpen(section) {
                return this.openSections[section] || false;
            }
        }">
            {{-- Adressen --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button 
                    @click="toggleSection('addresses')"
                    class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-map-pin class="w-5 h-5 text-gray-500" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Adressen</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-full">
                            {{ $record->addresses()->count() }}
                        </span>
                    </div>
                    <x-heroicon-o-chevron-down 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        ::class="{ 'rotate-180': isOpen('addresses') }"
                    />
                </button>
                <div 
                    x-show="isOpen('addresses')"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="border-t border-gray-200 dark:border-gray-700"
                    style="display: none;"
                >
                    <div class="p-6">
                        @livewire(\App\Filament\Resources\CustomerResource\RelationManagers\AddressesRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>

            {{-- Telefonnummern --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button 
                    @click="toggleSection('phones')"
                    class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-phone class="w-5 h-5 text-gray-500" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Telefonnummern</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-full">
                            {{ $record->phoneNumbers()->count() }}
                        </span>
                    </div>
                    <x-heroicon-o-chevron-down 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        ::class="{ 'rotate-180': isOpen('phones') }"
                    />
                </button>
                <div 
                    x-show="isOpen('phones')"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="border-t border-gray-200 dark:border-gray-700"
                    style="display: none;"
                >
                    <div class="p-6">
                        @livewire(\App\Filament\Resources\CustomerResource\RelationManagers\PhoneNumbersRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>

            {{-- Mitarbeiter --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button 
                    @click="toggleSection('employees')"
                    class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-users class="w-5 h-5 text-gray-500" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Mitarbeiter</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-full">
                            {{ $record->employees()->count() }}
                        </span>
                    </div>
                    <x-heroicon-o-chevron-down 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        ::class="{ 'rotate-180': isOpen('employees') }"
                    />
                </button>
                <div 
                    x-show="isOpen('employees')"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="border-t border-gray-200 dark:border-gray-700"
                    style="display: none;"
                >
                    <div class="p-6">
                        @livewire(\App\Filament\Resources\CustomerResource\RelationManagers\EmployeesRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>

            {{-- Dokumente --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button 
                    @click="toggleSection('documents')"
                    class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-document-text class="w-5 h-5 text-gray-500" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Dokumente</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-full">
                            {{ \App\Models\Document::where('documentable_type', \App\Models\Customer::class)->where('documentable_id', $record->id)->count() }}
                        </span>
                    </div>
                    <x-heroicon-o-chevron-down 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        ::class="{ 'rotate-180': isOpen('documents') }"
                    />
                </button>
                <div 
                    x-show="isOpen('documents')"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="border-t border-gray-200 dark:border-gray-700"
                    style="display: none;"
                >
                    <div class="p-6">
                        @livewire(\App\Filament\Widgets\CustomerDocumentsTableWidget::class, [
                            'customerId' => $record->id,
                        ])
                    </div>
                </div>
            </div>

            {{-- Rechnungen --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button 
                    @click="toggleSection('invoices')"
                    class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-document-currency-euro class="w-5 h-5 text-gray-500" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Rechnungen</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-full">
                            {{ $record->invoices()->count() }}
                        </span>
                    </div>
                    <x-heroicon-o-chevron-down 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        ::class="{ 'rotate-180': isOpen('invoices') }"
                    />
                </button>
                <div 
                    x-show="isOpen('invoices')"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="border-t border-gray-200 dark:border-gray-700"
                    style="display: none;"
                >
                    <div class="p-6">
                        @livewire(\App\Filament\Resources\CustomerResource\RelationManagers\InvoicesRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>

            {{-- Solar-Beteiligungen --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button 
                    @click="toggleSection('solar')"
                    class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-sun class="w-5 h-5 text-gray-500" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Solar-Beteiligungen</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-full">
                            {{ $record->solarParticipations()->count() }}
                        </span>
                    </div>
                    <x-heroicon-o-chevron-down 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        ::class="{ 'rotate-180': isOpen('solar') }"
                    />
                </button>
                <div 
                    x-show="isOpen('solar')"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="border-t border-gray-200 dark:border-gray-700"
                    style="display: none;"
                >
                    <div class="p-6">
                        @livewire(\App\Filament\Resources\CustomerResource\RelationManagers\SolarParticipationsRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>

            {{-- Monatliche Gutschriften --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button 
                    @click="toggleSection('credits')"
                    class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-banknotes class="w-5 h-5 text-gray-500" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Monatliche Gutschriften</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-full">
                            {{ $record->monthlyCredits()->count() }}
                        </span>
                    </div>
                    <x-heroicon-o-chevron-down 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        ::class="{ 'rotate-180': isOpen('credits') }"
                    />
                </button>
                <div 
                    x-show="isOpen('credits')"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="border-t border-gray-200 dark:border-gray-700"
                    style="display: none;"
                >
                    <div class="p-6">
                        @livewire(\App\Filament\Resources\CustomerResource\RelationManagers\MonthlyCreditsRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>

            {{-- Favoriten-Notizen --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button 
                    @click="toggleSection('favorite-notes')"
                    class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-star class="w-5 h-5 text-gray-500" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Favoriten-Notizen</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-full">
                            {{ $record->favoriteNotes()->count() }}
                        </span>
                    </div>
                    <x-heroicon-o-chevron-down 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        ::class="{ 'rotate-180': isOpen('favorite-notes') }"
                    />
                </button>
                <div 
                    x-show="isOpen('favorite-notes')"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="border-t border-gray-200 dark:border-gray-700"
                    style="display: none;"
                >
                    <div class="p-6">
                        @livewire(\App\Filament\Resources\CustomerResource\RelationManagers\FavoriteNotesRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>

            {{-- Standard-Notizen --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <button 
                    @click="toggleSection('standard-notes')"
                    class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                >
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-document-text class="w-5 h-5 text-gray-500" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Standard-Notizen</h3>
                        <span class="text-sm text-gray-500 bg-gray-100 dark:bg-gray-600 px-2 py-1 rounded-full">
                            {{ $record->standardNotes()->count() }}
                        </span>
                    </div>
                    <x-heroicon-o-chevron-down 
                        class="w-5 h-5 text-gray-500 transition-transform duration-200"
                        ::class="{ 'rotate-180': isOpen('standard-notes') }"
                    />
                </button>
                <div 
                    x-show="isOpen('standard-notes')"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-95"
                    class="border-t border-gray-200 dark:border-gray-700"
                    style="display: none;"
                >
                    <div class="p-6">
                        @livewire(\App\Filament\Resources\CustomerResource\RelationManagers\StandardNotesRelationManager::class, [
                            'ownerRecord' => $record,
                            'pageClass' => static::class,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>