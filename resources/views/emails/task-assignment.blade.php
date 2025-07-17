<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aufgabe zugewiesen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .task-title {
            font-size: 20px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 15px;
        }
        .task-description {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            border-radius: 4px;
            margin: 15px 0;
            line-height: 1.6;
        }
        .meta-info {
            color: #6c757d;
            font-size: 14px;
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }
        .meta-info strong {
            color: #495057;
        }
        .priority-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin: 5px 0;
        }
        .priority-blocker {
            background-color: #dc2626;
            color: white;
        }
        .priority-urgent {
            background-color: #fecaca;
            color: #991b1b;
        }
        .priority-high {
            background-color: #fed7aa;
            color: #c2410c;
        }
        .priority-medium {
            background-color: #fef3c7;
            color: #a16207;
        }
        .priority-low {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin: 5px 0;
            background-color: #e5e7eb;
            color: #374151;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 12px;
        }
        .change-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>
            @if($isNewTask)
                Neue Aufgabe wurde Ihnen zugewiesen
            @else
                Aufgabe wurde geändert
            @endif
        </h2>
    </div>

    <div class="content">
        <p>Hallo {{ $user->name }},</p>
        
        @if($isNewTask)
            <p>Ihnen wurde eine neue Aufgabe von <strong>{{ $author->name }}</strong> zugewiesen:</p>
        @else
            <p>Eine Ihnen zugewiesene Aufgabe wurde von <strong>{{ $author->name }}</strong> geändert:</p>
        @endif
        
        <div class="task-title">{{ $task->title }}</div>
        
        @if($task->description)
            <div class="task-description">{!! nl2br(e($task->description)) !!}</div>
        @endif
        
        <div class="meta-info">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <strong>Aufgaben-Nr:</strong> {{ $task->task_number ?? 'Nicht verfügbar' }}<br>
                    <strong>Status:</strong> 
                    <span class="status-badge">
                        @switch($task->status)
                            @case('open') Offen @break
                            @case('in_progress') In Bearbeitung @break
                            @case('waiting_external') Warte auf Extern @break
                            @case('waiting_internal') Warte auf Intern @break
                            @case('completed') Abgeschlossen @break
                            @case('cancelled') Abgebrochen @break
                            @default {{ ucfirst($task->status) }}
                        @endswitch
                    </span><br>
                    <strong>Priorität:</strong> 
                    <span class="priority-badge priority-{{ $task->priority }}">
                        @switch($task->priority)
                            @case('blocker') Blocker @break
                            @case('urgent') Dringend @break
                            @case('high') Hoch @break
                            @case('medium') Mittel @break
                            @case('low') Niedrig @break
                            @default {{ ucfirst($task->priority) }}
                        @endswitch
                    </span>
                </div>
                <div>
                    @if($task->due_date)
                        <strong>Fälligkeitsdatum:</strong> {{ $task->due_date->format('d.m.Y') }}<br>
                    @endif
                    @if($task->taskType)
                        <strong>Aufgabentyp:</strong> {{ $task->taskType->name }}<br>
                    @endif
                    @if($task->owner)
                        <strong>Inhaber:</strong> {{ $task->owner->name }}<br>
                    @endif
                    <strong>Erstellt:</strong> {{ $task->created_at->format('d.m.Y H:i') }}
                </div>
            </div>
            
            @if(!$isNewTask && isset($changes) && !empty($changes))
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                    <strong>Geänderte Felder:</strong><br>
                    @foreach($changes as $field => $change)
                        <div style="margin: 5px 0;">
                            <strong>{{ $field }}:</strong> 
                            @if($change['old_value'])
                                <span style="text-decoration: line-through; color: #6b7280;">{{ $change['old_value'] }}</span> → 
                            @endif
                            <span class="change-highlight">{{ $change['new_value'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        
        @if(isset($taskUrl))
            <a href="{{ $taskUrl }}" class="button">Aufgabe öffnen</a>
        @endif
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch gesendet, weil Ihnen eine Aufgabe zugewiesen wurde oder sich eine Ihrer Aufgaben geändert hat.</p>
        <p>Bitte antworten Sie nicht auf diese E-Mail.</p>
    </div>
</body>
</html>
