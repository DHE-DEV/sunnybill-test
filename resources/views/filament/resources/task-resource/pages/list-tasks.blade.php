<x-filament-panels::page>
    @if($this->showStatistics)
        @php
            $statistics = $this->getStatistics();
        @endphp

        <div class="space-y-6">
            <!-- Übersicht -->
            <div class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <x-filament::section>
                        <x-slot name="heading">
                            Gesamt Aufgaben
                        </x-slot>
                        <div class="text-3xl font-bold text-primary-600">
                            {{ $statistics['overview']['total_tasks'] }}
                        </div>
                    </x-filament::section>
                </div>

                <div class="flex-1 min-w-64">
                    <x-filament::section>
                        <x-slot name="heading">
                            Meine Aufgaben
                        </x-slot>
                        <div class="text-3xl font-bold text-info-600">
                            {{ $statistics['overview']['my_tasks'] }}
                        </div>
                    </x-filament::section>
                </div>

                <div class="flex-1 min-w-64">
                    <x-filament::section>
                        <x-slot name="heading">
                            Überfällige Aufgaben
                        </x-slot>
                        <div class="text-3xl font-bold text-danger-600">
                            {{ $statistics['overview']['overdue_tasks'] }}
                        </div>
                    </x-filament::section>
                </div>

                <div class="flex-1 min-w-64">
                    <x-filament::section>
                        <x-slot name="heading">
                            Heute fällig
                        </x-slot>
                        <div class="text-3xl font-bold text-warning-600">
                            {{ $statistics['overview']['due_today'] }}
                        </div>
                    </x-filament::section>
                </div>
            </div>

            <!-- Meine Aufgaben Details -->
            <x-filament::section>
                <x-slot name="heading">
                    Meine Aufgaben Details
                </x-slot>
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-3xl font-bold text-primary-600">{{ $statistics['my_tasks']['total'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Gesamt</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-3xl font-bold text-gray-600">{{ $statistics['my_tasks']['open'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Offen</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-3xl font-bold text-primary-600">{{ $statistics['my_tasks']['in_progress'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">In Bearbeitung</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-3xl font-bold text-success-600">{{ $statistics['my_tasks']['completed'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Abgeschlossen</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-3xl font-bold text-danger-600">{{ $statistics['my_tasks']['overdue'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Überfällig</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-3xl font-bold text-info-600">{{ $statistics['my_tasks']['assigned_to_me'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Mir zugewiesen</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-3xl font-bold text-warning-600">{{ $statistics['my_tasks']['created_by_me'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Von mir erstellt</div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Produktivität -->
            <x-filament::section>
                <x-slot name="heading">
                    Produktivität
                </x-slot>
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-2xl font-bold text-success-600">{{ $statistics['productivity']['completed_today'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Heute abgeschlossen</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-2xl font-bold text-primary-600">{{ $statistics['productivity']['completed_this_week'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Diese Woche</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-2xl font-bold text-info-600">{{ $statistics['productivity']['completed_last_30_days'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Letzte 30 Tage</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-2xl font-bold text-warning-600">{{ $statistics['productivity']['completion_rate'] }}%</div>
                            <div class="text-sm text-gray-600 mt-1">Abschlussrate</div>
                        </div>
                    </div>
                    <div class="flex-1 min-w-48">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-2xl font-bold text-gray-600">{{ $statistics['productivity']['avg_completion_time'] }}</div>
                            <div class="text-sm text-gray-600 mt-1">Ø Bearbeitungszeit</div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Zeiterfassung -->
            <x-filament::section>
                <x-slot name="heading">
                    Zeiterfassung
                </x-slot>
                <div class="flex flex-wrap gap-4">
                    <div class="w-64">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-2xl font-bold text-primary-600">{{ $statistics['time_tracking']['total_estimated_hours'] }}h</div>
                            <div class="text-sm text-gray-600 mt-1">Geschätzte Zeit</div>
                        </div>
                    </div>
                    <div class="w-64">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-2xl font-bold text-success-600">{{ $statistics['time_tracking']['total_actual_hours'] }}h</div>
                            <div class="text-sm text-gray-600 mt-1">Tatsächliche Zeit</div>
                        </div>
                    </div>
                    <div class="w-64">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-2xl font-bold text-info-600">{{ $statistics['time_tracking']['avg_estimated_minutes'] }} min</div>
                            <div class="text-sm text-gray-600 mt-1">Ø Schätzung</div>
                        </div>
                    </div>
                    <div class="w-64">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-center">
                            <div class="text-2xl font-bold text-warning-600">{{ $statistics['time_tracking']['estimation_accuracy'] }}%</div>
                            <div class="text-sm text-gray-600 mt-1">Schätzgenauigkeit</div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Status und Priorität Verteilung -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Status Verteilung -->
                <x-filament::section>
                    <x-slot name="heading">
                        Status Verteilung
                    </x-slot>
                    <div class="space-y-3">
                        @foreach($statistics['status_distribution'] as $status => $data)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium">{{ $data['label'] }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600">{{ $data['count'] }}</span>
                                    <span class="text-xs text-gray-500">({{ $data['percentage'] }}%)</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-{{ match($status) {
                                    'open' => 'gray',
                                    'in_progress' => 'primary',
                                    'waiting_external' => 'warning',
                                    'waiting_internal' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'gray'
                                } }}-500 h-2 rounded-full" style="width: {{ $data['percentage'] }}%"></div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                <!-- Priorität Verteilung -->
                <x-filament::section>
                    <x-slot name="heading">
                        Priorität Verteilung
                    </x-slot>
                    <div class="space-y-3">
                        @foreach($statistics['priority_distribution'] as $priority => $data)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium">{{ $data['label'] }}</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600">{{ $data['count'] }}</span>
                                    <span class="text-xs text-gray-500">({{ $data['percentage'] }}%)</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-{{ match($priority) {
                                    'low' => 'gray',
                                    'medium' => 'primary',
                                    'high' => 'warning',
                                    'urgent' => 'danger',
                                    default => 'gray'
                                } }}-500 h-2 rounded-full" style="width: {{ $data['percentage'] }}%"></div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            <!-- Aufgabentypen und Team Statistiken -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Aufgabentypen -->
                <x-filament::section>
                    <x-slot name="heading">
                        Aufgabentypen
                    </x-slot>
                    <div class="space-y-2">
                        @foreach($statistics['task_types'] as $taskType)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium">{{ $taskType['name'] }}</span>
                                </div>
                                <span class="text-sm text-gray-600">{{ $taskType['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                <!-- Top Zugewiesene -->
                <x-filament::section>
                    <x-slot name="heading">
                        Top Zugewiesene Benutzer
                    </x-slot>
                    <div class="space-y-2">
                        @foreach($statistics['team_stats']['top_assignees'] as $assignee)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50">
                                <span class="text-sm font-medium">{{ $assignee['name'] }}</span>
                                <span class="text-sm text-gray-600">{{ $assignee['task_count'] }} Aufgaben</span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                <!-- Top Ersteller -->
                <x-filament::section>
                    <x-slot name="heading">
                        Top Aufgaben-Ersteller
                    </x-slot>
                    <div class="space-y-2">
                        @foreach($statistics['team_stats']['top_creators'] as $creator)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50">
                                <span class="text-sm font-medium">{{ $creator['name'] }}</span>
                                <span class="text-sm text-gray-600">{{ $creator['task_count'] }} Aufgaben</span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>
        </div>
    @else
        {{ $this->table }}
    @endif
</x-filament-panels::page>
