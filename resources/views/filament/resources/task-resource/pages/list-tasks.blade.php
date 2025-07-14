<x-filament-panels::page>
    <!-- CSRF Token für AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @if($this->showBoard)
        <div class="bg-white dark:bg-gray-900 min-h-screen">
            <!-- Kanban Board -->
            <div class="overflow-x-auto">
                <div class="flex gap-6 p-6" style="min-width: 1800px;">
                    @foreach($this->getStatusColumns() as $status => $column)
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg flex-shrink-0" 
                             style="width: 280px;">
                        
                        <!-- Column Header -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm">
                                    {{ $column['label'] }}
                                </h3>
                                <span class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-medium px-2 py-1 rounded">
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
                                         <div class="blocker-banner text-white text-xs font-bold px-2 py-1 rounded-t-lg -mx-3 -mt-3 mb-3 text-center"
                                              style="background-color: #dc2626 !important; color: white !important;">
                                             BLOCKER
                                         </div>
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
                                                         {{ $this->getDueDateText($task->due_date) }}
                                                     </span>
                                                 @endif
                                             </div>

                                             <!-- Priority, Task Type, Owner and Assigned User Badges -->
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
                                
                                <!-- 2-Spalten Grid für alle restlichen Felder -->
                                <div style="display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 1rem !important;">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select wire:model="editStatus" class="block w-full border-gray-300 rounded-md shadow-sm">
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
                            <div class="mb-6 pt-4 bg-gray-50 rounded-lg">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Neue Notiz hinzufügen</label>
                                <textarea wire:model="newNoteContent"
                                          rows="3"
                                          class="block w-full border-gray-300 rounded-md shadow-sm resize-none"
                                          placeholder="Notiz eingeben..."></textarea>
                                <div class="flex justify-end mt-3">
                                    <button wire:click="addNote"
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
                                        <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ $note->content }}</div>
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
    @else
        <!-- List View (fallback) -->
        <div class="bg-white dark:bg-gray-900">
            <p class="p-6 text-gray-500">List View ist noch nicht implementiert. Verwenden Sie die Board-Ansicht.</p>
        </div>
    @endif
</x-filament-panels::page>
