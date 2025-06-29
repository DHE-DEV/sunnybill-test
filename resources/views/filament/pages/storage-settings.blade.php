<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center space-x-3 mb-4">
                <x-heroicon-o-cloud-arrow-up class="w-8 h-8 text-primary-600" />
                <div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                        Speicher-Einstellungen
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Konfigurieren Sie den Speicher für Ihre Dokumente
                    </p>
                </div>
            </div>

            <form wire:submit="save">
                {{ $this->form }}
            </form>
        </div>

        @if($this->data['storage_driver'] === 'digitalocean')
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                    <div class="text-sm">
                        <h3 class="font-medium text-blue-900 dark:text-blue-100 mb-1">
                            DigitalOcean Spaces Konfiguration
                        </h3>
                        <div class="text-blue-700 dark:text-blue-300 space-y-1">
                            <p>• Erstellen Sie einen Space in Ihrem DigitalOcean Account</p>
                            <p>• Generieren Sie API-Schlüssel unter "API" → "Spaces Keys"</p>
                            <p>• Der Endpoint folgt dem Format: https://[region].digitaloceanspaces.com</p>
                            <p>• Optional: Aktivieren Sie CDN für bessere Performance</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($this->data['storage_driver'] === 's3')
            <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-orange-600 dark:text-orange-400 mt-0.5" />
                    <div class="text-sm">
                        <h3 class="font-medium text-orange-900 dark:text-orange-100 mb-1">
                            Amazon S3 Konfiguration
                        </h3>
                        <div class="text-orange-700 dark:text-orange-300 space-y-1">
                            <p>• Erstellen Sie einen S3 Bucket in Ihrem AWS Account</p>
                            <p>• Erstellen Sie IAM-Benutzer mit S3-Berechtigung</p>
                            <p>• Konfigurieren Sie die Bucket-Policy für den Zugriff</p>
                            <p>• Wählen Sie die passende Region für Ihre Anwendung</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                Speicher-Vergleich
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div class="bg-white dark:bg-gray-700 p-3 rounded border">
                    <h4 class="font-medium text-gray-900 dark:text-white">Lokaler Speicher</h4>
                    <ul class="mt-1 text-gray-600 dark:text-gray-300 space-y-1">
                        <li>✓ Einfache Einrichtung</li>
                        <li>✓ Keine zusätzlichen Kosten</li>
                        <li>✗ Begrenzt auf Server-Speicher</li>
                        <li>✗ Keine automatischen Backups</li>
                    </ul>
                </div>
                <div class="bg-white dark:bg-gray-700 p-3 rounded border">
                    <h4 class="font-medium text-gray-900 dark:text-white">Amazon S3</h4>
                    <ul class="mt-1 text-gray-600 dark:text-gray-300 space-y-1">
                        <li>✓ Unbegrenzter Speicher</li>
                        <li>✓ Hohe Verfügbarkeit</li>
                        <li>✓ Automatische Backups</li>
                        <li>✗ Komplexere Einrichtung</li>
                    </ul>
                </div>
                <div class="bg-white dark:bg-gray-700 p-3 rounded border">
                    <h4 class="font-medium text-gray-900 dark:text-white">DigitalOcean Spaces</h4>
                    <ul class="mt-1 text-gray-600 dark:text-gray-300 space-y-1">
                        <li>✓ S3-kompatibel</li>
                        <li>✓ Einfache Preisgestaltung</li>
                        <li>✓ Integriertes CDN</li>
                        <li>✓ Gute Performance</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>