<?php

require_once 'vendor/autoload.php';

// Laravel Bootstrap
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Gmail E-Mail Logs Viewer ===\n\n";

$logFile = storage_path('logs/laravel.log');

if (!file_exists($logFile)) {
    echo "❌ Log-Datei nicht gefunden: {$logFile}\n";
    exit(1);
}

echo "📁 Log-Datei: {$logFile}\n";
echo "📊 Dateigröße: " . number_format(filesize($logFile) / 1024 / 1024, 2) . " MB\n\n";

// Optionen anzeigen
echo "Wählen Sie eine Option:\n";
echo "1. Alle Gmail Label Logs anzeigen\n";
echo "2. Nur die letzten 10 Gmail Label Logs\n";
echo "3. Nur die letzten 20 Gmail Label Logs\n";
echo "4. Gmail Sync Logs anzeigen\n";
echo "5. Gmail Warnungen anzeigen\n";
echo "6. Alle Gmail Logs (Labels + Sync + Warnungen)\n";
echo "7. Log-Datei in Echtzeit verfolgen (Ctrl+C zum Beenden)\n";
echo "\nGeben Sie eine Zahl ein (1-7): ";

$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));
fclose($handle);

echo "\n" . str_repeat("=", 80) . "\n\n";

switch ($choice) {
    case '1':
        showGmailLabelLogs($logFile);
        break;
    case '2':
        showGmailLabelLogs($logFile, 10);
        break;
    case '3':
        showGmailLabelLogs($logFile, 20);
        break;
    case '4':
        showGmailSyncLogs($logFile);
        break;
    case '5':
        showGmailWarnings($logFile);
        break;
    case '6':
        showAllGmailLogs($logFile);
        break;
    case '7':
        followLogs($logFile);
        break;
    default:
        echo "❌ Ungültige Auswahl. Bitte wählen Sie 1-7.\n";
        exit(1);
}

function showGmailLabelLogs($logFile, $limit = null) {
    echo "🏷️  Gmail Label Logs:\n\n";
    
    $command = 'findstr "Gmail Email Labels" "' . str_replace('/', '\\', $logFile) . '"';
    if ($limit) {
        $command = 'powershell "Get-Content \\"' . str_replace('/', '\\', $logFile) . '\\" | Select-String \\"Gmail Email Labels\\" | Select-Object -Last ' . $limit . '"';
    }
    
    $output = shell_exec($command);
    
    if (empty($output)) {
        echo "ℹ️  Keine Gmail Label Logs gefunden.\n";
        echo "💡 Führen Sie eine Gmail-Synchronisation durch, um Logs zu generieren.\n";
        return;
    }
    
    $lines = explode("\n", trim($output));
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        
        // Parse JSON context if possible
        if (preg_match('/\{.*\}/', $line, $matches)) {
            $jsonData = json_decode($matches[0], true);
            if ($jsonData) {
                echo "📧 Gmail ID: " . ($jsonData['gmail_id'] ?? 'N/A') . "\n";
                echo "📝 Subject: " . ($jsonData['subject'] ?? 'N/A') . "\n";
                echo "👤 From: " . ($jsonData['from'] ?? 'N/A') . "\n";
                echo "🏷️  Labels (" . ($jsonData['total_labels'] ?? 0) . "): " . implode(', ', $jsonData['all_labels'] ?? []) . "\n";
                echo "📥 Has INBOX: " . ($jsonData['has_inbox'] ? '✅ Yes' : '❌ No') . "\n";
                echo "🔍 Filter Active: " . ($jsonData['filter_active'] ? '✅ Yes' : '❌ No') . "\n";
                echo str_repeat("-", 60) . "\n\n";
            }
        } else {
            echo $line . "\n\n";
        }
    }
}

function showGmailSyncLogs($logFile) {
    echo "🔄 Gmail Sync Logs:\n\n";
    
    $command = 'findstr /C:"Gmail: Created" /C:"Gmail: Updated" "' . str_replace('/', '\\', $logFile) . '"';
    $output = shell_exec($command);
    
    if (empty($output)) {
        echo "ℹ️  Keine Gmail Sync Logs gefunden.\n";
        return;
    }
    
    echo $output;
}

function showGmailWarnings($logFile) {
    echo "⚠️  Gmail Warnungen:\n\n";
    
    $command = 'findstr /C:"Gmail: Email with INBOX label found despite filter" "' . str_replace('/', '\\', $logFile) . '"';
    $output = shell_exec($command);
    
    if (empty($output)) {
        echo "✅ Keine Gmail Warnungen gefunden. Das ist gut!\n";
        return;
    }
    
    echo $output;
}

function showAllGmailLogs($logFile) {
    echo "📧 Alle Gmail Logs:\n\n";
    
    $command = 'findstr /C:"Gmail" "' . str_replace('/', '\\', $logFile) . '"';
    $output = shell_exec($command);
    
    if (empty($output)) {
        echo "ℹ️  Keine Gmail Logs gefunden.\n";
        return;
    }
    
    echo $output;
}

function followLogs($logFile) {
    echo "👀 Verfolge Gmail Logs in Echtzeit...\n";
    echo "💡 Führen Sie in einem anderen Terminal 'php test_gmail_logging.php' aus\n";
    echo "🛑 Drücken Sie Ctrl+C zum Beenden\n\n";
    
    // PowerShell command to tail the log file
    $command = 'powershell "Get-Content \\"' . str_replace('/', '\\', $logFile) . '\\" -Wait -Tail 0 | Where-Object { $_ -match \\"Gmail\\" }"';
    
    // Execute and stream output
    $process = popen($command, 'r');
    if ($process) {
        while (!feof($process)) {
            $line = fgets($process);
            if ($line !== false) {
                echo $line;
                flush();
            }
        }
        pclose($process);
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ Log-Anzeige beendet.\n";
echo "\n💡 Tipps:\n";
echo "- Führen Sie 'php test_gmail_logging.php' aus, um neue Logs zu generieren\n";
echo "- Die Log-Datei befindet sich unter: storage/logs/laravel.log\n";
echo "- Sie können die Log-Datei auch direkt in einem Texteditor öffnen\n";
