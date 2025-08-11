<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Zeitpunkt</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->created_at->format('d.m.Y H:i:s') }}</p>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Benutzer</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->user?->name ?? 'System' }}</p>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Aktion</h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                @switch($record->action)
                    @case('created')
                        bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @break
                    @case('field_changed')
                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @break
                    @case('note_added')
                        bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200
                        @break
                    @case('note_updated')
                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @break
                    @case('note_deleted')
                        bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @break
                    @case('deleted')
                        bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                        @break
                    @default
                        bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                @endswitch
            ">
                @switch($record->action)
                    @case('created') Erstellt @break
                    @case('field_changed') Feld geändert @break
                    @case('note_added') Notiz hinzugefügt @break
                    @case('note_updated') Notiz bearbeitet @break
                    @case('note_deleted') Notiz gelöscht @break
                    @case('deleted') Gelöscht @break
                    @default {{ $record->action }}
                @endswitch
            </span>
        </div>

        @if($record->field_name && $record->action === 'field_changed')
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Geändertes Feld</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->field_name }}</p>
        </div>
        @endif
    </div>

    @if($record->action === 'field_changed' && ($record->old_value || $record->new_value))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Alter Wert</h3>
            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                @if($record->old_value)
                    <div class="text-sm text-gray-700 dark:text-gray-300 prose prose-sm max-w-none">
                        {!! html_entity_decode($record->old_value, ENT_QUOTES | ENT_HTML5, 'UTF-8') !!}
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">Leer</p>
                @endif
            </div>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Neuer Wert</h3>
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                @if($record->new_value)
                    <div class="text-sm text-gray-700 dark:text-gray-300 prose prose-sm max-w-none">
                        {!! html_entity_decode($record->new_value, ENT_QUOTES | ENT_HTML5, 'UTF-8') !!}
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">Leer</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($record->description)
    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Beschreibung</h3>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="text-sm text-gray-700 dark:text-gray-300 prose prose-sm max-w-none">
                {!! html_entity_decode($record->description, ENT_QUOTES | ENT_HTML5, 'UTF-8') !!}
            </div>
        </div>
    </div>
    @endif

    @if($record->meta_data)
    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Zusätzliche Informationen</h3>
        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ json_encode($record->meta_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
    @endif
</div>
