<?php

namespace App\Console\Commands;

use App\Models\StorageSetting;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TestDigitalOceanStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:test-digitalocean {--cleanup : Nur Aufräumen ohne Test} {--debug : Ausführliche Debug-Ausgaben}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vollständiger Test der DigitalOcean Storage-Funktionalität mit PDF-Upload, Datenbank-Eintrag und Download-Test';

    private $testResults = [];
    private $testFiles = [];
    private $verbose = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->verbose = $this->option('debug');
        
        $this->info('🚀 DigitalOcean Storage Volltest gestartet');
        $this->info('=' . str_repeat('=', 60));
        $this->newLine();

        // Nur Aufräumen wenn gewünscht
        if ($this->option('cleanup')) {
            $this->cleanupTestFiles();
            return;
        }

        try {
            // Schritt 1: Admin-Einstellungen laden
            $this->testStep('Admin-Einstellungen laden', function() {
                return $this->loadAdminSettings();
            });

            // Schritt 2: Storage-Verbindung testen
            $this->testStep('Storage-Verbindung testen', function() {
                return $this->testStorageConnection();
            });

            // Schritt 3: Testverzeichnis erstellen
            $this->testStep('Testverzeichnis erstellen', function() {
                return $this->createTestDirectory();
            });

            // Schritt 4: PDF-Datei hochladen
            $this->testStep('PDF-Datei hochladen', function() {
                return $this->uploadTestPDF();
            });

            // Schritt 5: Metadaten extrahieren
            $this->testStep('Metadaten extrahieren', function() {
                return $this->extractFileMetadata();
            });

            // Schritt 6: Datenbank-Eintrag erstellen
            $this->testStep('Datenbank-Eintrag erstellen', function() {
                return $this->createDatabaseEntry();
            });

            // Schritt 7: Download testen
            $this->testStep('Download testen', function() {
                return $this->testFileDownload();
            });

            // Schritt 8: Aufräumen
            $this->testStep('Aufräumen', function() {
                return $this->cleanupTestFiles();
            });

            // Zusammenfassung
            $this->showTestSummary();

        } catch (\Exception $e) {
            $this->error('❌ Kritischer Fehler: ' . $e->getMessage());
            $this->error('Stack Trace: ' . $e->getTraceAsString());
            
            // Notfall-Aufräumen
            $this->warn('🧹 Führe Notfall-Aufräumen durch...');
            $this->cleanupTestFiles();
        }
    }

    private function testStep(string $stepName, callable $testFunction): void
    {
        $this->info("🔄 {$stepName}...");
        
        $startTime = microtime(true);
        
        try {
            $result = $testFunction();
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result['success']) {
                $this->info("✅ {$stepName} erfolgreich ({$duration}ms)");
                if (isset($result['message'])) {
                    $this->line("   → {$result['message']}");
                }
                if ($this->verbose && isset($result['debug'])) {
                    foreach ($result['debug'] as $debugLine) {
                        $this->line("   🐛 {$debugLine}");
                    }
                }
            } else {
                $this->error("❌ {$stepName} fehlgeschlagen ({$duration}ms)");
                $this->error("   → {$result['message']}");
                throw new \Exception("Test-Schritt '{$stepName}' fehlgeschlagen: {$result['message']}");
            }
            
            $this->testResults[$stepName] = [
                'success' => $result['success'],
                'duration' => $duration,
                'message' => $result['message'] ?? '',
                'data' => $result['data'] ?? null
            ];
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->error("❌ {$stepName} Ausnahme ({$duration}ms): " . $e->getMessage());
            
            $this->testResults[$stepName] = [
                'success' => false,
                'duration' => $duration,
                'message' => $e->getMessage(),
                'exception' => $e
            ];
            
            throw $e;
        }
        
        $this->newLine();
    }

    private function loadAdminSettings(): array
    {
        $debug = [];
        
        // Storage-Einstellungen laden
        $storageSetting = StorageSetting::current();
        
        if (!$storageSetting) {
            return [
                'success' => false,
                'message' => 'Keine aktive Storage-Einstellung gefunden. Bitte konfigurieren Sie die Storage-Einstellungen unter /admin/storage-settings'
            ];
        }
        
        $debug[] = "Storage-Driver: {$storageSetting->storage_driver}";
        $debug[] = "Storage-Konfiguration geladen: " . count($storageSetting->storage_config ?? []) . " Parameter";
        
        if ($storageSetting->storage_driver !== 'digitalocean') {
            return [
                'success' => false,
                'message' => "Aktueller Storage-Driver ist '{$storageSetting->storage_driver}', aber 'digitalocean' erwartet. Bitte ändern Sie die Einstellung unter /admin/storage-settings"
            ];
        }
        
        // Debug-Allocations prüfen (falls vorhanden)
        $debugAllocations = DB::table('storage_settings')
            ->where('is_active', true)
            ->first();
            
        if ($debugAllocations) {
            $debug[] = "Debug-Allocations: Letzte Berechnung am " . ($debugAllocations->last_storage_calculation ?? 'nie');
            $debug[] = "Aktuell verwendeter Speicher: " . $storageSetting->getFormattedStorageUsedAttribute();
        }
        
        // Konfiguration validieren
        $configErrors = $storageSetting->validateConfig();
        if (!empty($configErrors)) {
            return [
                'success' => false,
                'message' => 'Konfigurationsfehler: ' . implode(', ', $configErrors)
            ];
        }
        
        $debug[] = "Konfiguration validiert: ✅ Alle erforderlichen Parameter vorhanden";
        $debug[] = "Space: {$storageSetting->storage_config['bucket']}";
        $debug[] = "Region: {$storageSetting->storage_config['region']}";
        $debug[] = "Endpoint: {$storageSetting->storage_config['endpoint']}";
        
        return [
            'success' => true,
            'message' => "Storage-Einstellungen erfolgreich geladen (Driver: {$storageSetting->storage_driver})",
            'debug' => $debug,
            'data' => $storageSetting
        ];
    }

    private function testStorageConnection(): array
    {
        $debug = [];
        $storageSetting = StorageSetting::current();
        
        $debug[] = "Teste Verbindung zu DigitalOcean Spaces...";
        
        $connectionResult = $storageSetting->testConnection();
        
        if (!$connectionResult['success']) {
            return [
                'success' => false,
                'message' => $connectionResult['message'],
                'debug' => $debug
            ];
        }
        
        $debug[] = "Verbindungstest erfolgreich";
        $debug[] = "Netzwerk-Konnektivität: ✅";
        $debug[] = "Credentials: ✅";
        $debug[] = "Space-Zugriff: ✅";
        
        return [
            'success' => true,
            'message' => 'DigitalOcean Spaces Verbindung erfolgreich getestet',
            'debug' => $debug
        ];
    }

    private function createTestDirectory(): array
    {
        $debug = [];
        $disk = DocumentStorageService::getDisk();
        
        $testDir = 'test-digitalocean-' . date('Y-m-d-H-i-s');
        $this->testFiles[] = $testDir; // Für Cleanup merken
        
        $debug[] = "Erstelle Testverzeichnis: {$testDir}";
        
        try {
            // Verzeichnis durch Upload einer Marker-Datei erstellen
            $markerFile = $testDir . '/.marker';
            $disk->put($markerFile, 'Test-Verzeichnis erstellt am ' . now());
            $this->testFiles[] = $markerFile;
            
            $debug[] = "Marker-Datei erstellt: {$markerFile}";
            
            // Prüfen ob Verzeichnis existiert
            if ($disk->exists($markerFile)) {
                $debug[] = "Verzeichnis erfolgreich erstellt und verifiziert";
                
                return [
                    'success' => true,
                    'message' => "Testverzeichnis '{$testDir}' erfolgreich erstellt",
                    'debug' => $debug,
                    'data' => ['directory' => $testDir, 'marker' => $markerFile]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Verzeichnis wurde erstellt, aber Verifikation fehlgeschlagen',
                    'debug' => $debug
                ];
            }
            
        } catch (\Exception $e) {
            $debug[] = "Fehler beim Erstellen: " . $e->getMessage();
            
            return [
                'success' => false,
                'message' => 'Verzeichnis-Erstellung fehlgeschlagen: ' . $e->getMessage(),
                'debug' => $debug
            ];
        }
    }

    private function uploadTestPDF(): array
    {
        $debug = [];
        $disk = DocumentStorageService::getDisk();
        
        // Test-PDF-Inhalt erstellen (einfacher PDF-Header)
        $pdfContent = $this->createTestPDFContent();
        
        $testDir = $this->testResults['Testverzeichnis erstellen']['data']['directory'];
        $fileName = 'test-document-' . time() . '.pdf';
        $filePath = $testDir . '/' . $fileName;
        
        $this->testFiles[] = $filePath;
        
        $debug[] = "Erstelle Test-PDF: {$fileName}";
        $debug[] = "Vollständiger Pfad: {$filePath}";
        $debug[] = "PDF-Größe: " . strlen($pdfContent) . " Bytes";
        
        try {
            // PDF hochladen
            $uploadResult = $disk->put($filePath, $pdfContent);
            
            if (!$uploadResult) {
                return [
                    'success' => false,
                    'message' => 'Upload fehlgeschlagen - put() gab false zurück',
                    'debug' => $debug
                ];
            }
            
            $debug[] = "Upload erfolgreich";
            
            // Existenz prüfen
            if (!$disk->exists($filePath)) {
                return [
                    'success' => false,
                    'message' => 'Datei wurde hochgeladen, aber existiert nicht',
                    'debug' => $debug
                ];
            }
            
            $debug[] = "Datei-Existenz verifiziert";
            
            // Größe prüfen
            $uploadedSize = $disk->size($filePath);
            $debug[] = "Hochgeladene Größe: {$uploadedSize} Bytes";
            
            if ($uploadedSize !== strlen($pdfContent)) {
                $debug[] = "⚠️ Größen-Mismatch: Erwartet " . strlen($pdfContent) . ", erhalten {$uploadedSize}";
            } else {
                $debug[] = "Größe stimmt überein: ✅";
            }
            
            // MIME-Type prüfen (falls verfügbar)
            try {
                $mimeType = $disk->mimeType($filePath);
                $debug[] = "MIME-Type: {$mimeType}";
            } catch (\Exception $e) {
                $debug[] = "MIME-Type nicht verfügbar: " . $e->getMessage();
            }
            
            return [
                'success' => true,
                'message' => "PDF-Datei '{$fileName}' erfolgreich hochgeladen ({$uploadedSize} Bytes)",
                'debug' => $debug,
                'data' => [
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'size' => $uploadedSize,
                    'content' => $pdfContent
                ]
            ];
            
        } catch (\Exception $e) {
            $debug[] = "Upload-Fehler: " . $e->getMessage();
            
            return [
                'success' => false,
                'message' => 'PDF-Upload fehlgeschlagen: ' . $e->getMessage(),
                'debug' => $debug
            ];
        }
    }

    private function extractFileMetadata(): array
    {
        $debug = [];
        $uploadData = $this->testResults['PDF-Datei hochladen']['data'];
        $filePath = $uploadData['file_path'];
        
        $debug[] = "Extrahiere Metadaten für: {$filePath}";
        
        try {
            // Benutzer für uploaded_by simulieren
            $user = User::first();
            if ($user) {
                Auth::login($user);
                $debug[] = "Benutzer für Test eingeloggt: {$user->name} (ID: {$user->id})";
            } else {
                $debug[] = "⚠️ Kein Benutzer gefunden - erstelle Test-Benutzer";
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                ]);
                Auth::login($user);
                $debug[] = "Test-Benutzer erstellt und eingeloggt: {$user->id}";
            }
            
            // Metadaten extrahieren
            $metadata = DocumentStorageService::extractFileMetadata($filePath);
            
            $debug[] = "Metadaten erfolgreich extrahiert:";
            foreach ($metadata as $key => $value) {
                $debug[] = "  {$key}: {$value}";
            }
            
            // Validierung der kritischen Felder
            $requiredFields = ['disk', 'path', 'original_name', 'size', 'mime_type', 'uploaded_by'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($metadata[$field]) || $metadata[$field] === null) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                return [
                    'success' => false,
                    'message' => 'Fehlende Metadaten-Felder: ' . implode(', ', $missingFields),
                    'debug' => $debug
                ];
            }
            
            $debug[] = "✅ Alle erforderlichen Metadaten-Felder vorhanden";
            
            return [
                'success' => true,
                'message' => 'Metadaten erfolgreich extrahiert und validiert',
                'debug' => $debug,
                'data' => $metadata
            ];
            
        } catch (\Exception $e) {
            $debug[] = "Metadaten-Extraktion fehlgeschlagen: " . $e->getMessage();
            
            return [
                'success' => false,
                'message' => 'Metadaten-Extraktion fehlgeschlagen: ' . $e->getMessage(),
                'debug' => $debug
            ];
        }
    }

    private function createDatabaseEntry(): array
    {
        $debug = [];
        $metadata = $this->testResults['Metadaten extrahieren']['data'];
        $uploadData = $this->testResults['PDF-Datei hochladen']['data'];
        
        $debug[] = "Erstelle Datenbank-Eintrag für Dokument...";
        
        try {
            // Dokument-Daten vorbereiten
            $documentData = [
                'name' => 'Test PDF Dokument - DigitalOcean Storage Test',
                'original_name' => $metadata['original_name'],
                'path' => $metadata['path'],
                'disk' => $metadata['disk'],
                'mime_type' => $metadata['mime_type'],
                'size' => $metadata['size'],
                'category' => 'technical',
                'description' => 'Automatisch erstelltes Test-Dokument für DigitalOcean Storage-Test am ' . now(),
                'uploaded_by' => $metadata['uploaded_by'],
                'is_favorite' => false,
                'documentable_type' => 'App\\Models\\User', // Dummy-Wert für Test
                'documentable_id' => $metadata['uploaded_by'], // Verknüpfe mit User
            ];
            
            $debug[] = "Dokument-Daten vorbereitet:";
            foreach ($documentData as $key => $value) {
                $debug[] = "  {$key}: {$value}";
            }
            
            // Dokument in Datenbank erstellen
            $document = Document::create($documentData);
            
            $debug[] = "Dokument erfolgreich erstellt mit ID: {$document->id}";
            
            // Dokument aus Datenbank laden zur Verifikation
            $loadedDocument = Document::find($document->id);
            
            if (!$loadedDocument) {
                return [
                    'success' => false,
                    'message' => 'Dokument wurde erstellt, aber kann nicht aus Datenbank geladen werden',
                    'debug' => $debug
                ];
            }
            
            $debug[] = "Dokument erfolgreich aus Datenbank geladen";
            $debug[] = "Verifizierte Daten:";
            $debug[] = "  ID: {$loadedDocument->id}";
            $debug[] = "  Name: {$loadedDocument->name}";
            $debug[] = "  Pfad: {$loadedDocument->path}";
            $debug[] = "  Disk: {$loadedDocument->disk}";
            $debug[] = "  Größe: {$loadedDocument->formatted_size}";
            $debug[] = "  MIME-Type: {$loadedDocument->mime_type}";
            $debug[] = "  Hochgeladen von: " . ($loadedDocument->uploadedBy ? $loadedDocument->uploadedBy->name : 'Unbekannt');
            
            return [
                'success' => true,
                'message' => "Dokument erfolgreich in Datenbank erstellt (ID: {$document->id})",
                'debug' => $debug,
                'data' => $loadedDocument
            ];
            
        } catch (\Exception $e) {
            $debug[] = "Datenbank-Fehler: " . $e->getMessage();
            
            return [
                'success' => false,
                'message' => 'Datenbank-Eintrag fehlgeschlagen: ' . $e->getMessage(),
                'debug' => $debug
            ];
        }
    }

    private function testFileDownload(): array
    {
        $debug = [];
        $document = $this->testResults['Datenbank-Eintrag erstellen']['data'];
        $originalContent = $this->testResults['PDF-Datei hochladen']['data']['content'];
        
        $debug[] = "Teste Download von Dokument ID: {$document->id}";
        $debug[] = "Datei-Pfad: {$document->path}";
        $debug[] = "Disk: {$document->disk}";
        
        try {
            $disk = DocumentStorageService::getDisk();
            
            // Existenz prüfen
            if (!$disk->exists($document->path)) {
                return [
                    'success' => false,
                    'message' => 'Datei existiert nicht mehr auf dem Storage',
                    'debug' => $debug
                ];
            }
            
            $debug[] = "Datei existiert auf Storage: ✅";
            
            // Datei herunterladen
            $downloadedContent = $disk->get($document->path);
            
            if ($downloadedContent === false || $downloadedContent === null) {
                return [
                    'success' => false,
                    'message' => 'Download fehlgeschlagen - get() gab false/null zurück',
                    'debug' => $debug
                ];
            }
            
            $debug[] = "Download erfolgreich";
            $debug[] = "Heruntergeladene Größe: " . strlen($downloadedContent) . " Bytes";
            $debug[] = "Erwartete Größe: " . strlen($originalContent) . " Bytes";
            
            // Inhalt vergleichen
            if ($downloadedContent === $originalContent) {
                $debug[] = "Inhalt stimmt überein: ✅";
                
                // Zusätzliche Verifikation: Dateigröße aus Datenbank vs. tatsächliche Größe
                if ($document->size === strlen($downloadedContent)) {
                    $debug[] = "Datenbank-Größe stimmt überein: ✅";
                } else {
                    $debug[] = "⚠️ Datenbank-Größe Mismatch: DB={$document->size}, Tatsächlich=" . strlen($downloadedContent);
                }
                
                // MIME-Type Verifikation
                if ($document->mime_type === 'application/pdf') {
                    $debug[] = "MIME-Type korrekt: ✅";
                } else {
                    $debug[] = "⚠️ MIME-Type: Erwartet 'application/pdf', erhalten '{$document->mime_type}'";
                }
                
                return [
                    'success' => true,
                    'message' => 'Download erfolgreich - Datei-Integrität verifiziert',
                    'debug' => $debug,
                    'data' => [
                        'downloaded_size' => strlen($downloadedContent),
                        'content_matches' => true,
                        'document' => $document
                    ]
                ];
                
            } else {
                $debug[] = "❌ Inhalt stimmt nicht überein!";
                $debug[] = "Original MD5: " . md5($originalContent);
                $debug[] = "Download MD5: " . md5($downloadedContent);
                
                return [
                    'success' => false,
                    'message' => 'Download erfolgreich, aber Datei-Inhalt stimmt nicht überein',
                    'debug' => $debug
                ];
            }
            
        } catch (\Exception $e) {
            $debug[] = "Download-Fehler: " . $e->getMessage();
            
            return [
                'success' => false,
                'message' => 'Download fehlgeschlagen: ' . $e->getMessage(),
                'debug' => $debug
            ];
        }
    }

    private function cleanupTestFiles(): array
    {
        $debug = [];
        $disk = DocumentStorageService::getDisk();
        $cleanupCount = 0;
        $errors = [];
        
        $debug[] = "Starte Aufräumen von " . count($this->testFiles) . " Test-Dateien...";
        
        // Test-Dateien löschen
        foreach ($this->testFiles as $filePath) {
            try {
                if ($disk->exists($filePath)) {
                    $disk->delete($filePath);
                    $debug[] = "Gelöscht: {$filePath}";
                    $cleanupCount++;
                } else {
                    $debug[] = "Bereits gelöscht: {$filePath}";
                }
            } catch (\Exception $e) {
                $error = "Fehler beim Löschen von {$filePath}: " . $e->getMessage();
                $debug[] = $error;
                $errors[] = $error;
            }
        }
        
        // Test-Dokument aus Datenbank löschen
        if (isset($this->testResults['Datenbank-Eintrag erstellen']['data'])) {
            try {
                $document = $this->testResults['Datenbank-Eintrag erstellen']['data'];
                $document->forceDelete(); // Hard delete für Test-Dokument
                $debug[] = "Test-Dokument aus Datenbank gelöscht (ID: {$document->id})";
                $cleanupCount++;
            } catch (\Exception $e) {
                $error = "Fehler beim Löschen des Datenbank-Eintrags: " . $e->getMessage();
                $debug[] = $error;
                $errors[] = $error;
            }
        }
        
        if (empty($errors)) {
            return [
                'success' => true,
                'message' => "Aufräumen erfolgreich - {$cleanupCount} Elemente gelöscht",
                'debug' => $debug
            ];
        } else {
            return [
                'success' => false,
                'message' => "Aufräumen teilweise fehlgeschlagen - {$cleanupCount} Elemente gelöscht, " . count($errors) . " Fehler",
                'debug' => $debug
            ];
        }
    }

    private function createTestPDFContent(): string
    {
        // Einfacher PDF-Header für Test-Zwecke
        $pdfContent = "%PDF-1.4\n";
        $pdfContent .= "1 0 obj\n";
        $pdfContent .= "<<\n";
        $pdfContent .= "/Type /Catalog\n";
        $pdfContent .= "/Pages 2 0 R\n";
        $pdfContent .= ">>\n";
        $pdfContent .= "endobj\n\n";
        
        $pdfContent .= "2 0 obj\n";
        $pdfContent .= "<<\n";
        $pdfContent .= "/Type /Pages\n";
        $pdfContent .= "/Kids [3 0 R]\n";
        $pdfContent .= "/Count 1\n";
        $pdfContent .= ">>\n";
        $pdfContent .= "endobj\n\n";
        
        $pdfContent .= "3 0 obj\n";
        $pdfContent .= "<<\n";
        $pdfContent .= "/Type /Page\n";
        $pdfContent .= "/Parent 2 0 R\n";
        $pdfContent .= "/MediaBox [0 0 612 792]\n";
        $pdfContent .= "/Contents 4 0 R\n";
        $pdfContent .= ">>\n";
        $pdfContent .= "endobj\n\n";
        
        $content = "BT\n/F1 12 Tf\n100 700 Td\n(DigitalOcean Storage Test PDF - " . now() . ") Tj\nET";
        $pdfContent .= "4 0 obj\n";
        $pdfContent .= "<<\n";
        $pdfContent .= "/Length " . strlen($content) . "\n";
        $pdfContent .= ">>\n";
        $pdfContent .= "stream\n";
        $pdfContent .= $content . "\n";
        $pdfContent .= "endstream\n";
        $pdfContent .= "endobj\n\n";
        
        $pdfContent .= "xref\n";
        $pdfContent .= "0 5\n";
        $pdfContent .= "0000000000 65535 f \n";
        $pdfContent .= "0000000009 00000 n \n";
        $pdfContent .= "0000000074 00000 n \n";
        $pdfContent .= "0000000131 00000 n \n";
        $pdfContent .= "0000000225 00000 n \n";
        $pdfContent .= "trailer\n";
        $pdfContent .= "<<\n";
        $pdfContent .= "/Size 5\n";
        $pdfContent .= "/Root 1 0 R\n";
        $pdfContent .= ">>\n";
        $pdfContent .= "startxref\n";
        $pdfContent .= strlen($pdfContent) + 20 . "\n";
        $pdfContent .= "%%EOF\n";
        
        return $pdfContent;
    }

    private function showTestSummary(): void
    {
        $this->newLine();
        $this->info('📊 TEST-ZUSAMMENFASSUNG');
        $this->info('=' . str_repeat('=', 60));
        
        $totalSteps = count($this->testResults);
        $successfulSteps = 0;
        $totalDuration = 0;
        
        foreach ($this->testResults as $stepName => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $duration = $result['duration'];
            $totalDuration += $duration;
            
            if ($result['success']) {
                $successfulSteps++;
            }
            
            $this->line("{$status} {$stepName} ({$duration}ms)");
            if (!$result['success']) {
                $this->line("   → {$result['message']}");
            }
        }
        
        $this->newLine();
        $this->info("Erfolgreiche Schritte: {$successfulSteps}/{$totalSteps}");
        $this->info("Gesamtdauer: {$totalDuration}ms");
        
        if ($successfulSteps === $totalSteps) {
            $this->info('🎉 ALLE TESTS ERFOLGREICH!');
            $this->info('✅ DigitalOcean Storage funktioniert vollständig');
            $this->info('✅ Upload, Metadaten-Extraktion, Datenbank-Eintrag und Download funktionieren');
            $this->info('✅ Das System ist bereit für den Produktiveinsatz');
        } else {
            $this->error('❌ EINIGE TESTS FEHLGESCHLAGEN');
            $this->error('⚠️ Bitte beheben Sie die Probleme vor dem Produktiveinsatz');
        }
        
        $this->newLine();
        $this->info('🔗 Nützliche Links:');
        $this->line('• Storage-Einstellungen: http://sunnybill-test.test/admin/storage-settings');
        $this->line('• Debug-Allocations: http://sunnybill-test.test/admin/debug-allocations');
        $this->line('• Laravel Logs: storage/logs/laravel.log');
        
        $this->newLine();
        $this->info('💡 Tipps:');
        $this->line('• Verwenden Sie --debug für detaillierte Debug-Ausgaben');
        $this->line('• Verwenden Sie --cleanup um nur aufzuräumen');
        $this->line('• Bei Problemen prüfen Sie die Laravel-Logs');
    }
}