<x-filament-widgets::widget>
    <x-filament::section
        :heading="$this->getHeading()"
        :icon="static::$icon"
        collapsible
        collapsed
    >
        <div class="p-0">
            @if($this->getOwnerRecord())
                @livewire($this->getRelationManagerClass(), [
                    'ownerRecord' => $this->getOwnerRecord(),
                    'pageClass' => \App\Filament\Resources\CustomerResource\Pages\ViewCustomer::class,
                ])
            @else
                <div class="text-center text-gray-500 py-8">
                    Kunde nicht gefunden
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>