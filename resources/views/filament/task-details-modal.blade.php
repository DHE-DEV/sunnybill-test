<div class="space-y-6">
    <!-- Grundinformationen -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Grundinformationen</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-600">Aufgabennummer:</span>
                    <p class="text-sm text-gray-900 font-mono">{{ $record->task_number }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Titel:</span>
                    <p class="text-sm text-gray-900 font-semibold">{{ $record->title }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Typ:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                        {{ $record->taskType?->name ?? 'Nicht zugewiesen' }}
                    </span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Status:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                        @switch($record->status)
                            @case('open')
                                Offen
                                @break
                            @case('in_progress')
                                In Bearbeitung
                                @break
                            @case('waiting_external')
                                Warte auf Extern
                                @break
                            @case('waiting_internal')
                                Warte auf Intern
                                @break
                            @case('completed')
                                Abgeschlossen
                                @break
                            @case('cancelled')
                                Abgebrochen
                                @break
                            @default
                                {{ $record->status }}
                        @endswitch
                    </span>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Priorität:</span>
                    @if($record->priority === 'blocker')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #dc2626 !important; color: white !important;">
                            Blocker
                        </span>
                    @elseif($record->priority === 'urgent')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #fecaca !important; color: #991b1b !important;">
                            Dringend
                        </span>
                    @elseif($record->priority === 'high')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #fed7aa !important; color: #c2410c !important;">
                            Hoch
                        </span>
                    @elseif($record->priority === 'medium')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #fef3c7 !important; color: #a16207 !important;">
                            Mittel
                        </span>
                    @elseif($record->priority === 'low')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #dcfce7 !important; color: #166534 !important;">
                            Niedrig
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                            Keine Priorität
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Zuordnungen</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-600">Zugewiesen an:</span>
                    <p class="text-sm text-gray-900">{{ $record->assignedUser?->name ?? 'Nicht zugewiesen' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Inhaber:</span>
                    <p class="text-sm text-gray-900">{{ $record->owner?->name ?? 'Kein Inhaber' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Erstellt von:</span>
                    <p class="text-sm text-gray-900">{{ $record->creator?->name ?? 'Unbekannt' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Kunde:</span>
                    <p class="text-sm text-gray-900">{{ $record->customer?->company_name ?? 'Kein Kunde' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Lieferant:</span>
                    <p class="text-sm text-gray-900">{{ $record->supplier?->company_name ?? 'Kein Lieferant' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Solaranlage:</span>
                    <p class="text-sm text-gray-900">
                        @if($record->applies_to_all_solar_plants)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                🌟 Alle Solaranlagen
                            </span>
                        @else
                            {{ $record->solarPlant?->name ?? 'Keine Solaranlage' }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Beschreibung -->
    @if($record->description)
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Beschreibung</h3>
        <div class="prose prose-sm max-w-none">
            <p class="text-gray-900 whitespace-pre-wrap">{{ $record->description }}</p>
        </div>
    </div>
    @endif

    <!-- Termine und Zeiten -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Termine</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-600">Fälligkeitsdatum:</span>
                    <p class="text-sm text-gray-900 @if($record->is_overdue) text-red-600 font-semibold @elseif($record->is_due_today) text-orange-600 font-semibold @endif">
                        {{ $record->due_date ? $record->due_date->format('d.m.Y') : 'Nicht gesetzt' }}
                        @if($record->is_overdue)
                            <span class="text-red-600 ml-1">⚠️ Überfällig</span>
                        @elseif($record->is_due_today)
                            <span class="text-orange-600 ml-1">⏰ Heute fällig</span>
                        @endif
                    </p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Fälligkeitszeit:</span>
                    <p class="text-sm text-gray-900">{{ $record->due_time ? $record->due_time->format('H:i') : 'Nicht gesetzt' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Erstellt am:</span>
                    <p class="text-sm text-gray-900">{{ $record->created_at->format('d.m.Y H:i') }}</p>
                </div>
                @if($record->completed_at)
                <div>
                    <span class="text-sm font-medium text-gray-600">Abgeschlossen am:</span>
                    <p class="text-sm text-gray-900">{{ $record->completed_at->format('d.m.Y H:i') }}</p>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Zeitaufwand</h3>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-600">Geschätzte Zeit:</span>
                    <p class="text-sm text-gray-900">{{ $record->estimated_minutes ? $record->estimated_minutes . ' Minuten' : 'Nicht geschätzt' }}</p>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-600">Tatsächliche Zeit:</span>
                    <p class="text-sm text-gray-900">{{ $record->actual_minutes ? $record->actual_minutes . ' Minuten' : 'Noch nicht erfasst' }}</p>
                </div>
                @if($record->estimated_minutes && $record->actual_minutes)
                <div>
                    <span class="text-sm font-medium text-gray-600">Abweichung:</span>
                    <p class="text-sm @if($record->actual_minutes > $record->estimated_minutes) text-red-600 @elseif($record->actual_minutes < $record->estimated_minutes) text-green-600 @else text-gray-900 @endif">
                        {{ $record->actual_minutes - $record->estimated_minutes }} Minuten
                        @if($record->actual_minutes > $record->estimated_minutes)
                            (Überschreitung)
                        @elseif($record->actual_minutes < $record->estimated_minutes)
                            (Unterschreitung)
                        @else
                            (Genau geschätzt)
                        @endif
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Labels -->
    @if($record->labels && count($record->labels) > 0)
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Labels</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($record->labels as $label)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $label }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Erweiterte Informationen -->
    @if($record->parentTask || $record->subtasks->count() > 0 || $record->is_recurring)
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Erweiterte Informationen</h3>
        <div class="space-y-3">
            @if($record->parentTask)
            <div>
                <span class="text-sm font-medium text-gray-600">Übergeordnete Aufgabe:</span>
                <p class="text-sm text-gray-900">{{ $record->parentTask->title }}</p>
            </div>
            @endif
            
            @if($record->subtasks->count() > 0)
            <div>
                <span class="text-sm font-medium text-gray-600">Unteraufgaben:</span>
                <p class="text-sm text-gray-900">{{ $record->subtasks->count() }} Unteraufgaben</p>
            </div>
            @endif

            @if($record->is_recurring)
            <div>
                <span class="text-sm font-medium text-gray-600">Wiederkehrend:</span>
                <p class="text-sm text-gray-900">
                    Ja {{ $record->recurring_pattern ? '(' . $record->recurring_pattern . ')' : '' }}
                </p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Fortschritt -->
    @if($record->subtasks->count() > 0)
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Fortschritt</h3>
        <div class="space-y-3">
            <div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Fortschritt</span>
                    <span class="font-medium">{{ $record->progress_percentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $record->progress_percentage }}%"></div>
                </div>
            </div>
            <div class="text-sm text-gray-600">
                {{ $record->subtasks->where('status', 'completed')->count() }} von {{ $record->subtasks->count() }} Unteraufgaben abgeschlossen
            </div>
        </div>
    </div>
    @endif
</div>
