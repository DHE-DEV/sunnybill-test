<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <x-dynamic-component 
                    :component="'heroicon-o-' . str_replace('heroicon-o-', '', $notification->icon)" 
                    class="h-8 w-8 {{ $notification->getColorClass() }}" 
                />
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">{{ $notification->title }}</h3>
                <div class="flex items-center space-x-4 mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $notification->getPriorityBadgeClass() }}">
                        {{ $notification->getPriorityText() }}
                    </span>
                    <span class="text-sm text-gray-500">
                        {{ $notification->created_at->format('d.m.Y H:i') }}
                    </span>
                    @if($notification->is_read)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <x-heroicon-o-eye class="w-3 h-3 mr-1" />
                            Gelesen
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            <x-heroicon-o-eye-slash class="w-3 h-3 mr-1" />
                            Ungelesen
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Nachrichteninhalt -->
    <div class="bg-gray-50 rounded-lg p-4">
        <p class="text-gray-900 whitespace-pre-wrap">{{ $notification->message }}</p>
    </div>

    <!-- Zusätzliche Daten -->
    @if($notification->data && count($notification->data) > 0)
        <div class="border-t pt-6">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Zusätzliche Informationen</h4>
            <div class="bg-gray-50 rounded-lg p-4">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
                    @foreach($notification->data as $key => $value)
                        @if(!in_array($key, ['url']) && !is_null($value))
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                                <dd class="text-sm text-gray-900">
                                    @if(is_array($value))
                                        {{ json_encode($value, JSON_PRETTY_PRINT) }}
                                    @elseif(is_bool($value))
                                        {{ $value ? 'Ja' : 'Nein' }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        </div>
    @endif

    <!-- Aktionen -->
    @if($notification->action_url || $notification->action_text)
        <div class="border-t pt-6">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-medium text-gray-900">Aktionen</h4>
                @if($notification->action_url)
                    <a 
                        href="{{ $notification->action_url }}" 
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        {{ $notification->action_text ?: 'Öffnen' }}
                        <x-heroicon-o-arrow-top-right-on-square class="ml-2 -mr-1 h-4 w-4" />
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- Typ-spezifische Informationen -->
    @if($notification->type === 'gmail_email')
        <div class="border-t pt-6">
            <h4 class="text-sm font-medium text-gray-900 mb-3">Gmail-Informationen</h4>
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center">
                    <x-heroicon-o-envelope class="h-5 w-5 text-blue-400" />
                    <span class="ml-2 text-sm text-blue-800">
                        Diese Benachrichtigung wurde durch eine neue Gmail-E-Mail ausgelöst.
                    </span>
                </div>
                @if(isset($notification->data['sender']))
                    <div class="mt-2">
                        <span class="text-sm font-medium text-blue-800">Von:</span>
                        <span class="text-sm text-blue-700">{{ $notification->data['sender'] }}</span>
                    </div>
                @endif
                @if(isset($notification->data['subject']))
                    <div class="mt-1">
                        <span class="text-sm font-medium text-blue-800">Betreff:</span>
                        <span class="text-sm text-blue-700">{{ $notification->data['subject'] }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Zeitstempel -->
    <div class="border-t pt-6">
        <div class="text-xs text-gray-500 space-y-1">
            <div>Erstellt: {{ $notification->created_at->format('d.m.Y H:i:s') }}</div>
            @if($notification->read_at)
                <div>Gelesen: {{ $notification->read_at->format('d.m.Y H:i:s') }}</div>
            @endif
            @if($notification->expires_at)
                <div>Läuft ab: {{ $notification->expires_at->format('d.m.Y H:i:s') }}</div>
            @endif
        </div>
    </div>
</div>
