<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Download</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: #f3f4f6;
        }
        .download-container {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .message {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="download-container">
        <div class="spinner"></div>
        <div class="message">Excel-Datei wird heruntergeladen...</div>
        <small style="color: #9ca3af;">Dieses Fenster schließt sich automatisch.</small>
    </div>

    <script>
        // Starte Download automatisch
        window.location.href = '{{ route("admin.download-excel-file") }}';

        // Schließe Fenster nach Download (oder nach 3 Sekunden)
        setTimeout(() => {
            window.close();
            // Falls window.close() nicht funktioniert (Hauptfenster), zurück zur vorherigen Seite
            if (!window.closed) {
                window.location.href = '{{ url()->previous() }}';
            }
        }, 3000);
    </script>
</body>
</html>
