<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Bulk Download</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                    Solaranlagen-Abrechnung
                    @if(!empty($downloads))
                        @php
                            // Sammle alle einzigartigen Perioden
                            $periods = collect($downloads)
                                ->pluck('period') 
                                ->filter()
                                ->unique()
                                ->sort()
                                ->values();
                        @endphp
                        @if($periods->count() == 1)
                            <span class="text-lg font-normal text-gray-600 ml-2">{{ $periods->first() }}</span>
                        @elseif($periods->count() > 1)
                            <span class="text-lg font-normal text-gray-600 ml-2">({{ $periods->implode(', ') }})</span>
                        @endif
                    @endif
                </h1>
                <a href="/admin/solar-plant-billings" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Zurück
                </a>
            </div>

            <!-- Status Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-green-800">Erfolgreich</h3>
                            <p class="text-green-600">{{ $successCount }} PDFs</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-red-800">Fehler</h3>
                            <p class="text-red-600">{{ $errorCount }} PDFs</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <i class="fas fa-folder text-blue-500 text-xl mr-3"></i>
                        <div>
                            <h3 class="font-semibold text-blue-800">Batch ID</h3>
                            <p class="text-blue-600 text-sm">{{ $batchId }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($downloads))
                <!-- Bulk Download Actions -->
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-blue-800 mb-1">Bulk-Download Optionen</h3>
                            <p class="text-sm text-blue-600">{{ count($downloads) }} PDFs bereit zum Download</p>
                        </div>
                        <div class="flex space-x-2">
                            <button id="downloadAllBtn" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-download mr-2"></i>Alle automatisch herunterladen
                            </button>
                            <button id="downloadZipBtn" 
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-file-archive mr-2"></i>Als ZIP herunterladen
                            </button>
                        </div>
                    </div>
                    
                    <!-- Progress Bar für automatische Downloads -->
                    <div id="downloadProgress" class="mt-4 hidden">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-blue-700">Downloads in Bearbeitung...</span>
                            <span id="progressText" class="text-sm text-blue-700">0 / {{ count($downloads) }}</span>
                        </div>
                        <div class="w-full bg-blue-200 rounded-full h-2">
                            <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Downloads List -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold mb-4">Verfügbare Downloads:</h2>
                    <div class="space-y-3">
                        @foreach($downloads as $download)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-800">
                                            @if(isset($download['customer_name']))
                                                {{ $download['customer_name'] }}
                                            @else
                                                PDF Download
                                            @endif
                                        </h3>
                                        @if(isset($download['filename']))
                                            <p class="text-xs text-gray-500">{{ $download['filename'] }}</p>
                                        @endif
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        @if(isset($download['status']) && $download['status'] === 'success')
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                                <i class="fas fa-check mr-1"></i>Erfolgreich
                                            </span>
                                            @if(isset($download['url']))
                                                <a href="{{ $download['url'] }}" 
                                                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors"
                                                   download>
                                                    <i class="fas fa-download mr-2"></i>Download
                                                </a>
                                            @endif
                                        @else
                                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">
                                                <i class="fas fa-times mr-1"></i>Fehler
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if(isset($download['error']))
                                    <div class="mt-2 text-sm text-red-600 bg-red-50 p-2 rounded">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        {{ $download['error'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Cleanup Button -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Die temporären Dateien werden nach dem Download automatisch bereinigt.
                        </div>
                        <form method="POST" action="{{ route('admin.cleanup-temp-pdfs') }}" 
                              onsubmit="return confirm('Sollen alle temporären PDF-Dateien gelöscht werden?')">
                            @csrf
                            <input type="hidden" name="batch" value="{{ $batchId }}">
                            <button type="submit" 
                                    class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-trash mr-2"></i>Temporäre Dateien löschen
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <!-- No Downloads Available -->
                <div class="text-center py-8">
                    <i class="fas fa-folder-open text-gray-400 text-6xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-600 mb-2">Keine Downloads verfügbar</h3>
                    <p class="text-gray-500 mb-4">Es konnten keine PDF-Dateien zum Download bereitgestellt werden.</p>
                    <a href="/admin/solar-plant-billings" 
                       class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition-colors">
                        Zurück zu den Abrechnungen
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Auto-cleanup script -->
    <script>
        // Download-URLs für JavaScript verfügbar machen
        const downloads = @json($downloads ?? []);
        
        // Automatische Downloads aller PDFs
        document.getElementById('downloadAllBtn').addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.innerHTML;
            const progressDiv = document.getElementById('downloadProgress');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            
            // Button deaktivieren und Anzeige ändern
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Downloads werden gestartet...';
            progressDiv.classList.remove('hidden');
            
            let completed = 0;
            const total = downloads.length;
            
            // Sequenziell durch alle Downloads gehen
            for (let i = 0; i < downloads.length; i++) {
                const download = downloads[i];
                
                if (download.url) {
                    try {
                        // Erstelle unsichtbaren Download-Link
                        const link = document.createElement('a');
                        link.href = download.url;
                        link.download = download.filename || `download_${i + 1}.pdf`;
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        
                        // Trigger Download
                        link.click();
                        document.body.removeChild(link);
                        
                        // Kurze Pause zwischen Downloads um Browser nicht zu überlasten
                        await new Promise(resolve => setTimeout(resolve, 500));
                        
                    } catch (error) {
                        console.error('Fehler beim Download:', error);
                    }
                }
                
                // Progress aktualisieren
                completed++;
                const percentage = (completed / total) * 100;
                progressBar.style.width = percentage + '%';
                progressText.textContent = `${completed} / ${total}`;
            }
            
            // Nach Abschluss
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check mr-2"></i>Downloads abgeschlossen';
                progressDiv.classList.add('hidden');
                
                // Nach 3 Sekunden zurück zum ursprünglichen Text
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 3000);
            }, 1000);
        });
        
        // ZIP-Download-Funktion
        document.getElementById('downloadZipBtn').addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>ZIP wird erstellt...';
            
            try {
                const zip = new JSZip();
                let completedDownloads = 0;
                
                // Alle PDFs zur ZIP hinzufügen
                for (const download of downloads) {
                    if (download.url) {
                        try {
                            const response = await fetch(download.url);
                            if (response.ok) {
                                const blob = await response.blob();
                                zip.file(download.filename || `download_${completedDownloads + 1}.pdf`, blob);
                                completedDownloads++;
                            }
                        } catch (error) {
                            console.error('Fehler beim Laden der PDF:', error);
                        }
                    }
                }
                
                if (completedDownloads > 0) {
                    // ZIP-Datei generieren und herunterladen
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>ZIP wird heruntergeladen...';
                    
                    const content = await zip.generateAsync({type: 'blob'});
                    const currentDate = new Date().toISOString().slice(0, 10);
                    const zipFilename = `Solaranlagen_Abrechnungen_${currentDate}.zip`;
                    
                    // ZIP-Download starten
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(content);
                    link.download = zipFilename;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                    
                    btn.innerHTML = '<i class="fas fa-check mr-2"></i>ZIP heruntergeladen';
                } else {
                    btn.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Keine Dateien gefunden';
                }
                
            } catch (error) {
                console.error('Fehler beim Erstellen der ZIP:', error);
                btn.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Fehler beim Erstellen';
            }
            
            // Nach 3 Sekunden zurück zum ursprünglichen Text
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }, 3000);
        });
        
        // Bereinige temporäre Dateien nach 5 Minuten automatisch
        setTimeout(function() {
            fetch('{{ route('admin.cleanup-temp-pdfs') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: 'batch={{ $batchId }}'
            });
        }, 300000); // 5 Minuten
    </script>
</body>
</html>
