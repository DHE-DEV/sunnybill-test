<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Erweiterte Business-Statistiken -->
        @if($showStatistics)
            @php
                $stats = $this->getStatistics();
            @endphp
            
            <div class="space-y-6">
                <!-- Hauptstatistiken -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
                    <!-- Empfangene Benachrichtigungen -->
                    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="grid gap-y-2">
                            <div class="flex items-center gap-x-2">
                                <div class="flex items-center gap-x-1">
                                    <div class="fi-wi-stats-overview-stat-icon flex h-5 w-5 items-center justify-center rounded-md bg-blue-500/10">
                                        <x-heroicon-o-inbox-arrow-down class="h-3 w-3 text-blue-500" />
                                    </div>
                                    <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Empfangen
                                    </span>
                                </div>
                            </div>
                            <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                                {{ $stats['received']['total'] }}
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Ungelesen:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-700/10">
                                        {{ $stats['received']['unread'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Gelesen:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-700/10">
                                        {{ $stats['received']['read'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Lesequote:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                        {{ $stats['overview']['read_rate'] }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gesendete Benachrichtigungen -->
                    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="grid gap-y-2">
                            <div class="flex items-center gap-x-2">
                                <div class="flex items-center gap-x-1">
                                    <div class="fi-wi-stats-overview-stat-icon flex h-5 w-5 items-center justify-center rounded-md bg-green-500/10">
                                        <x-heroicon-o-paper-airplane class="h-3 w-3 text-green-500" />
                                    </div>
                                    <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Gesendet
                                    </span>
                                </div>
                            </div>
                            <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                                {{ $stats['sent']['total'] }}
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Heute:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-700/10">
                                        {{ $stats['sent']['today'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Diese Woche:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-700/10">
                                        {{ $stats['sent']['this_week'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Lesequote:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                        {{ $stats['sent']['read_rate'] ?? '0' }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prioritäten -->
                    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="grid gap-y-2">
                            <div class="flex items-center gap-x-2">
                                <div class="flex items-center gap-x-1">
                                    <div class="fi-wi-stats-overview-stat-icon flex h-5 w-5 items-center justify-center rounded-md bg-red-500/10">
                                        <x-heroicon-o-exclamation-triangle class="h-3 w-3 text-red-500" />
                                    </div>
                                    <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Prioritäten
                                    </span>
                                </div>
                            </div>
                            <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                                {{ $stats['received']['urgent'] + $stats['received']['high'] }}
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Dringend:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-700/10">
                                        {{ $stats['received']['urgent'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Hoch:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-700/10">
                                        {{ $stats['received']['high'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Ø Reaktionszeit:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                        {{ $stats['overview']['avg_response_time'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Zeitanalyse -->
                    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="grid gap-y-2">
                            <div class="flex items-center gap-x-2">
                                <div class="flex items-center gap-x-1">
                                    <div class="fi-wi-stats-overview-stat-icon flex h-5 w-5 items-center justify-center rounded-md bg-purple-500/10">
                                        <x-heroicon-o-clock class="h-3 w-3 text-purple-500" />
                                    </div>
                                    <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Zeitanalyse
                                    </span>
                                </div>
                            </div>
                            <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                                {{ $stats['received']['today'] }}
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Heute:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">
                                        {{ $stats['received']['today'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Diese Woche:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">
                                        {{ $stats['received']['this_week'] }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-500">Aktivster Tag:</span>
                                    <span class="inline-flex items-center gap-x-1 rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">
                                        {{ $stats['overview']['most_active_day'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team vs. Personal Statistiken -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Empfänger-Verteilung -->
                    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="grid gap-y-4">
                            <div class="flex items-center gap-x-2">
                                <div class="flex items-center gap-x-1">
                                    <div class="fi-wi-stats-overview-stat-icon flex h-5 w-5 items-center justify-center rounded-md bg-indigo-500/10">
                                        <x-heroicon-o-user-group class="h-3 w-3 text-indigo-500" />
                                    </div>
                                    <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Empfänger-Verteilung (Empfangen)
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                    <div class="flex items-center gap-x-3">
                                        <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Persönlich</span>
                                    </div>
                                    <span class="text-lg font-semibold text-gray-950 dark:text-white">{{ $stats['received']['personal_notifications'] }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                    <div class="flex items-center gap-x-3">
                                        <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Team</span>
                                    </div>
                                    <span class="text-lg font-semibold text-gray-950 dark:text-white">{{ $stats['received']['team_notifications'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prioritäts-Verteilung -->
                    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="grid gap-y-4">
                            <div class="flex items-center gap-x-2">
                                <div class="flex items-center gap-x-1">
                                    <div class="fi-wi-stats-overview-stat-icon flex h-5 w-5 items-center justify-center rounded-md bg-indigo-500/10">
                                        <x-heroicon-o-chart-pie class="h-3 w-3 text-indigo-500" />
                                    </div>
                                    <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Prioritäts-Verteilung
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-3">
                                @foreach($stats['overview']['priority_distribution'] as $priority => $data)
                                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                        <div class="flex items-center gap-x-3">
                                            <div class="h-2 w-2 rounded-full {{
                                                $priority === 'urgent' ? 'bg-red-500' :
                                                ($priority === 'high' ? 'bg-orange-500' :
                                                ($priority === 'normal' ? 'bg-blue-500' : 'bg-gray-500'))
                                            }}"></div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{
                                                $priority === 'urgent' ? 'Dringend' :
                                                ($priority === 'high' ? 'Hoch' :
                                                ($priority === 'normal' ? 'Normal' : 'Niedrig'))
                                            }}</span>
                                        </div>
                                        <div class="flex items-center gap-x-2">
                                            <span class="text-lg font-semibold text-gray-950 dark:text-white">{{ $data['count'] }}</span>
                                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-700 dark:text-gray-400 dark:ring-gray-400/20">
                                                {{ $data['percentage'] }}%
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zusätzliche Insights -->
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="grid gap-y-6">
                        <div class="flex items-center gap-x-2">
                            <div class="flex items-center gap-x-1">
                                <div class="fi-wi-stats-overview-stat-icon flex h-5 w-5 items-center justify-center rounded-md bg-yellow-500/10">
                                    <x-heroicon-o-light-bulb class="h-3 w-3 text-yellow-500" />
                                </div>
                                <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                                    Kommunikations-Insights
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 p-6 shadow-sm ring-1 ring-blue-200/50 dark:from-blue-900/20 dark:to-blue-800/20 dark:ring-blue-700/50">
                                <div class="grid gap-y-2 text-center">
                                    <div class="text-sm font-medium text-blue-900 dark:text-blue-100">Gesendete Team-Nachrichten</div>
                                    <div class="text-3xl font-semibold tracking-tight text-blue-700 dark:text-blue-200">{{ $stats['sent']['team_notifications'] }}</div>
                                    <div class="inline-flex items-center justify-center rounded-full bg-blue-200 px-3 py-1 text-xs font-medium text-blue-600 dark:bg-blue-800 dark:text-blue-300">
                                        von {{ $stats['sent']['total'] }} gesamt
                                    </div>
                                </div>
                            </div>
                            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-gradient-to-br from-green-50 to-green-100 p-6 shadow-sm ring-1 ring-green-200/50 dark:from-green-900/20 dark:to-green-800/20 dark:ring-green-700/50">
                                <div class="grid gap-y-2 text-center">
                                    <div class="text-sm font-medium text-green-900 dark:text-green-100">Gesendete Personal-Nachrichten</div>
                                    <div class="text-3xl font-semibold tracking-tight text-green-700 dark:text-green-200">{{ $stats['sent']['personal_notifications'] }}</div>
                                    <div class="inline-flex items-center justify-center rounded-full bg-green-200 px-3 py-1 text-xs font-medium text-green-600 dark:bg-green-800 dark:text-green-300">
                                        von {{ $stats['sent']['total'] }} gesamt
                                    </div>
                                </div>
                            </div>
                            <div class="fi-wi-stats-overview-stat relative rounded-xl bg-gradient-to-br from-purple-50 to-purple-100 p-6 shadow-sm ring-1 ring-purple-200/50 dark:from-purple-900/20 dark:to-purple-800/20 dark:ring-purple-700/50">
                                <div class="grid gap-y-2 text-center">
                                    <div class="text-sm font-medium text-purple-900 dark:text-purple-100">Durchschnittliche Lesequote</div>
                                    <div class="text-3xl font-semibold tracking-tight text-purple-700 dark:text-purple-200">{{ $stats['overview']['read_rate'] }}%</div>
                                    <div class="inline-flex items-center justify-center rounded-full bg-purple-200 px-3 py-1 text-xs font-medium text-purple-600 dark:bg-purple-800 dark:text-purple-300">
                                        Ihrer empfangenen Nachrichten
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Benachrichtigungstabelle -->
        {{ $this->table }}
    </div>

    @push('scripts')
    <script>
        // Auto-refresh für Benachrichtigungen
        document.addEventListener('livewire:init', () => {
            Livewire.on('refresh-notifications', () => {
                // Aktualisiere die Seite nach einer kurzen Verzögerung
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            });
        });

        // Markiere Benachrichtigung als gelesen beim Klick auf Zeile
        document.addEventListener('click', function(e) {
            const row = e.target.closest('[data-notification-id]');
            if (row && !e.target.closest('button') && !e.target.closest('a')) {
                const notificationId = row.dataset.notificationId;
                const isRead = row.dataset.isRead === '1';
                
                if (!isRead) {
                    // Markiere als gelesen
                    Livewire.dispatch('markNotificationAsRead', { id: notificationId });
                }
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
