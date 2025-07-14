<div class="space-y-6">
    @php
        $statistics = $this->getStatistics();
    @endphp

    <!-- Übersicht Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Gesamt Aufgaben</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $statistics['overview']['total_tasks'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Abgeschlossen</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $statistics['overview']['completed_tasks'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Überfällig</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $statistics['overview']['overdue_tasks'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Meine Aufgaben</p>
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $statistics['overview']['my_tasks'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Verteilung -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Status Verteilung</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($statistics['status_distribution'] as $status => $data)
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $data['count'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $data['label'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500">{{ $data['percentage'] }}%</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Prioritäts Verteilung -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Prioritäts Verteilung</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($statistics['priority_distribution'] as $priority => $data)
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $data['count'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ $data['label'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500">{{ $data['percentage'] }}%</div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Produktivitäts Statistiken -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Meine Produktivität</h3>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Heute abgeschlossen:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $statistics['productivity']['completed_today'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Diese Woche:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $statistics['productivity']['completed_this_week'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Letzte 30 Tage:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $statistics['productivity']['completed_last_30_days'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Durchschnittliche Bearbeitungszeit:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $statistics['productivity']['avg_completion_time'] }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Zeiterfassung</h3>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Geschätzte Stunden (gesamt):</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $statistics['time_tracking']['total_estimated_hours'] }}h</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Tatsächliche Stunden (gesamt):</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $statistics['time_tracking']['total_actual_hours'] }}h</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Schätzungsgenauigkeit:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $statistics['time_tracking']['estimation_accuracy'] }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Aufgabentypen -->
    @if(!empty($statistics['task_types']))
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Aufgabentypen</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($statistics['task_types'] as $taskType)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $taskType['name'] }}</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $taskType['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>