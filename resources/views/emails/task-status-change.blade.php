<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aufgaben-Status geändert</title>
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
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin-bottom: 20px;
        }
        .task-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .status-change {
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin: 0 4px;
        }
        .status-open { background-color: #f3f4f6; color: #374151; }
        .status-in_progress { background-color: #dbeafe; color: #1e40af; }
        .status-waiting_external { background-color: #fef3c7; color: #a16207; }
        .status-waiting_internal { background-color: #e9d5ff; color: #7c3aed; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .arrow {
            display: inline-block;
            margin: 0 10px;
            font-size: 18px;
            color: #3b82f6;
        }
        .button {
            display: inline-block;
            padding: 12px 20px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Aufgaben-Status geändert</h1>
        <p>Hallo {{ $user->name }},</p>
        <p>eine Aufgabe, für die Sie als <strong>Inhaber</strong> verantwortlich sind, wurde von <strong>{{ $author->name }}</strong> zwischen den Spalten verschoben.</p>
    </div>

    <div class="task-info">
        <h2>📝 Aufgaben-Details</h2>
        <p><strong>Titel:</strong> {{ $task->title }}</p>
        
        @if($task->task_number)
            <p><strong>Aufgaben-Nr.:</strong> {{ $task->task_number }}</p>
        @endif
        
        @if($task->description)
            <p><strong>Beschreibung:</strong> {{ Str::limit($task->description, 200) }}</p>
        @endif
        
        @if($task->solarPlant)
            <p><strong>Solaranlage:</strong> {{ $task->solarPlant->name }}</p>
        @endif
        
        @if($task->due_date)
            <p><strong>Fälligkeitsdatum:</strong> {{ $task->due_date->format('d.m.Y') }}</p>
        @endif
        
        @if($task->assignedUser)
            <p><strong>Zugewiesen an:</strong> {{ $task->assignedUser->name }}</p>
        @endif
    </div>

    <div class="status-change">
        <h2>🔄 Status-Änderung</h2>
        <p><strong>{{ $author->name }}</strong> hat die Aufgabe verschoben:</p>
        
        <div style="text-align: center; margin: 20px 0;">
            <span class="status-badge status-{{ $oldStatus }}">{{ $oldStatusLabel }}</span>
            <span class="arrow">→</span>
            <span class="status-badge status-{{ $newStatus }}">{{ $newStatusLabel }}</span>
        </div>
        
        <p style="text-align: center; color: #6b7280; font-size: 14px;">
            Verschoben am {{ $changeDate->format('d.m.Y um H:i') }} Uhr
        </p>
    </div>

    <div style="text-align: center;">
        <a href="{{ $taskUrl }}" class="button">📋 Aufgabe anzeigen</a>
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch generiert, da Sie als Inhaber der Aufgabe eingetragen sind.</p>
        <p>Bei Fragen wenden Sie sich bitte an {{ $author->name }} ({{ $author->email }}).</p>
        <p style="margin-top: 20px;">
            <small>SunnyBill - Aufgabenverwaltung</small>
        </p>
    </div>
</body>
</html>
