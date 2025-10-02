<div wire:poll.30s="checkForNewNews" x-data="{
    init() {
        this.$watch('$wire.showPopup', value => {
            if (value) {
                const handler = (e) => {
                    if (e.key === 'Escape') {
                        $wire.closePopup();
                    }
                };
                document.addEventListener('keydown', handler);
                this.cleanup = () => document.removeEventListener('keydown', handler);
            } else if (this.cleanup) {
                this.cleanup();
            }
        });
    }
}">
    @if($showPopup && $currentNews)
        <div class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 999999; background-color: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px);">
            <div class="relative w-full max-w-2xl">
                <!-- Modal panel -->
                <div class="relative overflow-hidden text-left transition-all transform rounded-lg shadow-xl" style="background-color: #eff6ff;">
                    <!-- Close button (X) -->
                    <button wire:click="closePopup"
                            type="button"
                            class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full p-2 z-50"
                            style="background-color: #eff6ff;"
                            onmouseover="this.style.backgroundColor='#dbeafe'"
                            onmouseout="this.style.backgroundColor='#eff6ff'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div class="px-4 pb-4 sm:px-6 sm:pb-4" style="background-color: #eff6ff; padding-top: 35px;">
                        <div>
                            <div class="text-left rounded-lg" style="background-color: white; padding: 10px;">
                                <h3 class="text-base font-medium leading-6 text-gray-900" id="modal-title">
                                    {{ $currentNews['title'] }}
                                </h3>

                                @if($currentNews['published_at'])
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ \Carbon\Carbon::parse($currentNews['published_at'])->format('d.m.Y H:i') }} Uhr
                                    </p>
                                @endif

                                <div class="mt-4 prose prose-sm max-w-none">
                                    {!! $currentNews['content'] !!}
                                </div>

                                @if($currentNews['image_path'])
                                    <div class="mt-4">
                                        <a href="{{ Storage::url($currentNews['image_path']) }}" target="_blank" rel="noopener noreferrer">
                                            <img src="{{ Storage::url($currentNews['image_path']) }}"
                                                 alt="{{ $currentNews['title'] }}"
                                                 class="rounded-lg shadow-sm max-h-30 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                                                 style="max-height: 120px; width: auto;">
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Footer with action buttons -->
                    <div class="px-4 py-3 sm:px-6" style="background-color: #dbeafe;">
                        <div class="flex flex-row justify-between items-center gap-2">
                            <button wire:click="dontShowAgain"
                                    type="button"
                                    class="inline-flex justify-center px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm"
                                    onmouseover="this.style.backgroundColor='#eff6ff'"
                                    onmouseout="this.style.backgroundColor='white'">
                                Diese Nachricht nicht mehr anzeigen
                            </button>

                            @if($currentIndex < count($unviewedNews) - 1)
                                <button wire:click="nextNews"
                                        type="button"
                                        class="inline-flex justify-center px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm"
                                        onmouseover="this.style.backgroundColor='#eff6ff'"
                                        onmouseout="this.style.backgroundColor='white'">
                                    Weiter zur nächsten Nachricht
                                </button>
                            @else
                                <button wire:click="closePopup"
                                        type="button"
                                        class="inline-flex justify-center px-4 py-2 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm"
                                        onmouseover="this.style.backgroundColor='#eff6ff'"
                                        onmouseout="this.style.backgroundColor='white'">
                                    Schließen
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Progress indicator -->
                    @if(count($unviewedNews) > 1)
                        <div class="px-4 py-2 border-t" style="background-color: #bfdbfe; border-color: #93c5fd;">
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-xs text-gray-600">
                                    Nachricht {{ $currentIndex + 1 }} von {{ count($unviewedNews) }}
                                </span>
                                <div class="flex gap-1">
                                    @for($i = 0; $i < count($unviewedNews); $i++)
                                        <div class="w-2 h-2 rounded-full {{ $i === $currentIndex ? 'bg-blue-600' : 'bg-gray-300' }}"></div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    @endif
</div>
