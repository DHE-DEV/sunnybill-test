<div class="space-y-6">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Router Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-700">Name:</span>
                <span class="ml-2">{{ $router->name }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Modell:</span>
                <span class="ml-2">{{ $router->model }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">IP-Adresse:</span>
                <span class="ml-2">{{ $router->ip_address ?? 'Nicht gesetzt' }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-700">Webhook Port:</span>
                <span class="ml-2">{{ $router->webhook_port ?? '3000' }}</span>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Webhook URL</h3>
            <div class="bg-gray-100 p-3 rounded-md font-mono text-sm break-all">
                {{ $webhookUrl }}
            </div>
            <p class="text-sm text-gray-600 mt-2">
                Diese URL sollte in den Router-Einstellungen als Webhook-Ziel konfiguriert werden.
            </p>
        </div>

        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Test Command (cURL)</h3>
            <div class="bg-gray-900 text-green-400 p-4 rounded-md font-mono text-sm overflow-x-auto">
                <pre>{{ $curlCommand }}</pre>
            </div>
            <p class="text-sm text-gray-600 mt-2">
                Führen Sie diesen Befehl in der Kommandozeile aus, um einen Test-Webhook zu senden.
                <strong>Hinweis:</strong> Das <code>-k</code> Flag überspringt die SSL-Zertifikatsprüfung für lokale Entwicklungsumgebungen.
            </p>
        </div>

        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Alternative: PowerShell (Windows)</h3>
            <div class="bg-blue-900 text-blue-100 p-4 rounded-md font-mono text-sm overflow-x-auto">
                <pre>Invoke-RestMethod -Uri "{{ $webhookUrl }}" -Method POST -ContentType "application/json" -Body '{"operator": "Telekom.de", "signal_strength": -65, "network_type": "5G"}' -SkipCertificateCheck</pre>
            </div>
            <p class="text-sm text-gray-600 mt-2">
                PowerShell-Alternative für Windows-Systeme (PowerShell 6.0+ erforderlich für -SkipCertificateCheck).
            </p>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">
                        Hinweise zum Webhook-Test
                    </h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Stellen Sie sicher, dass der Router erreichbar ist</li>
                            <li>Der Router muss so konfiguriert sein, dass er Webhooks an diese URL sendet</li>
                            <li>Überprüfen Sie die Router-Logs auf eventuelle Fehler</li>
                            <li>Der Test-Befehl simuliert einen typischen Webhook vom Router</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
