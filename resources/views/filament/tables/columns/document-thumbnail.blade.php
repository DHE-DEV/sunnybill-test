@php
    $record = $getRecord();
    $fileUrl = route('documents.view', $record);
@endphp

<div class="flex items-center justify-center w-16 h-16 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50 dark:bg-gray-800">
    @switch($record->mime_type)
        @case('application/pdf')
            {{-- PDF Thumbnail --}}
            <div class="relative w-full h-full group cursor-pointer" 
                 onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: { action: 'preview', record: {{ $record->id }} } }))">
                <div class="flex items-center justify-center w-full h-full bg-red-50 dark:bg-red-900/20 group-hover:bg-red-100 dark:group-hover:bg-red-900/30 transition-colors">
                    <x-heroicon-o-document-text class="w-8 h-8 text-red-600 dark:text-red-400" />
                </div>
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/20 rounded">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4 text-white" />
                </div>
            </div>
            @break
            
        @case('image/jpeg')
        @case('image/png')
            {{-- Image Thumbnail --}}
            <div class="relative w-full h-full group cursor-pointer"
                 onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: { action: 'preview', record: {{ $record->id }} } }))">
                <img 
                    src="{{ $fileUrl }}" 
                    alt="{{ $record->name }}"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                    loading="lazy"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                />
                <div class="hidden items-center justify-center w-full h-full bg-green-50 dark:bg-green-900/20">
                    <x-heroicon-o-photo class="w-8 h-8 text-green-600 dark:text-green-400" />
                </div>
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/20 rounded">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4 text-white" />
                </div>
            </div>
            @break
            
        @case('application/msword')
        @case('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            {{-- Word Document --}}
            <div class="flex items-center justify-center w-full h-full bg-blue-50 dark:bg-blue-900/20">
                <x-heroicon-o-document class="w-8 h-8 text-blue-600 dark:text-blue-400" />
            </div>
            @break
            
        @default
            {{-- Generic File --}}
            <div class="flex items-center justify-center w-full h-full bg-gray-100 dark:bg-gray-700">
                <x-heroicon-o-document class="w-8 h-8 text-gray-500 dark:text-gray-400" />
            </div>
    @endswitch
</div>

{{-- File Type Badge --}}
<div class="mt-1 text-center">
    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
        @switch($record->mime_type)
            @case('application/pdf')
                bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400
                @break
            @case('image/jpeg')
            @case('image/png')
                bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400
                @break
            @case('application/msword')
            @case('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400
                @break
            @default
                bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
        @endswitch
    ">
        @switch($record->mime_type)
            @case('application/pdf')
                PDF
                @break
            @case('image/jpeg')
                JPEG
                @break
            @case('image/png')
                PNG
                @break
            @case('application/msword')
                DOC
                @break
            @case('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                DOCX
                @break
            @default
                {{ strtoupper(explode('/', $record->mime_type)[1] ?? 'FILE') }}
        @endswitch
    </span>
</div>