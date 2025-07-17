<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} API Dokumentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.10.3/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.10.3/favicon-16x16.png" sizes="16x16" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin: 0;
            background: #fafafa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .swagger-ui .topbar {
            background-color: #1976d2;
            padding: 10px 0;
        }
        .swagger-ui .topbar .download-url-wrapper {
            display: none;
        }
        .custom-header {
            background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .custom-header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .custom-header p {
            margin: 10px 0 0 0;
            font-size: 1.2em;
            opacity: 0.9;
        }
        .auth-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .auth-section h2 {
            color: #1976d2;
            margin-top: 0;
            border-bottom: 2px solid #1976d2;
            padding-bottom: 10px;
        }
        .auth-example {
            background: #f5f5f5;
            border-left: 4px solid #1976d2;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .auth-example code {
            background: #e8f5e8;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .permission-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .permission-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
        }
        .permission-item strong {
            color: #1976d2;
        }
        .quick-start {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            border-radius: 8px;
            padding: 20px;
            margin: 20px;
        }
        .quick-start h2 {
            color: #2e7d32;
            margin-top: 0;
        }
        .step {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid #4caf50;
        }
        .step-number {
            background: #4caf50;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            margin-top: 40px;
        }
        .footer p {
            margin: 0;
            color: #666;
        }
        .api-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px;
        }
        .api-info strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="custom-header">
        <h1>🌞 {{ config('app.name') }} API</h1>
        <p>Umfassende REST API für Aufgabenverwaltung, Kunden und Solaranlagen</p>
    </div>

    <div class="api-info">
        <strong>API-Endpunkt:</strong> {{ config('app.api_url') }}/api<br>
        <strong>Umgebung:</strong> {{ config('app.env') }}
    </div>

    <div class="auth-section">
        <h2>🔐 Authentifizierung</h2>
        <p>Die {{ config('app.name') }} API unterstützt zwei Authentifizierungsmethoden:</p>
        
        <div class="auth-example">
            <h3>1. App-Token (Empfohlen für externe Anwendungen)</h3>
            <p>Für mobile Apps und externe Integrationen verwenden Sie App-Token:</p>
            <code>Authorization: Bearer YOUR_APP_TOKEN</code>
            
            <h4>Token generieren:</h4>
            <ol>
                <li>Melden Sie sich in der Admin-Oberfläche an</li>
                <li>Gehen Sie zu "App-Token" → "Neues Token erstellen"</li>
                <li>Wählen Sie die erforderlichen Berechtigungen</li>
                <li>Kopieren Sie das generierte Token</li>
            </ol>
        </div>

        <div class="auth-example">
            <h3>2. Laravel Sanctum (Für Web-Anwendungen)</h3>
            <p>Für Web-basierte Anwendungen verwenden Sie Sanctum-Token:</p>
            <code>Authorization: Bearer YOUR_SANCTUM_TOKEN</code>
        </div>

        <h3>📋 Berechtigungen</h3>
        <p>App-Token haben granulare Berechtigungen:</p>
        <div class="permission-grid">
            <div class="permission-item">
                <strong>tasks:read</strong><br>
                Aufgaben anzeigen und durchsuchen
            </div>
            <div class="permission-item">
                <strong>tasks:create</strong><br>
                Neue Aufgaben erstellen
            </div>
            <div class="permission-item">
                <strong>tasks:update</strong><br>
                Aufgaben bearbeiten
            </div>
            <div class="permission-item">
                <strong>tasks:delete</strong><br>
                Aufgaben löschen
            </div>
            <div class="permission-item">
                <strong>tasks:status</strong><br>
                Aufgaben-Status ändern
            </div>
            <div class="permission-item">
                <strong>tasks:assign</strong><br>
                Aufgaben zuweisen
            </div>
            <div class="permission-item">
                <strong>tasks:time</strong><br>
                Zeiterfassung bearbeiten
            </div>
        </div>
    </div>

    <div class="quick-start">
        <h2>🚀 Schnellstart</h2>
        <div class="step">
            <span class="step-number">1</span>
            <strong>Token generieren:</strong> Erstellen Sie ein App-Token mit den erforderlichen Berechtigungen
        </div>
        <div class="step">
            <span class="step-number">2</span>
            <strong>API testen:</strong> Verwenden Sie den "Try it out" Button in der Dokumentation unten
        </div>
        <div class="step">
            <span class="step-number">3</span>
            <strong>Integration:</strong> Verwenden Sie die API in Ihrer Anwendung
        </div>
        
        <h3>Beispiel-Anfrage:</h3>
        <pre style="background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 6px; overflow-x: auto;">
<code>curl -X GET "{{ config('app.api_url') }}/api/app/tasks" \
  -H "Authorization: Bearer YOUR_APP_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json"</code></pre>
    </div>

    <div id="swagger-ui"></div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }} API - Alle Rechte vorbehalten</p>
    </div>

    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            // Begin Swagger UI call region
            const ui = SwaggerUIBundle({
                url: '{{ url('/api-docs/openapi.yaml') }}',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                docExpansion: "none",
                operationsSorter: "alpha",
                supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
                validatorUrl: null,
                tryItOutEnabled: true,
                requestInterceptor: function(request) {
                    // Add custom headers or modify requests here
                    return request;
                },
                responseInterceptor: function(response) {
                    // Handle responses here
                    return response;
                }
            });
            // End Swagger UI call region

            // Custom styling after UI loads
            setTimeout(function() {
                // Hide the top bar
                const topbar = document.querySelector('.topbar');
                if (topbar) {
                    topbar.style.display = 'none';
                }
                
                // Add custom CSS classes
                const infoSection = document.querySelector('.information-container');
                if (infoSection) {
                    infoSection.style.margin = '20px';
                    infoSection.style.padding = '20px';
                    infoSection.style.backgroundColor = '#fff';
                    infoSection.style.borderRadius = '8px';
                    infoSection.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                }
            }, 1000);
        };
    </script>
</body>
</html>
