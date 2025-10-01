<div>
    @if($showPopup && $currentNews)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>

                <!-- Center the modal -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-blue-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                <!-- Icon -->
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                    {{ $currentNews['title'] }}
                                </h3>

                                @if($currentNews['published_at'])
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ \Carbon\Carbon::parse($currentNews['published_at'])->format('d.m.Y H:i') }} Uhr
                                    </p>
                                @endif

                                @if($currentNews['image_path'])
                                    <div class="mt-4">
                                        <img src="{{ Storage::url($currentNews['image_path']) }}"
                                             alt="{{ $currentNews['title'] }}"
                                             class="w-full rounded-lg shadow-sm max-h-80 object-cover">
                                    </div>
                                @endif

                                <div class="mt-4 prose prose-sm max-w-none">
                                    {!! $currentNews['content'] !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer with action buttons -->
                    <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        @if($currentIndex < count($unviewedNews) - 1)
                            <button wire:click="nextNews"
                                    type="button"
                                    class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Weiter zur nächsten Neuigkeit
                            </button>
                        @else
                            <button wire:click="closePopup"
                                    type="button"
                                    class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Schließen
                            </button>
                        @endif

                        <button wire:click="dontShowAgain"
                                type="button"
                                class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Nicht mehr anzeigen
                        </button>
                    </div>

                    <!-- Progress indicator -->
                    @if(count($unviewedNews) > 1)
                        <div class="px-4 py-2 bg-gray-100 border-t border-gray-200">
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-xs text-gray-600">
                                    Neuigkeit {{ $currentIndex + 1 }} von {{ count($unviewedNews) }}
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
