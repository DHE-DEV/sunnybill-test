@props(['documents'])

<div class="p-4">
    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
        Zugehörige Dokumente
    </h2>

    @if(empty($documents))
        <p class="text-gray-500 dark:text-gray-400">Keine Dokumente für diese Position gefunden.</p>
    @else
        <ul class="space-y-3">
            @foreach($documents as $doc)
                <li class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-document-text class="h-6 w-6 text-gray-400 mr-3"/>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ $doc['original_filename'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ (new \Carbon\Carbon($doc['created_at']))->format('d.m.Y H:i') }} - {{ \Illuminate\Support\Number::fileSize($doc['size_in_bytes']) }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('documents.download', $doc['id']) }}"
                       class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4 mr-1"/>
                        Download
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
