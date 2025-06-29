<x-filament-panels::page>
    <div class="timeline-page space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                Aufgaben & Projekttermine Timeline
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                Chronologische Übersicht aller anstehenden Aufgaben und Projekttermine
            </p>
        </div>

        <!-- Timeline Container - Flowbite Style -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <style>
                /* Timeline-specific styles - only for this page */
                .timeline-page .timeline-icon svg {
                    display: block;
                    width: 100%;
                    height: 100%;
                }
                
                .timeline-page .timeline-marker {
                    position: relative;
                    z-index: 10;
                }
                
                .timeline-page .timeline-content {
                    position: relative;
                }
                
                /* Flowbite Timeline Styles */
                .timeline-page .timeline-border {
                    border-left: 2px solid #e5e7eb;
                }
                
                .timeline-page .timeline-border.dark {
                    border-left-color: #374151;
                }
                
                .timeline-page .timeline-item {
                    position: relative;
                    margin-bottom: 2.5rem;
                    margin-left: 1.5rem;
                }
                
                .timeline-page .timeline-marker-date {
                    position: absolute;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 2rem;
                    height: 2rem;
                    border-radius: 50%;
                    left: -1rem;
                    border: 4px solid white;
                }
                
                .timeline-page .timeline-marker-item {
                    position: absolute;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 1.5rem;
                    height: 1.5rem;
                    border-radius: 50%;
                    left: -0.75rem;
                    border: 8px solid white;
                }
                
                .timeline-page .timeline-content-box {
                    padding: 1rem;
                    background-color: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 0.5rem;
                    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
                }
                
                .timeline-page .timeline-content-box.date-header {
                    background-color: #f3f4f6;
                }
                
                .timeline-page .dark .timeline-content-box {
                    background-color: #374151;
                    border-color: #4b5563;
                }
                
                .timeline-page .dark .timeline-content-box.date-header {
                    background-color: #4b5563;
                }
                
                .timeline-page .dark .timeline-marker-date,
                .timeline-page .dark .timeline-marker-item {
                    border-color: #1f2937;
                }
            </style>
            @php
                $timelineData = $this->getTimelineData();
                $today = \Carbon\Carbon::today();
            @endphp

            @forelse($timelineData as $date => $items)
                @php
                    $dateCarbon = \Carbon\Carbon::parse($date);
                    $isToday = $dateCarbon->isToday();
                    $isPast = $dateCarbon->isPast() && !$isToday;
                    $isFuture = $dateCarbon->isFuture();
                @endphp

                <ol class="relative timeline-border {{ !$loop->last ? 'mb-10' : '' }}">
                    <!-- Date Header Item -->
                    <li class="timeline-item">
                        <!-- Date Icon -->
                        <span class="timeline-marker-date
                            {{ $isToday ? 'bg-blue-100 dark:bg-blue-900' :
                               ($isPast ? 'bg-red-100 dark:bg-red-900' : 'bg-green-100 dark:bg-green-900') }}">
                            @if($isToday)
                                <svg class="w-3.5 h-3.5 text-blue-500 dark:text-blue-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                </svg>
                            @elseif($isPast)
                                <svg class="w-3.5 h-3.5 text-red-500 dark:text-red-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                                </svg>
                            @else
                                <svg class="w-3.5 h-3.5 text-green-500 dark:text-green-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                </svg>
                            @endif
                        </span>

                        <!-- Date Content -->
                        <div class="timeline-content-box date-header">
                            <div class="items-center justify-between mb-3 sm:flex">
                                <time class="mb-1 text-xs font-normal text-gray-400 sm:order-last sm:mb-0">
                                    {{ $dateCarbon->locale('de')->isoFormat('dddd') }}
                                </time>
                                <div class="text-sm font-normal text-gray-500 dark:text-gray-300">
                                    <span class="bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-600 dark:text-gray-300">
                                        {{ $items->count() }} {{ $items->count() === 1 ? 'Eintrag' : 'Einträge' }}
                                    </span>
                                    am <span class="font-semibold text-gray-900 dark:text-white">{{ $dateCarbon->format('d.m.Y') }}</span>
                                    @if($isToday)
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">Heute</span>
                                    @elseif($dateCarbon->isYesterday())
                                        <span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">Gestern</span>
                                    @elseif($dateCarbon->isTomorrow())
                                        <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Morgen</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- Items for this date -->
                    @foreach($items as $item)
                        <li class="timeline-item">
                            <!-- Item Icon -->
                            <span class="timeline-marker-item timeline-icon
                                {{ $item['type'] === 'task' ? 'bg-blue-100 dark:bg-blue-900' : 'bg-purple-100 dark:bg-purple-900' }}">
                                @if($item['type'] === 'task')
                                    <svg class="w-2.5 h-2.5 text-blue-800 dark:text-blue-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                    </svg>
                                @else
                                    <svg class="w-2.5 h-2.5 text-purple-800 dark:text-purple-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 5V.13a2.96 2.96 0 0 0-1.293.749L.879 3.707A2.96 2.96 0 0 0 .13 5H5Z"/>
                                        <path d="M6.737 11.061a2.961 2.961 0 0 1 .81-1.515l6.117-6.116A4.839 4.839 0 0 1 16 2.141V2a1.97 1.97 0 0 0-1.933-2H7v5a2 2 0 0 1-2 2H0v11a1.969 1.969 0 0 0 1.933 2h12.134A1.97 1.97 0 0 0 16 18v-3.093l-1.546 1.546c-.413.413-.94.695-1.513.81l-3.4.679a2.947 2.947 0 0 1-1.85-.227 2.96 2.96 0 0 1-1.635-3.257l.681-3.397Z"/>
                                        <path d="M8.961 16a.93.93 0 0 0 .189-.019l3.4-.679a.961.961 0 0 0 .49-.263l6.118-6.117a2.884 2.884 0 0 0-4.079-4.078l-6.117 6.117a.96.96 0 0 0-.263.491l-.679 3.4A.961.961 0 0 0 8.961 16Zm7.477-9.8a.958.958 0 0 1 .68-.281.961.961 0 0 1 .682 1.644l-.315.315-1.36-1.36.313-.318Zm-5.911 5.911 4.236-4.236 1.359 1.359-4.236 4.237-1.7.339.341-1.699Z"/>
                                    </svg>
                                @endif
                            </span>

                            <!-- Item Content -->
                            <div class="timeline-content-box">
                                <!-- Header with Title and Badges -->
                                <div class="items-center justify-between mb-3 sm:flex">
                                    <time class="mb-1 text-xs font-normal text-gray-400 sm:order-last sm:mb-0">
                                        @if($item['time'])
                                            {{ \Carbon\Carbon::parse($item['time'])->format('H:i') }} Uhr
                                        @endif
                                    </time>
                                    <div class="text-sm font-normal text-gray-500 dark:text-gray-300">
                                        @if($item['type'] === 'task')
                                            <span class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                                {{ $item['task_type'] }}
                                            </span>
                                        @else
                                            <span class="bg-purple-100 text-purple-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300">
                                                Projekttermin
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Title -->
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    {{ $item['title'] }}
                                </h3>

                                <!-- Description -->
                                @if($item['description'] && strlen(trim($item['description'])) > 0)
                                    <p class="mb-4 text-base font-normal text-gray-500 dark:text-gray-400">
                                        {{ Str::limit(strip_tags($item['description']), 150) }}
                                    </p>
                                @endif

                                <!-- Status and Priority Badges -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <!-- Status Badge -->
                                    <span class="text-xs font-medium px-2.5 py-0.5 rounded
                                        {{ $item['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 
                                           ($item['status'] === 'planned' ? 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300' : 
                                            ($item['status'] === 'delayed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300')) }}">
                                        {{ match($item['status']) {
                                            'open' => 'Offen',
                                            'in_progress' => 'In Bearbeitung',
                                            'planned' => 'Geplant',
                                            'delayed' => 'Verzögert',
                                            'waiting_external' => 'Warte Extern',
                                            'waiting_internal' => 'Warte Intern',
                                            default => $item['status']
                                        } }}
                                    </span>

                                    <!-- Priority Badge -->
                                    @if($item['priority'])
                                        <span class="text-xs font-medium px-2.5 py-0.5 rounded
                                            {{ $item['priority'] === 'urgent' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : 
                                               ($item['priority'] === 'high' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' : 
                                                ($item['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300')) }}">
                                            {{ match($item['priority']) {
                                                'urgent' => 'Dringend',
                                                'high' => 'Hoch',
                                                'medium' => 'Mittel',
                                                'low' => 'Niedrig',
                                                default => $item['priority']
                                            } }}
                                        </span>
                                    @endif

                                    <!-- Overdue/Due Today Badges -->
                                    @if($item['is_overdue'])
                                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300">
                                            Überfällig
                                        </span>
                                    @elseif($item['is_due_today'])
                                        <span class="bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-orange-900 dark:text-orange-300">
                                            Heute fällig
                                        </span>
                                    @endif
                                </div>

                                <!-- Details Grid -->
                                @if($item['owner'] || $item['assigned_user'] || $item['customer'] || $item['project'])
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4 text-sm">
                                        @if($item['owner'])
                                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                                <svg class="w-4 h-4 me-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                                                </svg>
                                                <span class="font-medium">Inhaber:</span>&nbsp;{{ $item['owner'] }}
                                            </div>
                                        @endif

                                        @if($item['assigned_user'])
                                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                                <svg class="w-4 h-4 me-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"/>
                                                    <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                                                </svg>
                                                <span class="font-medium">Zuständig:</span>&nbsp;{{ $item['assigned_user'] }}
                                            </div>
                                        @endif

                                        @if($item['customer'])
                                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                                <svg class="w-4 h-4 me-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M19 4h-1a1 1 0 1 0 0 2v11a1 1 0 0 1-2 0V2a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2v15a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V5a1 1 0 0 0-1-1ZM3 4a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4Zm9 13H4a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-3H4a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-3H4a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Z"/>
                                                </svg>
                                                <span class="font-medium">Kunde:</span>&nbsp;{{ $item['customer'] }}
                                            </div>
                                        @endif

                                        @if($item['project'])
                                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                                <svg class="w-4 h-4 me-3 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 5V.13a2.96 2.96 0 0 0-1.293.749L.879 3.707A2.96 2.96 0 0 0 .13 5H5Z"/>
                                                    <path d="M6.737 11.061a2.961 2.961 0 0 1 .81-1.515l6.117-6.116A4.839 4.839 0 0 1 16 2.141V2a1.97 1.97 0 0 0-1.933-2H7v5a2 2 0 0 1-2 2H0v11a1.969 1.969 0 0 0 1.933 2h12.134A1.97 1.97 0 0 0 16 18v-3.093l-1.546 1.546c-.413.413-.94.695-1.513.81l-3.4.679a2.947 2.947 0 0 1-1.85-.227 2.96 2.96 0 0 1-1.635-3.257l.681-3.397Z"/>
                                                    <path d="M8.961 16a.93.93 0 0 0 .189-.019l3.4-.679a.961.961 0 0 0 .49-.263l6.118-6.117a2.884 2.884 0 0 0-4.079-4.078l-6.117 6.117a.96.96 0 0 0-.263.491l-.679 3.4A.961.961 0 0 0 8.961 16Zm7.477-9.8a.958.958 0 0 1 .68-.281.961.961 0 0 1 .682 1.644l-.315.315-1.36-1.36.313-.318Zm-5.911 5.911 4.236-4.236 1.359 1.359-4.236 4.237-1.7.339.341-1.699Z"/>
                                                </svg>
                                                <span class="font-medium">Projekt:</span>&nbsp;{{ $item['project'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Actions Dropdown -->
                                <div class="relative inline-block text-left" x-data="{ open: false }">
                                    <button @click="open = !open" type="button"
                                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:outline-none focus:ring-gray-200 focus:text-blue-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                                            aria-expanded="false" aria-haspopup="true">
                                        Aktionen
                                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700 dark:ring-gray-600" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                                        <div class="py-1" role="none">
                                            <!-- View Action -->
                                            <a href="{{ $item['actions']['view'] }}"
                                               class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                                                <svg class="w-4 h-4 me-3 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 12.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
                                                    <path d="M17.5 9c-.167-.833-1.5-3-7.5-3s-7.333 2.167-7.5 3c.167.833 1.5 3 7.5 3s7.333-2.167 7.5-3Z"/>
                                                </svg>
                                                Anzeigen
                                            </a>
                                            
                                            <!-- Edit Action -->
                                            <a href="{{ $item['actions']['edit'] }}"
                                               class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                                                <svg class="w-4 h-4 me-3 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="m13.835 7.578-.005.007-7.137 7.137 2.139 2.138 7.143-7.142-2.14-2.14Zm-10.696 3.59 2.139 2.14 7.138-7.137.007-.005-2.141-2.141-7.143 7.143Zm1.433 4.261L2 12.852.051 18.684a1 1 0 0 0 1.265 1.264L7.147 18l-2.575-2.571Zm14.249-14.25a4.03 4.03 0 0 0-5.693 0L11.7 2.611 17.389 8.3l1.432-1.432a4.029 4.029 0 0 0 0-5.689Z"/>
                                                </svg>
                                                Bearbeiten
                                            </a>

                                            @if($item['type'] === 'task')
                                                <!-- Task-specific actions -->
                                                @if($item['actions']['can_start'])
                                                    <button type="button" onclick="alert('Funktion noch nicht implementiert')"
                                                            class="group flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                                                        <svg class="w-4 h-4 me-3 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="m17.418 3.623-.018-.008a6.713 6.713 0 0 0-2.4-.569V2a1 1 0 1 0-2 0v1.046a6.672 6.672 0 0 0-2.4.569l-.018.008A6.676 6.676 0 0 0 8 9.025V15a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-.025a6.676 6.676 0 0 0 2.582-5.402 6.676 6.676 0 0 0 2.836-5.975ZM9 9.025a4.675 4.675 0 0 1 2-3.825V15H9V9.025Z"/>
                                                        </svg>
                                                        Starten
                                                    </button>
                                                @endif

                                                @if($item['actions']['can_complete'])
                                                    <button type="button" onclick="alert('Funktion noch nicht implementiert')"
                                                            class="group flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-white" role="menuitem">
                                                        <svg class="w-4 h-4 me-3 text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                                                        </svg>
                                                        Abschließen
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>

            @empty
                <!-- Empty State -->
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Keine Termine gefunden</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Es gibt derzeit keine anstehenden Aufgaben oder Projekttermine.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>