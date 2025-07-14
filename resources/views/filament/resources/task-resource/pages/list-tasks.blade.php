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
                                    
                                    <!-- Task Content -->
                                    <div class="flex-grow space-y-2">
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

                                        <!-- Task Type and Priority -->
                                        <div class="flex items-center justify-between">
                                            @if($task->taskType)
                                                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs px-2 py-1 rounded">
                                                    {{ $task->taskType->name }}
                                                </span>
                                            @endif
                                            
                                            @if($task->priority === 'urgent')
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded">
                                                    Dringend
                                                </span>
                                            @elseif($task->priority === 'high')
                                                <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">
                                                    Hoch
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Assigned User -->
                                        @if($task->assignedUser)
                                            <div class="flex items-center text-xs text-gray-600 dark:text-gray-400">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{ $task->assignedUser->name }}
                                            </div>
                                        @endif
                                    </div>
                                    
                                     <!-- Edit Action -->
                                     <div class="flex-shrink-0">
                                         <button wire:click="mountTaskAction('{{ $task->id }}')" class="text-gray-400 hover:text-gray-600">
                                             <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                 <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                                 <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                             </svg>
                                         </button>
                                     </div>
                                 </div>
                            @endforeach
                            
                            <!-- Add Task Button -->
                            <button class="w-full p-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-gray-500 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors text-sm">
                                + Aufgabe hinzufügen
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Kein zusätzliches JavaScript nötig, alles wird von AlpineJS und Livewire gehandhabt -->

        <x-filament-actions::modals />

    @elseif($this->showStatistics)
        <!-- Statistik View (bestehend) -->
        @include('filament.resources.task-resource.pages.statistics')
    @else
        <!-- Standard Tabellen View -->
        {{ $this->table }}
    @endif
</x-filament-panels::page>
