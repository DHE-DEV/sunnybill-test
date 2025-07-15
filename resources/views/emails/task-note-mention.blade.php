<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neue Notiz - Aufgabe - {{ $task->title }}</title>
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
        .task-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .note-content {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #007bff;
            margin: 15px 0;
            font-style: italic;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            font-size: 12px;
            color: #6c757d;
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .mention {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header p-4">
        <h1>Sie wurden in einer Aufgaben-Notiz erwähnt</h1>
        <p>Hallo {{ $mentionedUser->name }},</p>
        <p>{{ $author->name }} hat Sie in einer neuen Notiz zu einer Aufgabe erwähnt.</p>
    </div>

    <div class="content">
        <div class="task-info p-4">
            <h3>Aufgabe: {{ $task->title }}</h3>
            <p><strong>Aufgabennummer:</strong> {{ $task->task_number }}</p>
            @if($task->taskType)
                <p><strong>Typ:</strong> {{ $task->taskType->name }}</p>
            @endif
            @if($task->priority)
                <p><strong>Priorität:</strong> 
                    @switch($task->priority)
                        @case('low') Niedrig @break
                        @case('medium') Mittel @break
                        @case('high') Hoch @break
                        @case('urgent') Dringend @break
                        @default {{ $task->priority }}
                    @endswitch
                </p>
            @endif
            @if($task->due_date)
                <p><strong>Fälligkeitsdatum:</strong> {{ $task->due_date->format('d.m.Y') }}</p>
            @endif
        </div>

        <h3>Notiz von {{ $author->name }}:</h3>
        <div class="note-content">
            {!! nl2br(preg_replace('/@(\w+)/', '<span class="mention">@$1</span>', e($note->content))) !!}
        </div>

        <p><strong>Erstellt am:</strong> {{ $note->created_at->format('d.m.Y H:i') }} Uhr</p>

        <div style="text-align: center;">
            <a href="{{ $taskUrl }}" class="button">
                Zur Notiz der Aufgabe
            </a>
        </div>
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch generiert, weil Sie in einer Aufgaben-Notiz erwähnt wurden.</p>
        <p>Wenn Sie auf "Zur Notiz der Aufgabe" klicken, werden Sie zu admin/tasks weitergeleitet und das Modal zur betreffenden Aufgabe mit den Notizen wird geöffnet.</p>
    </div>
</body>
</html>