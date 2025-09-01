<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk PDF Downloads - SunnyBill</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .content {
            padding: 30px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid #007bff;
        }

        .stat-card.success {
            border-left-color: #28a745;
        }

        .stat-card.error {
            border-left-color: #dc3545;
        }

        .stat-card.progress {
            border-left-color: #ffc107;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .progress-section {
            margin-bottom: 30px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .progress-title {
            font-size: 1.3rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .progress-counter {
            font-size: 1.1rem;
            color: #6c757d;
            font-weight: 500;
        }

        .progress-bar-container {
            background: #e9ecef;
            border-radius: 25px;
            height: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            position: relative;
        }

        .progress-bar {
            background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
            height: 100%;
            width: 0%;
            transition: width 0.5s ease;
            border-radius: 25px;
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, .2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, .2) 50%,
                rgba(255, 255, 255, .2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 30px 30px;
            animation: move 2s linear infinite;
        }

        @keyframes move {
            0% {
                background-position: 0 0;
            }
            100% {
                background-position: 30px 30px;
            }
        }

        .current-download {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            display: none;
        }

        .current-download.active {
            display: block;
        }

        .current-download-content {
            display: flex;
            align-items: center;
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .current-download-text {
            font-weight: 500;
            color: #856404;
        }

        .download-list {
            list-style: none;
        }

        .download-item {
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            border-left: 4px solid #e9ecef;
        }

        .download-item.completed {
            background: #d4edda;
            border-left-color: #28a745;
        }

        .download-item.downloading {
            background: #fff3cd;
            border-left-color: #ffc107;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .download-link {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #495057;
            transition: color 0.3s ease;
        }

        .download-link:hover {
            color: #007bff;
        }

        .download-filename {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .download-details {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .status-icon {
            float: right;
            margin-top: 5px;
            font-size: 1.2rem;
        }

        .status-icon.completed {
            color: #28a745;
        }

        .status-icon.downloading {
            color: #ffc107;
        }

        .completion-message {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            display: none;
        }

        .completion-message.show {
            display: block;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .completion-message h3 {
            margin-bottom: 10px;
            font-size: 1.4rem;
        }

        .return-button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: background 0.3s ease;
            font-weight: 500;
        }

        .return-button:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
        }

        .no-downloads {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .no-downloads-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.8rem;
            }
            
            .content {
                padding: 20px;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Bulk PDF Downloads</h1>
            <p>Ihre Abrechnungen werden verarbeitet und heruntergeladen</p>
        </div>
        
        <div class="content">
            <div class="stats">
                <div class="stat-card success">
                    <div class="stat-number" id="success-count">{{ $successCount }}</div>
                    <div class="stat-label">Erfolgreich</div>
                </div>
                <div class="stat-card error">
                    <div class="stat-number" id="error-count">{{ $errorCount }}</div>
                    <div class="stat-label">Fehler</div>
                </div>
                <div class="stat-card progress">
                    <div class="stat-number" id="downloaded-count">0</div>
                    <div class="stat-label">Heruntergeladen</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="total-count">{{ count($downloads ?? []) }}</div>
                    <div class="stat-label">Gesamt</div>
                </div>
            </div>

            @if(!empty($downloads))
                <div class="progress-section">
                    <div class="progress-header">
                        <div class="progress-title">Download-Fortschritt</div>
                        <div class="progress-counter" id="progress-counter">0 von {{ count($downloads) }}</div>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" id="progress-bar"></div>
                    </div>
                </div>

                <div class="current-download" id="current-download">
                    <div class="current-download-content">
                        <div class="spinner"></div>
                        <div class="current-download-text" id="current-download-text">
                            Wird geladen...
                        </div>
                    </div>
                </div>

                <div class="completion-message" id="completion-message">
                    <h3>🎉 Alle Downloads abgeschlossen!</h3>
                    <p>Alle {{ count($downloads) }} PDF-Dateien wurden erfolgreich heruntergeladen.</p>
                    <a href="/admin/solar-plant-billings" class="return-button">← Zurück zu den Abrechnungen</a>
                </div>

                <ul class="download-list">
                    @foreach($downloads as $index => $download)
                        <li class="download-item" id="download-item-{{ $index }}">
                            <a href="{{ $download['url'] }}" 
                               download="{{ $download['filename'] }}" 
                               class="download-link"
                               target="_blank">
                                <div class="download-filename">{{ $download['filename'] }}</div>
                                <div class="download-details">
                                    {{ $download['customer_name'] }} • {{ $download['period'] }}
                                </div>
                                <span class="status-icon" id="status-icon-{{ $index }}">⏳</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="no-downloads">
                    <div class="no-downloads-icon">📄</div>
                    <h3>Keine PDFs zum Download verfügbar</h3>
                    <p>Es wurden keine PDF-Dateien zum Download gefunden.</p>
                    <a href="/admin/solar-plant-billings" class="return-button" style="background: #007bff; margin-top: 20px;">← Zurück zu den Abrechnungen</a>
                </div>
            @endif
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const downloads = @json($downloads ?? []);
            
            if (downloads.length === 0) return;

            let downloadedCount = 0;
            const totalCount = downloads.length;
            
            // Update progress elements
            const progressBar = document.getElementById('progress-bar');
            const progressCounter = document.getElementById('progress-counter');
            const downloadedCountEl = document.getElementById('downloaded-count');
            const currentDownload = document.getElementById('current-download');
            const currentDownloadText = document.getElementById('current-download-text');
            const completionMessage = document.getElementById('completion-message');

            function updateProgress() {
                const percentage = Math.round((downloadedCount / totalCount) * 100);
                progressBar.style.width = percentage + '%';
                progressCounter.textContent = `${downloadedCount} von ${totalCount}`;
                downloadedCountEl.textContent = downloadedCount;
            }

            function markAsDownloading(index) {
                const item = document.getElementById(`download-item-${index}`);
                const icon = document.getElementById(`status-icon-${index}`);
                
                // Reset all items to normal state
                document.querySelectorAll('.download-item').forEach(el => {
                    el.classList.remove('downloading');
                });
                
                // Mark current as downloading
                item.classList.add('downloading');
                icon.textContent = '⬇️';
                icon.className = 'status-icon downloading';
                
                // Show current download info
                currentDownload.classList.add('active');
                currentDownloadText.textContent = `Wird heruntergeladen: ${downloads[index].filename}`;
                
                // Scroll to current item
                item.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            function markAsCompleted(index) {
                const item = document.getElementById(`download-item-${index}`);
                const icon = document.getElementById(`status-icon-${index}`);
                
                item.classList.remove('downloading');
                item.classList.add('completed');
                icon.textContent = '✅';
                icon.className = 'status-icon completed';
                
                downloadedCount++;
                updateProgress();
            }

            function completeAllDownloads() {
                currentDownload.classList.remove('active');
                completionMessage.classList.add('show');
                
                // Scroll to completion message
                completionMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Optional: Play success sound or show notification
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('Downloads abgeschlossen!', {
                        body: `Alle ${totalCount} PDF-Dateien wurden erfolgreich heruntergeladen.`,
                        icon: '/favicon.ico'
                    });
                }
            }

            // Request notification permission
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }

            // Start downloading process
            downloads.forEach((download, index) => {
                setTimeout(() => {
                    // Mark as currently downloading
                    markAsDownloading(index);
                    
                    // Create and trigger download
                    const link = document.createElement('a');
                    link.href = download.url;
                    link.download = download.filename;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    console.log(`Downloading (${index + 1}/${totalCount}):`, download.filename);
                    
                    // Mark as completed after a short delay
                    setTimeout(() => {
                        markAsCompleted(index);
                        
                        // Check if all downloads are completed
                        if (downloadedCount === totalCount) {
                            setTimeout(completeAllDownloads, 500);
                        }
                    }, 800); // Small delay to show the downloading state
                    
                }, index * 1200); // Stagger downloads by 1.2 seconds
            });
            
            // Initialize progress
            updateProgress();
        });
    </script>
</body>
</html>
