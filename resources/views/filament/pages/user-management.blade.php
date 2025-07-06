<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Widgets --}}
        <div class="grid grid-cols-1 gap-6">
            @foreach ($this->getHeaderWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Schnellaktionen</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ \App\Filament\Resources\UserResource::getUrl('create') }}" 
                   class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-plus class="h-8 w-8 text-blue-600" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-900">Neuen Benutzer erstellen</p>
                        <p class="text-sm text-blue-700">Benutzer hinzufügen</p>
                    </div>
                </a>

                <a href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}?tableFilters[is_active][value]=0" 
                   class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-exclamation-triangle class="h-8 w-8 text-yellow-600" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-yellow-900">Inaktive Benutzer</p>
                        <p class="text-sm text-yellow-700">Deaktivierte anzeigen</p>
                    </div>
                </a>

                <a href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}?tableFilters[email_verified_at][value]=0" 
                   class="flex items-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-shield-exclamation class="h-8 w-8 text-red-600" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-900">Nicht verifiziert</p>
                        <p class="text-sm text-red-700">E-Mail nicht bestätigt</p>
                    </div>
                </a>

                <a href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}?tableFilters[role][value]=admin" 
                   class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-shield-check class="h-8 w-8 text-purple-600" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-purple-900">Administratoren</p>
                        <p class="text-sm text-purple-700">Admin-Benutzer verwalten</p>
                    </div>
                </a>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Kürzliche Aktivitäten</h3>
            <div class="space-y-3">
                @php
                    $recentUsers = \App\Models\User::latest('created_at')->take(5)->get();
                    $recentLogins = \App\Models\User::whereNotNull('last_login_at')
                        ->latest('last_login_at')->take(5)->get();
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Neueste Benutzer</h4>
                        <div class="space-y-2">
                            @forelse($recentUsers as $user)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 bg-blue-500 rounded-full flex items-center justify-center">
                                                <span class="text-xs font-medium text-white">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</p>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                            {{ $user->role === 'admin' ? 'bg-red-100 text-red-800' : 
                                               ($user->role === 'manager' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                            {{ \App\Models\User::getRoles()[$user->role] ?? $user->role }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Keine neuen Benutzer</p>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Letzte Anmeldungen</h4>
                        <div class="space-y-2">
                            @forelse($recentLogins as $user)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 bg-green-500 rounded-full flex items-center justify-center">
                                                <span class="text-xs font-medium text-white">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">{{ $user->last_login_at->diffForHumans() }}</p>
                                        <div class="flex items-center">
                                            <x-heroicon-s-check-circle class="h-3 w-3 text-green-500 mr-1" />
                                            <span class="text-xs text-green-600">Online</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Keine Anmeldungen</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- System Information --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">System-Informationen</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-server class="h-8 w-8 text-blue-600" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-900">Benutzer-Datenbank</p>
                            <p class="text-xs text-blue-700">{{ \App\Models\User::count() }} Einträge</p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-shield-check class="h-8 w-8 text-green-600" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-900">Sicherheit</p>
                            <p class="text-xs text-green-700">Aktiv & überwacht</p>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-o-clock class="h-8 w-8 text-yellow-600" />
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-900">Letzte Aktualisierung</p>
                            <p class="text-xs text-yellow-700">{{ now()->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>