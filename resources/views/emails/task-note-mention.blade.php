<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sie wurden in einer Notiz erwähnt</title>
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
            font-size: 18px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
        .note-content {
            background-color: transparent;
            padding: 15px;
            border-left: 4px solid #007bff;
            border-radius: 4px;
            margin: 15px 0;
            line-height: 1.6;
        }
        
        /* Rich Text Editor Styling für E-Mails */
        .note-content h1 {
            font-size: 1.5em;
            font-weight: bold;
            margin: 0.5em 0;
        }
        
        .note-content h2 {
            font-size: 1.3em;
            font-weight: bold;
            margin: 0.5em 0;
        }
        
        .note-content p {
            margin: 0.5em 0;
        }
        
        .note-content strong {
            font-weight: bold;
        }
        
        .note-content em {
            font-style: italic;
        }
        
        .note-content u {
            text-decoration: underline;
        }
        
        .note-content ol {
            margin: 0.5em 0;
            padding-left: 2em;
        }
        
        .note-content ul {
            margin: 0.5em 0;
            padding-left: 2em;
        }
        
        .note-content li {
            margin: 0.2em 0;
        }
        
        .note-content blockquote {
            margin: 0.5em 0;
            padding-left: 1em;
            border-left: 3px solid #ccc;
            color: #666;
            font-style: italic;
        }
        
        /* @mention Styling in E-Mails */
        .note-content [style*="color: #3b82f6"] {
            color: #007bff !important;
            font-weight: bold;
            background-color: #e3f2fd;
            padding: 2px 4px;
            border-radius: 3px;
        }
        .meta-info {
            color: #6c757d;
            font-size: 14px;
            margin-top: 10px;
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
    </style>
</head>
<body>
    <div class="header">
        <h2>Sie wurden in einer Aufgaben-Notiz erwähnt</h2>
    </div>

    <div class="content">
        <p>Hallo {{ $user->name }},</p>
        
        <p>Sie wurden von <strong>{{ $author->name }}</strong> in einer Notiz zur Aufgabe "<strong>{{ $task->title }}</strong>" erwähnt.</p>
        
        <div class="task-title">Aufgabe: {{ $task->title }}</div>
        
        <div class="note-content">{!! $note->content !!}</div>
        
        <div class="meta-info">
            <strong>Autor:</strong> {{ $author->name }}<br>
            <strong>Zeitpunkt:</strong> {{ $note->created_at->format('d.m.Y H:i') }}<br>
            @if($task->assignedUser)
                <strong>Zugewiesen an:</strong> {{ $task->assignedUser->name }}<br>
            @endif
            @if($task->owner)
                <strong>Inhaber:</strong> {{ $task->owner->name }}<br>
            @endif
            <strong>Status:</strong> {{ $task->status_label ?? ucfirst($task->status) }}<br>
            <strong>Priorität:</strong> {{ $task->priority_label ?? ucfirst($task->priority) }}
        </div>
        
        @if(isset($taskUrl))
            <a href="{{ $taskUrl }}" class="button">Aufgabe öffnen</a>
        @endif
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch gesendet, weil Sie in einer Aufgaben-Notiz erwähnt wurden.</p>
        <p>Bitte antworten Sie nicht auf diese E-Mail.</p>
    </div>
</body>
</html>
