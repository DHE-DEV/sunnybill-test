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
                             x-data="{
                                 status: '{{ $status }}',
                                 handleDrop(event) {
                                     const taskId = event.dataTransfer.getData('text/plain');
                                     const fromStatus = event.dataTransfer.getData('application/json');
                                     
                                     if (this.status !== fromStatus) {
                                         // Die Logik zur Neuordnung ist komplex, wir übergeben vorerst nur die notwendigen Daten
                                         const orderedIds = Array.from(event.currentTarget.querySelectorAll('.task-card')).map(el => el.dataset.taskId);
                                         @this.call('onTaskDropped', taskId, this.status, fromStatus, orderedIds);
                                     }
                                     
                                     event.currentTarget.classList.remove('bg-blue-100', 'dark:bg-gray-700');
                                 },
                                 handleDragOver(event) {
                                     event.preventDefault();
                                     event.currentTarget.classList.add('bg-blue-100', 'dark:bg-gray-700');
                                 },
                                 handleDragLeave(event) {
                                     event.currentTarget.classList.remove('bg-blue-100', 'dark:bg-gray-700');
                                 }
                             }"
                             class="p-4 space-y-3 min-h-[600px] drop-zone"
                             data-status="{{ $status }}"
                             x-on:drop.prevent="handleDrop($event)"
                             x-on:dragover.prevent="handleDragOver($event)"
                             x-on:dragleave.prevent="handleDragLeave($event)">

                            @foreach($column['tasks'] as $task)
                                <div wire:key="task-{{ $task->id }}"
                                     x-data="{
                                         handleDragStart(event) {
                                             event.dataTransfer.setData('text/plain', '{{ $task->id }}');
                                             event.dataTransfer.setData('application/json', '{{ $task->status }}');
                                             event.target.classList.add('opacity-50');
                                         },
                                         handleDragEnd(event) {
                                             event.target.classList.remove('opacity-50');
                                         }
                                     }"
                                     class="bg-white dark:bg-gray-700 rounded-lg p-3 shadow-sm border border-gray-200 dark:border-gray-600 cursor-move task-card hover:shadow-md transition-shadow"
                                     draggable="true"
                                     x-on:dragstart="handleDragStart($event)"
                                     x-on:dragend="handleDragEnd($event)"
                                     data-task-id="{{ $task->id }}">
                                    
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
                                            <div class="flex items-center justify-between text-xs">
                                                @if($task->task_number)
                                                    <span class="text-gray-500 dark:text-gray-400 font-mono">
                                                        {{ $task->task_number }}
                                                    </span>
                                                @endif
                                                
                                                @if($task->due_date)
                                                    <span class="text-xs @if($task->is_overdue) text-red-500 @elseif($task->is_due_today) text-orange-500 @else text-gray-500 dark:text-gray-400 @endif">
                                                        {{ $task->due_date->format('d.m.Y') }}
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Priority, Task Type, Owner and Assigned User Badges -->
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <!-- Priority Badge -->
                                                @if($task->priority === 'urgent')
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
                                        
                                        <!-- Edit Action -->
                                        <div class="flex-shrink-0">
                                            <button wire:click="editTaskById({{ $task->id }})"
                                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1"
                                                    title="Aufgabe bearbeiten">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
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

        <!-- Kein zusätzliches JavaScript nötig, alles wird von AlpineJS und Livewire gehandhabt -->

<!-- Edit Task Modal -->
@if($showEditModal)
            <div class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="z-index: 9999 !important;">
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

        <x-filament-actions::modals />

    @elseif($this->showStatistics)
        <!-- Statistik View (bestehend) -->
        @include('filament.resources.task-resource.pages.statistics')
    @else
        <!-- Standard Tabellen View -->
        {{ $this->table }}
    @endif
</x-filament-panels::page>
