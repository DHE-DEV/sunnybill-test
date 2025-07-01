<div class="space-y-4">
    {{-- Document Info Header --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ $document->name }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $document->original_name }} • 
                    @switch($document->mime_type)
                        @case('application/pdf')
                            PDF-Dokument
                            @break
                        @case('image/jpeg')
                        @case('image/png')
                            Bild
                            @break
                        @default
                            {{ strtoupper(explode('/', $document->mime_type)[1] ?? 'Unbekannt') }}
                    @endswitch
                    • {{ number_format($document->size / 1024, 1) }} KB
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('documents.download', $document) }}" 
                   class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                    Herunterladen
                </a>
                <a href="{{ $fileUrl }}" 
                   target="_blank"
                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 mr-2" />
                    In neuem Tab öffnen
                </a>
            </div>
        </div>
    </div>

    {{-- Document Preview --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-900">
        @if($document->mime_type === 'application/pdf')
            {{-- PDF Preview --}}
            <div class="w-full" style="height: 70vh;">
                <iframe 
                    src="{{ $fileUrl }}#toolbar=1&navpanes=1&scrollbar=1&page=1&view=FitH" 
                    class="w-full h-full border-0"
                    title="PDF Vorschau: {{ $document->name }}"
                    loading="lazy">
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <x-heroicon-o-document-text class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                            <p class="text-gray-500 dark:text-gray-400 mb-4">
                                PDF kann nicht angezeigt werden.
                            </p>
                            <a href="{{ $fileUrl }}" 
                               target="_blank"
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4 mr-2" />
                                In neuem Tab öffnen
                            </a>
                        </div>
                    </div>
                </iframe>
            </div>
        @elseif(in_array($document->mime_type, ['image/jpeg', 'image/png']))
            {{-- Image Preview --}}
            <div class="flex items-center justify-center p-4" style="max-height: 70vh;">
                <img 
                    src="{{ $fileUrl }}" 
                    alt="{{ $document->name }}"
                    class="max-w-full max-h-full object-contain rounded-lg shadow-lg"
                    loading="lazy"
                />
            </div>
        @else
            {{-- Unsupported File Type --}}
            <div class="flex items-center justify-center h-64">
                <div class="text-center">
                    <x-heroicon-o-document class="w-16 h-16 mx-auto text-gray-400 mb-4" />
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Vorschau für diesen Dateityp nicht verfügbar.
                    </p>
                    <a href="{{ route('documents.download', $document) }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                        Datei herunterladen
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Document Description --}}
    @if($document->description)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Beschreibung</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $document->description }}</p>
        </div>
    @endif
</div>