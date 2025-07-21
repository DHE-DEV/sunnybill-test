<x-filament-panels::page>
    <!-- CSRF Token für AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @if($this->showBoard)
        <div class="bg-white dark:bg-gray-900 min-h-screen">
            <!-- Filter Bar -->
            <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
                <div class="space-y-3">
                    <!-- Erste Zeile: Suchfeld + Reset Button + Active Filter Info -->
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- Suchfeld -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Suche:</span>
                            <input type="text" 
                                   wire:model.live="searchQuery"
                                   placeholder="Aufgabennummer oder Titel..."
                                   class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   style="min-width: 200px;">
                        </div>
                        
                        <!-- Solaranlagen-Suchfeld -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Solaranlage:</span>
                            <input type="text" 
                                   wire:model.live="solarPlantSearch"
                                   placeholder="Solaranlage suchen..."
                                   class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   style="min-width: 200px;">
                        </div>
                        
                        <!-- Reset Button (grün) -->
                        <button wire:click="resetFilters"
                                class="px-3 py-1 text-xs font-medium text-white rounded hover:bg-green-600 transition-colors"
                                style="background-color: #16a34a !important;">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Zurücksetzen
                        </button>
                        
                        <!-- Active Filters Info -->
                        @if($filterAssignment !== 'all' || count($selectedStatuses) !== 6 || count($selectedPriorities) !== 5 || count($selectedDueDates) !== 5 || !empty(trim($searchQuery)) || !empty(trim($solarPlantSearch)))
                            <div class="text-xs text-gray-500 bg-yellow-50 px-2 py-1 rounded border border-yellow-200">
                                Filter aktiv: 
                                @if(!empty(trim($searchQuery)))
                                    Suche: "{{ $searchQuery }}"
                                @endif
                                @if(!empty(trim($searchQuery)) && (!empty(trim($solarPlantSearch)) || $filterAssignment !== 'all' || count($selectedStatuses) !== 6 || count($selectedPriorities) !== 5 || count($selectedDueDates) !== 5))
                                    • 
                                @endif
                                @if(!empty(trim($solarPlantSearch)))
                                    Solaranlage: "{{ $solarPlantSearch }}"
                                @endif
                                @if(!empty(trim($solarPlantSearch)) && ($filterAssignment !== 'all' || count($selectedStatuses) !== 6 || count($selectedPriorities) !== 5 || count($selectedDueDates) !== 5))
                                    • 
                                @endif
                                @if($filterAssignment !== 'all')
                                    {{ $this->assignmentFilters[$filterAssignment] }}
                                @endif
                                @if($filterAssignment !== 'all' && (count($selectedStatuses) !== 6 || count($selectedPriorities) !== 5 || count($selectedDueDates) !== 5))
                                    • 
                                @endif
                                @if(count($selectedStatuses) !== 6)
                                    {{ count($selectedStatuses) }}/6 Status
                                @endif
                                @if(count($selectedStatuses) !== 6 && (count($selectedPriorities) !== 5 || count($selectedDueDates) !== 5))
                                    • 
                                @endif
                                @if(count($selectedPriorities) !== 5)
                                    {{ count($selectedPriorities) }}/5 Prioritäten
                                @endif
                                @if(count($selectedPriorities) !== 5 && count($selectedDueDates) !== 5)
                                    • 
                                @endif
                                @if(count($selectedDueDates) !== 5)
                                    {{ count($selectedDueDates) }}/5 Fälligkeiten
                                @endif
                            </div>
                        @endif
                    </div>
                    
                    <!-- Zweite Zeile: Zuweisungsfilter + Prioritätsfilter (zweispaltig) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Linke Spalte: Zuweisungsfilter -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Zuweisung:</span>
                            <div class="flex gap-1 flex-wrap">
                                @foreach($this->assignmentFilters as $value => $label)
                                    <button wire:click="filterByAssignment('{{ $value }}')"
                                            class="px-3 py-1 text-xs font-medium rounded-full transition-colors border
                                                   {{ $filterAssignment === $value 
                                                      ? 'text-white border-transparent' 
                                                      : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}"
                                            style="{{ $filterAssignment === $value ? 'background-color: rgb(217, 119, 6) !important;' : '' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Rechte Spalte: Prioritätsfilter -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Priorität:</span>
                            <div class="flex gap-1 flex-wrap">
                                @foreach($this->availablePriorities as $priority => $label)
                                    <button wire:click="togglePriorityFilter('{{ $priority }}')"
                                            class="px-3 py-1 text-xs font-medium rounded-full transition-colors border
                                                   {{ in_array($priority, $selectedPriorities) 
                                                      ? 'text-white border-transparent' 
                                                      : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}"
                                            style="{{ in_array($priority, $selectedPriorities) ? 'background-color: rgb(217, 119, 6) !important;' : '' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dritte Zeile: Status-Filter + Fälligkeitsfilter (zweispaltig) -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Linke Spalte: Status-Filter -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</span>
                            <div class="flex gap-1 flex-wrap">
                                @foreach($this->availableStatuses as $status => $label)
                                    <button wire:click="toggleStatusFilter('{{ $status }}')"
                                            class="px-3 py-1 text-xs font-medium rounded-full transition-colors border
                                                   {{ in_array($status, $selectedStatuses) 
                                                      ? 'text-white border-transparent' 
                                                      : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}"
                                            style="{{ in_array($status, $selectedStatuses) ? 'background-color: rgb(217, 119, 6) !important;' : '' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        
                        <!-- Rechte Spalte: Fälligkeitsfilter -->
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Fälligkeit:</span>
                            <div class="flex gap-1 flex-wrap">
                                @foreach($this->availableDueDates as $dueDate => $label)
                                    <button wire:click="toggleDueDateFilter('{{ $dueDate }}')"
                                            class="px-3 py-1 text-xs font-medium rounded-full transition-colors border
                                                   {{ in_array($dueDate, $selectedDueDates) 
                                                      ? 'text-white border-transparent' 
                                                      : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}"
                                            style="{{ in_array($dueDate, $selectedDueDates) ? 'background-color: rgb(217, 119, 6) !important;' : '' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Kanban Board -->
            <div class="overflow-x-auto">
                <div class="flex gap-6 p-6" style="min-width: 1800px;">
                    @foreach($this->getStatusColumns() as $status => $column)
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg flex-shrink-0 {{ $status === 'recurring' ? 'bg-emerald-50 dark:bg-emerald-900' : '' }}" 
                             style="width: 280px;">
                        
                        <!-- Column Header -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 {{ $status === 'recurring' ? 'border-emerald-200 dark:border-emerald-700' : '' }}">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold {{ $status === 'recurring' ? 'text-emerald-800 dark:text-emerald-200' : 'text-gray-900 dark:text-white' }} text-sm">
                                    {{ $column['label'] }}
                                </h3>
                                <span class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-medium px-2 py-1 rounded {{ $status === 'recurring' ? 'bg-emerald-200 dark:bg-emerald-700 text-emerald-800 dark:text-emerald-200' : '' }}">
                                    {{ $column['count'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Tasks in Column -->
                        <div wire:key="status-{{ $status }}"
                             class="p-4 space-y-3 min-h-[600px] drop-zone"
                             data-status="{{ $status }}"
                             x-data="{ status: '{{ $status }}' }"
                             x-on:drop.prevent="
                                const taskId = $event.dataTransfer.getData('text/plain');
                                const orderedIds = Array.from($event.currentTarget.querySelectorAll('.task-card')).map(el => el.dataset.taskId);
                                @this.call('updateTaskOrder', taskId, status, orderedIds);
                                $event.currentTarget.classList.remove('bg-blue-100', 'dark:bg-gray-700');
                             "
                             x-on:dragover.prevent="
                                $event.currentTarget.classList.add('bg-blue-100', 'dark:bg-gray-700');
                                const afterElement = [...$event.currentTarget.querySelectorAll('.task-card:not(.dragging):not([data-is-blocker=true])')].reduce((closest, child) => {
                                    const box = child.getBoundingClientRect();
                                    const offset = $event.clientY - box.top - box.height / 2;
                                    if (offset < 0 && offset > closest.offset) {
                                        return { offset: offset, element: child };
                                    } else {
                                        return closest;
                                    }
                                }, { offset: Number.NEGATIVE_INFINITY }).element;
                                const draggedElement = document.querySelector('.task-card.dragging');
                                if (draggedElement) {
                                    if (afterElement == null) {
                                        $event.currentTarget.appendChild(draggedElement);
                                    } else {
                                        $event.currentTarget.insertBefore(draggedElement, afterElement);
                                    }
                                }
                             "
                             x-on:dragleave.prevent="
                                if (!$event.currentTarget.contains($event.relatedTarget)) {
                                    $event.currentTarget.classList.remove('bg-blue-100', 'dark:bg-gray-700');
                                }
                             ">

                            @foreach($column['tasks'] as $task)
                                <div wire:key="task-{{ $task->id }}"
                                     class="bg-white dark:bg-gray-700 rounded-lg p-3 shadow-sm border {{ $task->priority === 'blocker' ? 'border-red-500 border-2' : 'border-gray-200 dark:border-gray-600' }} {{ $task->priority === 'blocker' ? 'cursor-not-allowed' : 'cursor-move' }} task-card hover:shadow-md transition-shadow"
                                     draggable="true"
                                     data-task-id="{{ $task->id }}"
                                     data-is-blocker="{{ $task->priority === 'blocker' ? 'true' : 'false' }}"
                                     x-on:dragstart="
                                        $event.dataTransfer.setData('text/plain', '{{ $task->id }}');
                                        $event.dataTransfer.setData('application/json', '{{ $task->status }}');
                                        $event.target.classList.add('opacity-50', 'dragging');
                                     "
                                     x-on:dragend="
                                        $event.target.classList.remove('opacity-50', 'dragging');
                                     ">
                                     
                                     @if($task->priority === 'blocker')
                                        <div class="pt-2"></div>
                                         <div class="blocker-banner text-white text-xs font-bold px-0 py-1 rounded-t-lg -mx-3 -mt-3 mb-3 text-center"
                                              style="background-color: #dc2626 !important; color: white !important;">
                                             BLOCKER
                                         </div>
                                         <div class="pt-2"></div>
                                     @endif
                                     
                                     <!-- Task Content with Edit Button -->
                                     <div class="flex items-start justify-between">
                                         <div class="flex-grow space-y-2 mr-2">
                                             <!-- Task Title -->
                                             <h4 class="text-sm font-medium text-gray-900 dark:text-white leading-tight">
                                                 {{ $task->title }}
                                             </h4>

                                             <!-- Task Description -->
                                             @if($task->description)
                                                 <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                                     {{ Str::limit($task->description, 100) }}
                                                 </p>
                                             @endif

                                             <!-- Task Meta Info -->
                                             <div class="flex flex-col text-xs space-y-1">
                                                 @if($task->task_number)
                                                     <span class="text-gray-500 dark:text-gray-400 font-mono">
                                                         {{ $task->task_number }}
                                                     </span>
                                                 @endif
                                                 
                                                 @if($task->applies_to_all_solar_plants)
                                                     <span class="text-purple-600 dark:text-purple-400 font-medium">
                                                         🌞 Alle Solaranlagen
                                                     </span>
                                                 @elseif($task->solarPlant)
                                                     <span class="text-blue-600 dark:text-blue-400 font-medium">
                                                         🌞 {{ $task->solarPlant->name }}
                                                     </span>
                                                 @endif
                                                 
                                                 @if($task->due_date)
                                                     @php
                                                         $now = now()->startOfDay();
                                                         $due = \Carbon\Carbon::parse($task->due_date)->startOfDay();
                                                         $diffInDays = $now->diffInDays($due, false);
                                                         
                                                         if ($diffInDays < 0) {
                                                             $color = '#dc2626'; // red-600
                                                         } elseif ($diffInDays == 0) {
                                                             $color = '#ea580c'; // orange-600
                                                         } elseif ($diffInDays <= 7) {
                                                             $color = '#2563eb'; // blue-600
                                                         } else {
                                                             $color = '#6b7280'; // gray-500
                                                         }
                                                     @endphp
                                                     <span class="text-xs font-medium" style="color: {{ $color }} !important;">
                                                         {{ $this->getDueDateText($task) }}
                                                     </span>
                                                 @endif
                                             </div>

                                             <!-- Priority, Task Type, Recurring and User Badges -->
                                             <div class="flex items-center gap-2 flex-wrap">
                                                 <!-- Priority Badge -->
                                                 @if($task->priority === 'blocker')
                                                     <span class="text-xs px-2 py-1 rounded font-medium" style="background-color: #dc2626 !important; color: white !important;">
                                                         Blocker
                                                     </span>
                                                 @elseif($task->priority === 'urgent')
                                                     <span class="text-xs px-2 py-1 rounded font-medium" style="background-color: #fecaca !important; color: #991b1b !important;">
                                                         Dringend
                                                     </span>
                                                 @elseif($task->priority === 'high')
                                                     <span class="text-xs px-2 py-1 rounded font-medium" style="background-color: #fed7aa !important; color: #c2410c !important;">
                                                         Hoch
                                                     </span>
                                                 @elseif($task->priority === 'medium')
                                                     <span class="text-xs px-2 py-1 rounded font-medium" style="background-color: #fef3c7 !important; color: #a16207 !important;">
                                                         Mittel
                                                     </span>
                                                 @elseif($task->priority === 'low')
                                                     <span class="text-xs px-2 py-1 rounded font-medium" style="background-color: #dcfce7 !important; color: #166534 !important;">
                                                         Niedrig
                                                     </span>
                                                 @else
                                                     <span class="text-xs px-2 py-1 rounded font-medium" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                                                         Keine Priorität
                                                     </span>
                                                 @endif
                                                 
                                                 <!-- Recurring Badge -->
                                                 @if($task->is_recurring)
                                                     <span class="text-xs px-2 py-1 rounded font-medium" style="background-color: #10b981 !important; color: white !important;" title="Wiederkehrende Aufgabe">
                                                         Wiederkehrend
                                                     </span>
                                                 @endif
                                                 
                                                 <!-- Task Type Badge -->
                                                 @if($task->taskType)
                                                     <span class="text-xs px-2 py-1 rounded" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                                                         {{ $task->taskType->name }}
                                                     </span>
                                                 @endif
                                                 
                                                 <!-- Owner Badge with Person Icon -->
                                                 @if($task->owner)
                                                     <span class="text-xs px-2 py-1 rounded font-medium flex items-center gap-1" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                                                         👤 {{ $task->owner->name }}
                                                     </span>
                                                 @endif
                                                 
                                                 <!-- Assigned User Badge with Hammer Icon -->
                                                 @if($task->assignedUser)
                                                     <span class="text-xs px-2 py-1 rounded font-medium flex items-center gap-1" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                                                         🔨 {{ $task->assignedUser->name }}
                                                     </span>
                                                 @endif
                                             </div>
                                         </div>
                                         
                                         <!-- Action Buttons -->
                                         <div class="flex-shrink-0 flex flex-col items-center gap-1">
                                             <!-- Details Button -->
                                             <button wire:click="openDetailsModal({{ $task->id }})"
                                                     class="text-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors p-1"
                                                     title="Details anzeigen">
                                                 <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                     <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                 </svg>
                                             </button>
                                             
                                             <!-- Edit Button -->
                                             <button wire:click="editTaskById({{ $task->id }})"
                                                     class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1"
                                                     title="Aufgabe bearbeiten">
                                                 <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                     <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                     <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                                 </svg>
                                             </button>
                                             
                                             <!-- Notes Button -->
                                             <button wire:click="openNotesModal({{ $task->id }})"
                                                     class="{{ $this->hasUnreadNotes($task) ? 'text-orange-500 hover:text-orange-600' : 'text-gray-400 hover:text-gray-600' }} dark:hover:text-gray-300 transition-colors p-1"
                                                     title="Notizen anzeigen">
                                                 <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                     <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                                 </svg>
                                             </button>
                                             
                                             <!-- History Button -->
                                             <button wire:click="openHistoryModal({{ $task->id }})"
                                                     class="{{ $this->hasUnreadHistory($task) ? 'text-orange-500 hover:text-orange-600' : 'text-gray-400 hover:text-gray-600' }} dark:hover:text-gray-300 transition-colors p-1"
                                                     title="Historie anzeigen">
                                                 <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                     <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                 </svg>
                                             </button>
                                             
                                             <!-- Mark as Read Button (Eye) -->
                                             <button wire:click="markAsRead({{ $task->id }})"
                                                     class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1"
                                                     title="Als gelesen markieren">
                                                 <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                     <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                     <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                 </svg>
                                             </button>
                                         </div>
                                     </div>
                                 </div>
                            @endforeach
                            
                            <!-- Add Task Button -->
                            <button wire:click="addTaskToColumn('{{ $status }}')" class="w-full p-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors text-sm">
                                + Aufgabe hinzufügen
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Edit Task Modal -->
        @if($showEditModal)
            <div class="fixed inset-0 z-[9999] overflow-y-auto"
                 aria-labelledby="modal-title"
                 role="dialog"
                 aria-modal="true"
                 style="z-index: 9999 !important;"
                 x-data="{}"
                 x-on:keydown.escape.window="$wire.closeEditModal()">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 transition-opacity" style="background-color: rgba(0, 0, 0, 0.75) !important; z-index: 9998 !important;" wire:click="closeEditModal"></div>
                    
                    <!-- Modal container -->
                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center" style="z-index: 9999 !important;">
                        <!-- Modal panel -->
                        <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all" style="width: 90vw !important; max-width: 1200px !important; z-index: 9999 !important; padding: 20px !important;">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    @if($editingTask)
                                        Aufgabe bearbeiten
                                    @else
                                        Neue Aufgabe erstellen
                                    @endif
                                </h3>
                                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Form Fields -->
                            <div class="space-y-4 pt-4">
                                <!-- Titel -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Titel</label>
                                    <input type="text" wire:model="editTitle" class="block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                                
                                <!-- Beschreibung -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Beschreibung</label>
                                    <textarea wire:model="editDescription" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                                </div>
                                
                                <!-- Solaranlage (volle Breite) -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Solaranlage</label>
                                    <select wire:model="editSolarPlantId" class="block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="">-- Keine Solaranlage --</option>
                                        <option value="all">Alle Solaranlagen</option>
                                        @foreach($this->solarPlants as $solarPlant)
                                            <option value="{{ $solarPlant->id }}">{{ $solarPlant->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- 2-Spalten Grid für alle restlichen Felder -->
                                <div style="display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 1rem !important;">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select wire:model="editStatus" class="block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="recurring">Wiederkehrend</option>
                                            <option value="open">Offen</option>
                                            <option value="in_progress">In Bearbeitung</option>
                                            <option value="waiting_external">Warte auf Extern</option>
                                            <option value="waiting_internal">Warte auf Intern</option>
                                            <option value="completed">Abgeschlossen</option>
                                            <option value="cancelled">Abgebrochen</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Aufgabentyp</label>
                                        <select wire:model="editTaskTypeId" class="block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="">-- Bitte wählen --</option>
                                            @foreach($this->taskTypes as $taskType)
                                                <option value="{{ $taskType->id }}">{{ $taskType->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Priorität</label>
                                        <select wire:model="editPriority" class="block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="low">Niedrig</option>
                                            <option value="medium">Mittel</option>
                                            <option value="high">Hoch</option>
                                            <option value="urgent">Dringend</option>
                                            <option value="blocker">Blocker</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Fälligkeitsdatum</label>
                                        <input type="date" wire:model="editDueDate" class="block w-full border-gray-300 rounded-md shadow-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Zugewiesen an</label>
                                        <select wire:model="editAssignedTo" class="block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="">-- Nicht zugewiesen --</option>
                                            @foreach($this->users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Inhaber</label>
                                        <select wire:model="editOwnerId" class="block w-full border-gray-300 rounded-md shadow-sm">
                                            <option value="">-- Kein Inhaber --</option>
                                            @foreach($this->users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex justify-end mt-6 pt-4 border-t border-gray-200" style="gap: 1rem !important;">
                                <button wire:click="closeEditModal" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                    Abbrechen
                                </button>
                                <button wire:click="saveTask" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 focus:outline-none" style="background-color: rgb(217, 119, 6) !important; color: white !important; border: none !important;">
                                    Speichern
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Notes Modal -->
            @if($showNotesModal && $notesTask)
                <div class="fixed inset-0 z-[9999] overflow-y-auto"
                     aria-labelledby="notes-modal-title"
                     role="dialog"
                     aria-modal="true"
                     style="z-index: 9999 !important;"
                     x-data="{}"
                     x-on:keydown.escape.window="$wire.closeNotesModal()">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 transition-opacity" style="background-color: rgba(0, 0, 0, 0.75) !important; z-index: 9998 !important;" wire:click="closeNotesModal"></div>
                    
                    <!-- Modal container -->
                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center" style="z-index: 9999 !important;">
                        <!-- Modal panel -->
                        <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all" style="width: 90vw !important; max-width: 800px !important; z-index: 9999 !important; padding: 20px !important; max-height: 80vh !important;">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    {{ $notesTask->title }}
                                </h3>
                                <button wire:click="closeNotesModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- New Note Input -->
                            <div class="mb-6 pt-4 bg-gray-50 rounded-lg" style="padding: 16px;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Neue Notiz hinzufügen</label>
                                
                <!-- Rich Text Editor -->
                <div class="border rounded-lg overflow-hidden" style="border: 2px solid #3b82f6;" wire:ignore>
                    <div id="rich-text-editor" 
                         style="min-height: 120px; background: white;"></div>
                </div>
                                
                                <!-- Hidden textarea for Livewire -->
                                <textarea wire:model="newNoteContent"
                                          id="kanban-note-content"
                                          style="display: none;"></textarea>
                                
                                <!-- Benutzer-Auswahl-Komponente -->
                                <div class="user-mention-selector mt-3" style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                                    <div style="margin-bottom: 8px;">
                                        <span style="font-size: 13px; font-weight: 500; color: #374151;">💡 Klicken Sie auf einen Benutzer, um ihn zu erwähnen:</span>
                                    </div>
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px;" id="kanban-user-buttons">
                                        <!-- Benutzer-Buttons werden hier dynamisch eingefügt -->
                                    </div>
                                </div>
                                
                                <div class="flex justify-end mt-3">
                                    <button onclick="saveRichTextNote()"
                                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none"
                                            style="background-color: rgb(217, 119, 6) !important; color: white !important; border: none !important;">
                                        Speichern
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Notes List -->
                            <div class="pt-4 space-y-4" style="max-height: 400px !important; overflow-y: auto !important;">
                                @forelse($this->notes as $note)
                                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex items-center">
                                                <span class="text-sm font-medium text-gray-900">{{ $note->user->name }}</span>
                                                <span class="text-sm text-gray-500">&nbsp;&nbsp;&nbsp;{{ $note->created_at->format('d.m.Y H:i') }}</span>
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-700 prose prose-sm max-w-none">{!! $note->content !!}</div>
                                    </div>
                                @empty
                                    <div class="text-center py-8 text-gray-500">
                                        <p>Noch keine Notizen vorhanden</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- History Modal -->
            @if($showHistoryModal && $historyTask)
                <div class="fixed inset-0 z-[9999] overflow-y-auto"
                     aria-labelledby="history-modal-title"
                     role="dialog"
                     aria-modal="true"
                     style="z-index: 9999 !important;"
                     x-data="{}"
                     x-on:keydown.escape.window="$wire.closeHistoryModal()">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 transition-opacity" style="background-color: rgba(0, 0, 0, 0.75) !important; z-index: 9998 !important;" wire:click="closeHistoryModal"></div>
                    
                    <!-- Modal container -->
                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center" style="z-index: 9999 !important;">
                        <!-- Modal panel -->
                        <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all" style="width: 90vw !important; max-width: 800px !important; z-index: 9999 !important; padding: 20px !important; max-height: 80vh !important;">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Historie: {{ $historyTask->title }}
                                </h3>
                                <button wire:click="closeHistoryModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- History List -->
                            <div class="pt-4 space-y-4" style="max-height: 400px !important; overflow-y: auto !important;">
                                @forelse($this->history as $historyEntry)
                                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex items-center">
                                                <span class="text-sm font-medium text-gray-900">{{ $historyEntry->user->name }}</span>
                                                <span class="text-sm text-gray-500">&nbsp;&nbsp;&nbsp;{{ $historyEntry->created_at->format('d.m.Y H:i') }}</span>
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-700">
                                            <strong>{{ $historyEntry->action }}:</strong> {{ $historyEntry->description }}
                                            @if($historyEntry->old_value || $historyEntry->new_value)
                                                <div class="mt-1 text-xs text-gray-600">
                                                    @if($historyEntry->old_value)
                                                        <span class="text-red-600">Alt: {{ $historyEntry->old_value }}</span>
                                                    @endif
                                                    @if($historyEntry->old_value && $historyEntry->new_value)
                                                        →
                                                    @endif
                                                    @if($historyEntry->new_value)
                                                        <span class="text-green-600">Neu: {{ $historyEntry->new_value }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-8 text-gray-500">
                                        <p>Noch keine Historie vorhanden</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Details Modal -->
            @if($showDetailsModal && $detailsTask)
                <div class="fixed inset-0 z-[9999] overflow-y-auto"
                     aria-labelledby="details-modal-title"
                     role="dialog"
                     aria-modal="true"
                     style="z-index: 9999 !important;"
                     x-data="{}"
                     x-on:keydown.escape.window="$wire.closeDetailsModal()">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 transition-opacity" style="background-color: rgba(0, 0, 0, 0.75) !important; z-index: 9998 !important;" wire:click="closeDetailsModal"></div>
                    
                    <!-- Modal container -->
                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center" style="z-index: 9999 !important;">
                        <!-- Modal panel -->
                        <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all" style="width: 90vw !important; max-width: 1000px !important; z-index: 9999 !important; padding: 20px !important; max-height: 85vh !important;">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    {{ $detailsTask->title }}
                                </h3>
                                <button wire:click="closeDetailsModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Content -->
                            <div class="pt-4 space-y-6" style="max-height: 70vh !important; overflow-y: auto !important;">
                                <!-- Task Information Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Left Column -->
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Aufgabennummer</label>
                                            <p class="text-sm text-gray-900 font-mono">{{ $detailsTask->task_number ?? 'Nicht vergeben' }}</p>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                                                {{ $this->availableStatuses[$detailsTask->status] ?? ucfirst($detailsTask->status) }}
                                            </span>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Priorität</label>
                                            @if($detailsTask->priority === 'blocker')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #dc2626 !important; color: white !important;">
                                                    Blocker
                                                </span>
                                            @elseif($detailsTask->priority === 'urgent')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #fecaca !important; color: #991b1b !important;">
                                                    Dringend
                                                </span>
                                            @elseif($detailsTask->priority === 'high')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #fed7aa !important; color: #c2410c !important;">
                                                    Hoch
                                                </span>
                                            @elseif($detailsTask->priority === 'medium')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #fef3c7 !important; color: #a16207 !important;">
                                                    Mittel
                                                </span>
                                            @elseif($detailsTask->priority === 'low')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #dcfce7 !important; color: #166534 !important;">
                                                    Niedrig
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                                                    Keine Priorität
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Aufgabentyp</label>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: #f3f4f6 !important; color: #374151 !important;">
                                                {{ $detailsTask->taskType->name ?? 'Nicht festgelegt' }}
                                            </span>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Fälligkeitsdatum</label>
                                            <p class="text-sm text-gray-900">
                                                @if($detailsTask->due_date)
                                                    {{ $detailsTask->due_date->format('d.m.Y') }}
                                                    <span class="text-xs {{ $this->getDueDateColor($detailsTask->due_date) }}">
                                                        ({{ $this->getDueDateText($detailsTask->due_date) }})
                                                    </span>
                                                @else
                                                    Nicht festgelegt
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Right Column -->
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Zugewiesen an</label>
                                            <p class="text-sm text-gray-900">{{ $detailsTask->assignedUser->name ?? 'Nicht zugewiesen' }}</p>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Inhaber</label>
                                            <p class="text-sm text-gray-900">{{ $detailsTask->owner->name ?? 'Nicht festgelegt' }}</p>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Erstellt von</label>
                                            <p class="text-sm text-gray-900">{{ $detailsTask->creator->name ?? 'Unbekannt' }}</p>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Erstellt am</label>
                                            <p class="text-sm text-gray-900">{{ $detailsTask->created_at->format('d.m.Y H:i') }}</p>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Solaranlage</label>
                                            <p class="text-sm text-gray-900">
                                                @if($detailsTask->applies_to_all_solar_plants)
                                                    🌞 Alle Solaranlagen
                                                @elseif($detailsTask->solarPlant)
                                                    🌞 {{ $detailsTask->solarPlant->name }}
                                                @else
                                                    Nicht festgelegt
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                @if($detailsTask->description)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Beschreibung</label>
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $detailsTask->description }}</p>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Additional Information -->
                                <div class="border-t pt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Zusätzliche Informationen</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        @if($detailsTask->completed_at)
                                            <div>
                                                <span class="font-medium">Abgeschlossen am:</span>
                                                <span class="text-gray-900">{{ $detailsTask->completed_at->format('d.m.Y H:i') }}</span>
                                            </div>
                                        @endif
                                        
                                        <div>
                                            <span class="font-medium">Letzte Änderung:</span>
                                            <span class="text-gray-900">{{ $detailsTask->updated_at->format('d.m.Y H:i') }}</span>
                                        </div>
                                        
                                        @if($detailsTask->parentTask)
                                            <div>
                                                <span class="font-medium">Übergeordnete Aufgabe:</span>
                                                <span class="text-gray-900">{{ $detailsTask->parentTask->title }}</span>
                                            </div>
                                        @endif
                                        
                                        @if($detailsTask->subtasks->count() > 0)
                                            <div>
                                                <span class="font-medium">Unteraufgaben:</span>
                                                <span class="text-gray-900">{{ $detailsTask->subtasks->count() }} Stück</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class="flex justify-end mt-6 pt-4 border-t border-gray-200">
                                <button wire:click="closeDetailsModal" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                    Schließen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
    @else
        <!-- List View (fallback) -->
        <div class="bg-white dark:bg-gray-900">
            <p class="p-6 text-gray-500">List View ist noch nicht implementiert. Verwenden Sie die Board-Ansicht.</p>
        </div>
    @endif

    <!-- Kanban @mention JavaScript -->
    <script>
    // Globale Variablen für Kanban @mentions
    window.kanbanMentionUsers = [];
    window.kanbanFilteredUsers = [];
    window.kanbanMentionDropdown = null;

    // Kanban Mention System initialisieren
    window.initializeKanbanMentionSystem = function() {
        console.log('🔧 Kanban: Initialisiere Mention System...');
        
        // Benutzer laden falls noch nicht geschehen
        if (window.kanbanMentionUsers.length === 0) {
            fetch('/api/users/all')
                .then(response => response.json())
                .then(users => {
                    window.kanbanMentionUsers = users;
                    console.log('👥 Kanban: Benutzer geladen:', users.length);
                    createKanbanUserButtons();
                })
                .catch(error => {
                    console.error('❌ Kanban: Fehler beim Laden der Benutzer:', error);
                });
        } else {
            createKanbanUserButtons();
        }
        
        // CSS hinzufügen
        if (!document.getElementById('kanban-mention-styles')) {
            const style = document.createElement('style');
            style.id = 'kanban-mention-styles';
            style.textContent = `
                #kanban-mention-dropdown {
                    position: absolute;
                    background: white;
                    border: 1px solid #d1d5db;
                    border-radius: 6px;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                    max-height: 200px;
                    overflow-y: auto;
                    z-index: 99999 !important;
                    display: none;
                    min-width: 250px;
                    font-family: system-ui, -apple-system, sans-serif;
                }
                .kanban-mention-item {
                    padding: 12px 16px;
                    cursor: pointer;
                    border-bottom: 1px solid #f3f4f6;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    transition: background-color 0.15s ease;
                }
                .kanban-mention-item:hover,
                .kanban-mention-item.selected {
                    background-color: #eff6ff !important;
                }
                .kanban-mention-avatar {
                    width: 28px;
                    height: 28px;
                    border-radius: 50%;
                    background-color: #3b82f6;
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    font-weight: bold;
                }
                .kanban-mention-name {
                    font-weight: 600;
                    font-size: 14px;
                    color: #111827;
                }
                .kanban-user-btn {
                    display: inline-flex;
                    align-items: center;
                    padding: 6px 10px;
                    border: 1px solid #d1d5db;
                    border-radius: 4px;
                    background: white;
                    cursor: pointer;
                    font-size: 12px;
                    gap: 6px;
                    transition: all 0.2s;
                }
                .kanban-user-btn:hover {
                    background-color: #f3f4f6;
                    transform: translateY(-1px);
                }
                .kanban-user-avatar {
                    width: 18px;
                    height: 18px;
                    border-radius: 50%;
                    background-color: #3b82f6;
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 10px;
                    font-weight: bold;
                }
            `;
            document.head.appendChild(style);
            console.log('✅ Kanban: Mention Styles hinzugefügt');
        }
    };

    // Benutzer-Buttons erstellen
    function createKanbanUserButtons() {
        const container = document.getElementById('kanban-user-buttons');
        if (!container || window.kanbanMentionUsers.length === 0) return;
        
        container.innerHTML = '';
        
        window.kanbanMentionUsers.forEach(user => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'kanban-user-btn';
            button.onclick = () => insertKanbanUserMention(user.name);
            button.title = `@${user.name} erwähnen`;
            
            const initial = user.name.charAt(0).toUpperCase();
            button.innerHTML = `
                <div class="kanban-user-avatar">${initial}</div>
                <span>${user.name}</span>
            `;
            
            container.appendChild(button);
        });
        
        console.log('✅ Kanban: Benutzer-Buttons erstellt:', window.kanbanMentionUsers.length);
    }

    // Benutzer-Mention einfügen (Button-Klick)
    function insertKanbanUserMention(username) {
        console.log('👤 Kanban: Benutzer-Button geklickt:', username);
        
        const textarea = document.getElementById('kanban-note-content');
        if (!textarea) {
            console.error('❌ Kanban: Textarea nicht gefunden');
            return;
        }
        
        const cursorPos = textarea.selectionStart;
        const currentText = textarea.value;
        const textBefore = currentText.substring(0, cursorPos);
        const textAfter = currentText.substring(cursorPos);
        const mentionText = ' @' + username + ' ';
        const newText = textBefore + mentionText + textAfter;
        
        textarea.value = newText;
        const newCursorPos = cursorPos + mentionText.length;
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();
        
        // Livewire Event triggern
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        
        console.log('✅ Kanban: Mention eingefügt:', mentionText);
        
        // Visuelles Feedback
        const button = event.target.closest('.kanban-user-btn');
        if (button) {
            const originalBg = button.style.backgroundColor;
            button.style.backgroundColor = '#10b981';
            button.style.color = 'white';
            setTimeout(() => {
                button.style.backgroundColor = originalBg;
                button.style.color = '';
            }, 300);
        }
    }

    // Input Handler für Autovervollständigung
    window.handleKanbanMentionInput = function(textarea, event) {
        console.log('📝 Kanban: Input Event:', textarea.value);
        
        const cursorPos = textarea.selectionStart;
        const text = textarea.value;
        
        let mentionStart = -1;
        for (let i = cursorPos - 1; i >= 0; i--) {
            if (text[i] === '@') {
                if (i === 0 || /\s/.test(text[i - 1])) {
                    mentionStart = i;
                    break;
                }
            } else if (/\s/.test(text[i])) {
                break;
            }
        }

        if (mentionStart !== -1) {
            const query = text.substring(mentionStart + 1, cursorPos);
            if (!/\s/.test(query)) {
                console.log('🔍 Kanban: Zeige Dropdown für:', query);
                showKanbanMentionDropdown(textarea, query, mentionStart);
                return;
            }
        }
        hideKanbanMentionDropdown();
    };

    // Keydown Handler für Navigation
    window.handleKanbanMentionKeydown = function(textarea, event) {
        console.log('⌨️ Kanban: Keydown:', event.key);
        
        const dropdown = document.getElementById('kanban-mention-dropdown');
        if (!dropdown || dropdown.style.display === 'none') return;
        
        const items = dropdown.querySelectorAll('.kanban-mention-item');
        let selectedIndex = Array.from(items).findIndex(item => item.classList.contains('selected'));
        
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateKanbanSelection(items, selectedIndex);
                break;
            case 'ArrowUp':
                event.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                updateKanbanSelection(items, selectedIndex);
                break;
            case 'Tab':
            case 'Enter':
                event.preventDefault();
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    selectKanbanMention(items[selectedIndex], textarea);
                }
                break;
            case 'Escape':
                hideKanbanMentionDropdown();
                break;
        }
    };

    // Dropdown anzeigen
    function showKanbanMentionDropdown(textarea, query, mentionStart) {
        console.log('🔍 Kanban: Suche nach Benutzern mit Query:', query);
        
        // API-Suche verwenden für bessere Ergebnisse
        fetch(`/api/users/search?q=${encodeURIComponent(query)}`)
            .then(response => {
                console.log('📡 Kanban: API Response Status:', response.status);
                return response.json();
            })
            .then(users => {
                console.log('👥 Kanban: API Suchergebnis:', users.length, 'Benutzer');
                
                if (users.length === 0) {
                    console.log('❌ Kanban: Keine Benutzer gefunden für:', query);
                    hideKanbanMentionDropdown();
                    return;
                }

                hideKanbanMentionDropdown();

                const dropdown = document.createElement('div');
                dropdown.id = 'kanban-mention-dropdown';
                
                // Speichere gefilterte Benutzer für Auswahl
                window.kanbanFilteredUsers = users.slice(0, 5);
                
                window.kanbanFilteredUsers.forEach((user, index) => {
                    const item = document.createElement('div');
                    item.className = 'kanban-mention-item' + (index === 0 ? ' selected' : '');
                    item.dataset.userIndex = index;
                    item.dataset.mentionStart = mentionStart;
                    
                    const initial = user.name.charAt(0).toUpperCase();
                    item.innerHTML = `
                        <div class="kanban-mention-avatar">${initial}</div>
                        <div class="kanban-mention-name">${user.name}</div>
                    `;
                    
                    item.addEventListener('click', () => {
                        selectKanbanMention(item, textarea);
                    });
                    
                    dropdown.appendChild(item);
                });

                const rect = textarea.getBoundingClientRect();
                dropdown.style.left = rect.left + 'px';
                dropdown.style.top = (rect.bottom + 5) + 'px';
                dropdown.style.display = 'block';
                
                document.body.appendChild(dropdown);
                window.kanbanMentionDropdown = dropdown;
                
                console.log('✅ Kanban: Dropdown angezeigt mit', window.kanbanFilteredUsers.length, 'Benutzern');
            })
            .catch(error => {
                console.error('❌ Kanban: Fehler bei API-Suche:', error);
                // Fallback auf lokale Filterung
                const filteredUsers = window.kanbanMentionUsers.filter(user =>
                    user.name.toLowerCase().includes(query.toLowerCase())
                ).slice(0, 5);
                
                if (filteredUsers.length > 0) {
                    console.log('🔄 Kanban: Fallback auf lokale Filterung:', filteredUsers.length);
                    showKanbanMentionDropdownLocal(textarea, filteredUsers, mentionStart);
                } else {
                    hideKanbanMentionDropdown();
                }
            });
    }
    
    // Lokale Dropdown-Anzeige (Fallback)
    function showKanbanMentionDropdownLocal(textarea, users, mentionStart) {
        hideKanbanMentionDropdown();

        const dropdown = document.createElement('div');
        dropdown.id = 'kanban-mention-dropdown';
        
        window.kanbanFilteredUsers = users;
        
        users.forEach((user, index) => {
            const item = document.createElement('div');
            item.className = 'kanban-mention-item' + (index === 0 ? ' selected' : '');
            item.dataset.userIndex = index;
            item.dataset.mentionStart = mentionStart;
            
            const initial = user.name.charAt(0).toUpperCase();
            item.innerHTML = `
                <div class="kanban-mention-avatar">${initial}</div>
                <div class="kanban-mention-name">${user.name}</div>
            `;
            
            item.addEventListener('click', () => {
                selectKanbanMention(item, textarea);
            });
            
            dropdown.appendChild(item);
        });

        const rect = textarea.getBoundingClientRect();
        dropdown.style.left = rect.left + 'px';
        dropdown.style.top = (rect.bottom + 5) + 'px';
        dropdown.style.display = 'block';
        
        document.body.appendChild(dropdown);
        window.kanbanMentionDropdown = dropdown;
        
        console.log('✅ Kanban: Lokaler Dropdown angezeigt mit', users.length, 'Benutzern');
    }

    // Dropdown verstecken
    function hideKanbanMentionDropdown() {
        const dropdown = document.getElementById('kanban-mention-dropdown');
        if (dropdown) {
            dropdown.remove();
        }
        window.kanbanMentionDropdown = null;
    }

    // Auswahl aktualisieren
    function updateKanbanSelection(items, selectedIndex) {
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === selectedIndex);
        });
    }

    // Mention auswählen
    function selectKanbanMention(item, textarea) {
        const userIndex = parseInt(item.dataset.userIndex);
        const mentionStart = parseInt(item.dataset.mentionStart);
        
        // Verwende gefilterte Benutzer falls vorhanden, sonst alle Benutzer
        const users = window.kanbanFilteredUsers || window.kanbanMentionUsers;
        const user = users[userIndex];
        
        console.log('🎯 Kanban: Auswahl - Index:', userIndex, 'User:', user?.name);
        
        if (user) {
            const text = textarea.value;
            const cursorPos = textarea.selectionStart;
            const beforeMention = text.substring(0, mentionStart);
            const afterCursor = text.substring(cursorPos);
            const newText = beforeMention + '@' + user.name + ' ' + afterCursor;
            
            textarea.value = newText;
            textarea.setSelectionRange(beforeMention.length + user.name.length + 2, beforeMention.length + user.name.length + 2);
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            
            console.log('✅ Kanban: Mention eingefügt:', user.name);
        } else {
            console.error('❌ Kanban: Benutzer nicht gefunden für Index:', userIndex);
        }
        
        hideKanbanMentionDropdown();
        textarea.focus();
    }

    // Debug-Funktion
    window.debugKanbanMentions = function() {
        console.log('🔧 Kanban Debug Info:');
        console.log('- Textarea gefunden:', !!document.getElementById('kanban-note-content'));
        console.log('- Benutzer geladen:', window.kanbanMentionUsers.length);
        console.log('- Gefilterte Benutzer:', window.kanbanFilteredUsers.length);
        console.log('- Benutzer-Buttons:', document.querySelectorAll('.kanban-user-btn').length);
        console.log('- Dropdown vorhanden:', !!document.getElementById('kanban-mention-dropdown'));
        console.log('- Alle Benutzer:', window.kanbanMentionUsers);
        
        // API-Test
        console.log('🧪 Teste API-Endpunkt...');
        fetch('/api/users/search?q=Tho')
            .then(response => {
                console.log('📡 API Response Status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('✅ API Test erfolgreich:', data);
            })
            .catch(error => {
                console.error('❌ API Test fehlgeschlagen:', error);
            });
    };

    // Rich Text Editor Setup
    let quill;
    let editorInitializing = false;
    
    // Rich Text Editor initialisieren
    function initializeRichTextEditor() {
        // Verhindere mehrfache gleichzeitige Initialisierung
        if (editorInitializing) {
            console.log('⏭️ Rich Text Editor wird bereits initialisiert - überspringe');
            return;
        }
        
        console.log('📝 Initialisiere Rich Text Editor...');
        editorInitializing = true;
        
        // Prüfe ob Quill bereits geladen ist
        if (typeof Quill === 'undefined') {
            console.log('📦 Lade Quill.js...');
            editorInitializing = false;
            loadQuillJS();
            return;
        }
        
        const container = document.getElementById('rich-text-editor');
        if (!container) {
            console.error('❌ Rich Text Editor Container nicht gefunden');
            editorInitializing = false;
            return;
        }
        
        // Prüfe ob bereits ein funktionsfähiger Editor existiert
        if (quill && container.querySelector('.ql-editor')) {
            console.log('✅ Rich Text Editor bereits vorhanden und funktionsfähig');
            editorInitializing = false;
            return;
        }
        
        // Bereinige vorhandenen Quill-Inhalt im Container
        if (container.querySelector('.ql-toolbar') || container.querySelector('.ql-editor')) {
            console.log('🧹 Bereinige vorhandenen Quill-Inhalt...');
            container.innerHTML = '';
        }
        
        // Entferne bestehenden Editor falls vorhanden
        if (quill) {
            try {
                quill = null;
            } catch (e) {
                console.log('⚠️ Fehler beim Bereinigen des alten Editors:', e);
            }
        }
        
        // Quill mit vereinfachter Toolbar konfigurieren
        quill = new Quill(container, {
            theme: 'snow',
            placeholder: 'Notiz eingeben... Verwenden Sie @benutzername um Benutzer zu erwähnen.',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['blockquote'],
                    ['clean']
                ]
            }
        });
        
        // Event-Handler für Textänderungen
        quill.on('text-change', function(delta, oldDelta, source) {
            if (source === 'user') {
                const html = quill.root.innerHTML;
                const textarea = document.getElementById('kanban-note-content');
                if (textarea) {
                    textarea.value = html;
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                // @mention Handling im Rich Text Editor
                handleRichTextMentions();
            }
        });
        
        // Sperre nach erfolgreicher Initialisierung freigeben
        setTimeout(() => {
            editorInitializing = false;
            console.log('✅ Rich Text Editor initialisiert - Sperre freigegeben');
        }, 100);
    }
    
    // Quill.js dynamisch laden
    function loadQuillJS() {
        // CSS laden
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.quilljs.com/1.3.6/quill.snow.css';
        document.head.appendChild(link);
        
        // JavaScript laden
        const script = document.createElement('script');
        script.src = 'https://cdn.quilljs.com/1.3.6/quill.min.js';
        script.onload = function() {
            console.log('✅ Quill.js geladen');
            initializeRichTextEditor();
        };
        document.head.appendChild(script);
    }
    
    // @mentions im Rich Text Editor verarbeiten
    function handleRichTextMentions() {
        if (!quill) return;
        
        const text = quill.getText();
        const cursorPos = quill.getSelection()?.index || 0;
        
        // Suche nach @mentions
        let mentionStart = -1;
        for (let i = cursorPos - 1; i >= 0; i--) {
            if (text[i] === '@') {
                if (i === 0 || /\s/.test(text[i - 1])) {
                    mentionStart = i;
                    break;
                }
            } else if (/\s/.test(text[i])) {
                break;
            }
        }
        
        if (mentionStart !== -1) {
            const query = text.substring(mentionStart + 1, cursorPos);
            if (!/\s/.test(query) && query.length > 0) {
                console.log('🔍 Rich Text: Zeige Dropdown für:', query);
                showRichTextMentionDropdown(query, mentionStart);
                return;
            }
        }
        hideRichTextMentionDropdown();
    }
    
    // Dropdown für Rich Text Editor anzeigen
    function showRichTextMentionDropdown(query, mentionStart) {
        fetch(`/api/users/search?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(users => {
                if (users.length === 0) {
                    hideRichTextMentionDropdown();
                    return;
                }
                
                hideRichTextMentionDropdown();
                
                const dropdown = document.createElement('div');
                dropdown.id = 'rich-text-mention-dropdown';
                dropdown.style.cssText = `
                    position: absolute;
                    background: white;
                    border: 1px solid #d1d5db;
                    border-radius: 6px;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
                    max-height: 200px;
                    overflow-y: auto;
                    z-index: 99999;
                    min-width: 250px;
                    font-family: system-ui, -apple-system, sans-serif;
                `;
                
                users.slice(0, 5).forEach((user, index) => {
                    const item = document.createElement('div');
                    item.className = 'rich-text-mention-item';
                    item.style.cssText = `
                        padding: 12px 16px;
                        cursor: pointer;
                        border-bottom: 1px solid #f3f4f6;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        transition: background-color 0.15s ease;
                    `;
                    
                    if (index === 0) {
                        item.style.backgroundColor = '#eff6ff';
                        item.classList.add('selected');
                    }
                    
                    item.addEventListener('mouseenter', () => {
                        document.querySelectorAll('.rich-text-mention-item').forEach(i => {
                            i.style.backgroundColor = '';
                            i.classList.remove('selected');
                        });
                        item.style.backgroundColor = '#eff6ff';
                        item.classList.add('selected');
                    });
                    
                    const initial = user.name.charAt(0).toUpperCase();
                    item.innerHTML = `
                        <div style="width: 28px; height: 28px; border-radius: 50%; background-color: #3b82f6; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">${initial}</div>
                        <div style="font-weight: 600; font-size: 14px; color: #111827;">${user.name}</div>
                    `;
                    
                    item.addEventListener('click', () => {
                        selectRichTextMention(user.name, mentionStart);
                    });
                    
                    dropdown.appendChild(item);
                });
                
                const editorRect = document.getElementById('rich-text-editor').getBoundingClientRect();
                dropdown.style.left = editorRect.left + 'px';
                dropdown.style.top = (editorRect.bottom + 5) + 'px';
                
                document.body.appendChild(dropdown);
                
                console.log('✅ Rich Text: Dropdown angezeigt');
            })
            .catch(error => {
                console.error('❌ Rich Text: Fehler bei API-Suche:', error);
            });
    }
    
    // Dropdown für Rich Text Editor verstecken
    function hideRichTextMentionDropdown() {
        const dropdown = document.getElementById('rich-text-mention-dropdown');
        if (dropdown) {
            dropdown.remove();
        }
    }
    
    // @mention im Rich Text Editor auswählen
    function selectRichTextMention(username, mentionStart) {
        if (!quill) return;
        
        const text = quill.getText();
        const cursorPos = quill.getSelection()?.index || 0;
        const beforeMention = text.substring(0, mentionStart);
        const afterCursor = text.substring(cursorPos);
        
        // Entferne den aktuellen @mention Text
        const mentionLength = cursorPos - mentionStart;
        quill.deleteText(mentionStart, mentionLength);
        
        // Füge @mention als formatierter Text ein
        quill.insertText(mentionStart, `@${username} `, {
            'color': '#3b82f6',
            'bold': true
        });
        
        // Cursor nach dem Mention positionieren und Formatierung zurücksetzen
        const newCursorPos = mentionStart + username.length + 2;
        quill.setSelection(newCursorPos);
        
        // Formatierung für nachfolgenden Text zurücksetzen
        quill.format('color', false);
        quill.format('bold', false);
        
        hideRichTextMentionDropdown();
        
        console.log('✅ Rich Text: Mention eingefügt:', username);
    }
    
    // Rich Text Editor Inhalt speichern
    function saveRichTextNote() {
        if (!quill) {
            console.error('❌ Rich Text Editor nicht initialisiert');
            return;
        }
        
        const html = quill.root.innerHTML;
        const textarea = document.getElementById('kanban-note-content');
        
        if (textarea) {
            textarea.value = html;
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        // Livewire-Event über Event-Dispatch
        if (window.Livewire) {
            try {
                // Versuche über Livewire.dispatch
                window.Livewire.dispatch('addNote');
                console.log('✅ Rich Text: Notiz über Livewire.dispatch gespeichert');
                
                // Editor nach dem Speichern leeren aber nicht zerstören
                setTimeout(() => {
                    if (quill) {
                        quill.setText('');
                        console.log('🧹 Rich Text Editor geleert');
                    }
                }, 500);
                
            } catch (error) {
                console.error('❌ Fehler beim Livewire.dispatch:', error);
                
                // Fallback: Button-Klick simulieren
                const saveButton = document.querySelector('[wire\\:click="addNote"]');
                if (saveButton) {
                    console.log('🔄 Fallback: Simuliere Button-Klick');
                    saveButton.click();
                    console.log('✅ Rich Text: Notiz über Button-Klick gespeichert');
                    
                    // Editor nach dem Speichern leeren aber nicht zerstören
                    setTimeout(() => {
                        if (quill) {
                            quill.setText('');
                            console.log('🧹 Rich Text Editor geleert (Fallback)');
                        }
                    }, 500);
                    
                } else {
                    console.error('❌ Save-Button nicht gefunden');
                }
            }
        } else {
            console.error('❌ Livewire nicht verfügbar');
        }
    }
    
    // Benutzer-Mention über Button in Rich Text Editor einfügen
    function insertRichTextUserMention(username) {
        if (!quill) {
            console.error('❌ Rich Text Editor nicht initialisiert');
            return;
        }
        
        const selection = quill.getSelection();
        const cursorPos = selection ? selection.index : quill.getLength();
        
        // Füge @mention als formatierten Text ein
        quill.insertText(cursorPos, ` @${username} `, {
            'color': '#3b82f6',
            'bold': true
        });
        
        // Cursor nach dem Mention positionieren und Formatierung zurücksetzen
        const newCursorPos = cursorPos + username.length + 3;
        quill.setSelection(newCursorPos);
        
        // Formatierung für nachfolgenden Text zurücksetzen
        quill.format('color', false);
        quill.format('bold', false);
        
        console.log('✅ Rich Text: Button-Mention eingefügt:', username);
        
        // Visuelles Feedback
        const button = event.target.closest('.kanban-user-btn');
        if (button) {
            const originalBg = button.style.backgroundColor;
            button.style.backgroundColor = '#10b981';
            button.style.color = 'white';
            setTimeout(() => {
                button.style.backgroundColor = originalBg;
                button.style.color = '';
            }, 300);
        }
    }
    
    // Modal-Initialisierung beim Öffnen
    window.addEventListener('DOMContentLoaded', function() {
        // Sofort beim Laden initialisieren
        initializeKanbanMentionSystem();
        
        // Observer für Modal-Erkennung und Editor-Initialisierung
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            // Notes Modal geöffnet - Rich Text Editor initialisieren
                            const notesModal = node.querySelector('[aria-labelledby="notes-modal-title"]');
                            if (notesModal) {
                                console.log('📝 Notes Modal erkannt - starte vollständige Initialisierung');
                                
                                // Warte kurz auf DOM-Aufbau und initialisiere dann alles
                                setTimeout(() => {
                                    initializeKanbanMentionSystem();
                                    
                                    // Rich Text Editor initialisieren
                                    setTimeout(() => {
                                        initializeRichTextEditor();
                                        console.log('✅ Notes Modal: Editor initialisiert');
                                    }, 100);
                                }, 300);
                            }
                            
                            // Rich Text Editor Container direkt gefunden
                            const richTextEditor = node.querySelector('#rich-text-editor');
                            if (richTextEditor) {
                                console.log('📝 Rich Text Editor Container direkt erkannt');
                                setTimeout(() => {
                                    initializeRichTextEditor();
                                }, 100);
                            }
                            
                            // Benutzer-Button Container gefunden
                            const userButtonContainer = node.querySelector('#kanban-user-buttons');
                            if (userButtonContainer) {
                                setTimeout(() => {
                                    console.log('👥 Benutzer-Button Container erkannt');
                                    createKanbanUserButtons();
                                }, 100);
                            }
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    
    // Livewire Event Listener für Modal-Öffnung
    document.addEventListener('livewire:initialized', () => {
        // Initialisierung beim ersten Laden
        setTimeout(() => {
            initializeKanbanMentionSystem();
        }, 500);
        
        // Hook in Livewire's event system für Updates
        Livewire.hook('element.updated', (el, component) => {
            // Benutzer-Button Container aktualisiert
            const userButtonContainer = el.querySelector('#kanban-user-buttons');
            if (userButtonContainer) {
                setTimeout(() => {
                    console.log('👥 Livewire Update: Erstelle Benutzer-Buttons');
                    createKanbanUserButtons();
                }, 100);
            }
            
            // Rich Text Editor Container aktualisiert
            const richTextEditor = el.querySelector('#rich-text-editor');
            if (richTextEditor) {
                setTimeout(() => {
                    console.log('📝 Livewire Update: Rich Text Editor erkannt');
                    initializeRichTextEditor();
                }, 200);
            }
        });
        
        // Event-Listener für Modal-Öffnung
        Livewire.on('notesModalOpened', () => {
            console.log('📝 Notes Modal geöffnet - initialisiere Editor');
            setTimeout(() => {
                initializeKanbanMentionSystem();
                setTimeout(() => {
                    initializeRichTextEditor();
                }, 200);
            }, 500);
        });
        
        // Event-Listener für Note Added mit Editor-Reinitialisierung
        Livewire.on('noteAdded', (eventData) => {
            console.log('🎯 Note Added Event erkannt - bereite Editor-Reset vor');
            
            // Sperre zurücksetzen für Neuinitialisierung nach Livewire-Update
            editorInitializing = false;
            
            // Editor nur leeren, nicht zerstören
            setTimeout(() => {
                if (quill) {
                    quill.setText('');
                    console.log('🧹 Rich Text Editor nach Note Added geleert');
                }
                
                // Benutzer-Buttons wiederherstellen
                setTimeout(() => {
                    console.log('👥 Stelle Benutzer-Buttons nach Note Added wieder her');
                    createKanbanUserButtons();
                }, 200);
                
                // Nach Livewire-Update Editor neu initialisieren falls Container nicht mehr funktionsfähig ist
                setTimeout(() => {
                    const container = document.getElementById('rich-text-editor');
                    if (container && (!quill || !container.querySelector('.ql-editor'))) {
                        console.log('🔄 Rich Text Editor Container nach Livewire-Update neu initialisieren');
                        initializeRichTextEditor();
                    }
                    
                    // Sicherstellung dass Benutzer-Buttons vorhanden sind
                    const userButtonContainer = document.getElementById('kanban-user-buttons');
                    if (userButtonContainer && userButtonContainer.children.length === 0) {
                        console.log('🔄 Benutzer-Buttons Container leer - erstelle erneut');
                        createKanbanUserButtons();
                    }
                }, 1000);
            }, 500);
        });
    });
    
    // Benutzer-Buttons für Rich Text Editor überschreiben
    window.insertKanbanUserMention = function(username) {
        if (quill) {
            insertRichTextUserMention(username);
        } else {
            // Fallback für normales Textarea
            const textarea = document.getElementById('kanban-note-content');
            if (!textarea) return;
            
            const cursorPos = textarea.selectionStart;
            const currentText = textarea.value;
            const textBefore = currentText.substring(0, cursorPos);
            const textAfter = currentText.substring(cursorPos);
            const mentionText = ' @' + username + ' ';
            const newText = textBefore + mentionText + textAfter;
            
            textarea.value = newText;
            const newCursorPos = cursorPos + mentionText.length;
            textarea.setSelectionRange(newCursorPos, newCursorPos);
            textarea.focus();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };
    
    console.log('✅ Kanban @mention System geladen');
    console.log('� Verwende window.debugKanbanMentions() für Debug-Infos');
    
    // Livewire Console-Logging Event Listener
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('console-log', (eventData) => {
            console.log('🔧 Raw event data:', eventData);
            
            // Prüfe ob eventData ein Array ist (häufig bei Livewire Events)
            let data;
            if (Array.isArray(eventData)) {
                data = eventData[0];
            } else {
                data = eventData;
            }
            
            console.log('� Processed data:', data);
            
            if (!data || typeof data !== 'object') {
                console.error('❌ Invalid event data format:', data);
                return;
            }
            
            const { type, message, data: payload } = data;
            const style = {
                info: 'color: #3b82f6; font-weight: bold',
                success: 'color: #10b981; font-weight: bold',
                warning: 'color: #f59e0b; font-weight: bold',
                error: 'color: #ef4444; font-weight: bold'
            };
            
            if (message) {
                console.log(`%c${message}`, style[type] || style.info);
                if (payload) {
                    console.log('📊 Daten:', payload);
                }
            } else {
                console.error('❌ Message is missing in event data:', data);
            }
        });
    });
    </script>
</x-filament-panels::page>
