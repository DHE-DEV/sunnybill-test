# Gmail E-Mail Logs - Wo und Wie einsehen

## 🎯 Schnellstart - Log Viewer verwenden

**Am einfachsten:** Verwenden Sie das bereitgestellte Script:

```bash
php show_gmail_logs.php
```

Das Script bietet Ihnen ein interaktives Menü mit folgenden Optionen:
1. Alle Gmail Label Logs anzeigen
2. Nur die letzten 10 Gmail Label Logs
3. Nur die letzten 20 Gmail Label Logs
4. Gmail Sync Logs anzeigen
5. Gmail Warnungen anzeigen
6. Alle Gmail Logs (Labels + Sync + Warnungen)
7. Log-Datei in Echtzeit verfolgen

## 📁 Log-Datei Speicherort

**Hauptlog-Datei:**
```
E:\Web-Entwicklung\sunnybill-test\storage\logs\laravel.log
```

**Aktuelle Größe:** ~8.1 MB (Stand: 08.07.2025)

## 🔍 Verschiedene Wege die Logs einzusehen

### 1. **Mit dem Log Viewer Script (Empfohlen)**
```bash
php show_gmail_logs.php
```
- ✅ Benutzerfreundlich
- ✅ Formatierte Ausgabe
- ✅ Filteroptionen
- ✅ Echtzeit-Verfolgung

### 2. **Direkt in der Log-Datei**
Öffnen Sie die Datei in einem Texteditor:
```
storage\logs\laravel.log
```

### 3. **Mit Windows PowerShell**
```powershell
# Alle Gmail Logs anzeigen
Get-Content "storage\logs\laravel.log" | Select-String "Gmail"

# Nur Gmail Label Logs
Get-Content "storage\logs\laravel.log" | Select-String "Gmail Email Labels"

# Letzte 10 Gmail Label Logs
Get-Content "storage\logs\laravel.log" | Select-String "Gmail Email Labels" | Select-Object -Last 10

# Logs in Echtzeit verfolgen
Get-Content "storage\logs\laravel.log" -Wait -Tail 0 | Where-Object { $_ -match "Gmail" }
```

### 4. **Mit Windows CMD**
```cmd
# Alle Gmail Logs anzeigen
findstr "Gmail" storage\logs\laravel.log

# Nur Gmail Label Logs
findstr "Gmail Email Labels" storage\logs\laravel.log

# Gmail Sync Logs
findstr /C:"Gmail: Created" /C:"Gmail: Updated" storage\logs\laravel.log
```

## 📧 Was wird geloggt?

### Gmail Label Logs
Für jede E-Mail wird geloggt:
```json
{
    "gmail_id": "18d1234567890abcd",
    "subject": "Test E-Mail",
    "from": "test@example.com",
    "total_labels": 3,
    "all_labels": ["INBOX", "UNREAD", "IMPORTANT"],
    "system_labels": ["INBOX", "UNREAD", "IMPORTANT"],
    "category_labels": [],
    "user_labels": [],
    "has_inbox": true,
    "is_unread": true,
    "is_important": true,
    "is_starred": false,
    "filter_active": true
}
```

### Gmail Sync Logs
```
Gmail: Created new email {"gmail_id":"123","subject":"Test","labels":["INBOX"]}
Gmail: Updated email {"gmail_id":"456","subject":"Test 2","labels":["SENT"]}
```

### Gmail Warnungen
```
Gmail: Email with INBOX label found despite filter being active
```

## 🚀 Logs generieren

Um neue Logs zu erstellen, führen Sie eine Gmail-Synchronisation durch:

```bash
# Test mit Logging
php test_gmail_logging.php

# Normale Synchronisation
php test_gmail_sync.php
```

## 💡 Tipps

### Echtzeit-Monitoring
Öffnen Sie zwei Terminals:
1. **Terminal 1:** `php show_gmail_logs.php` → Option 7 (Echtzeit-Verfolgung)
2. **Terminal 2:** `php test_gmail_logging.php` (Synchronisation ausführen)

### Log-Datei zu groß?
Falls die Log-Datei zu groß wird:
```bash
# Backup erstellen
copy storage\logs\laravel.log storage\logs\laravel_backup.log

# Log-Datei leeren (Vorsicht!)
echo. > storage\logs\laravel.log
```

### Nur bestimmte Zeiträume
```powershell
# Logs von heute
Get-Content "storage\logs\laravel.log" | Select-String "2025-07-08.*Gmail"

# Logs der letzten Stunde
Get-Content "storage\logs\laravel.log" | Select-String "$(Get-Date -Format 'yyyy-MM-dd HH'):.*Gmail"
```

## 🔧 Troubleshooting

### Keine Logs sichtbar?
1. ✅ Prüfen Sie ob Gmail konfiguriert ist
2. ✅ Führen Sie `php test_gmail_logging.php` aus
3. ✅ Prüfen Sie die Log-Datei Berechtigung
4. ✅ Stellen Sie sicher, dass Laravel Logging aktiviert ist

### Log-Datei nicht gefunden?
```bash
# Verzeichnis erstellen falls nicht vorhanden
mkdir storage\logs

# Leere Log-Datei erstellen
echo. > storage\logs\laravel.log
```

## 📊 Log-Analyse

### Häufige Suchbegriffe
- `"Gmail Email Labels"` - Detaillierte Label-Informationen
- `"Gmail: Created"` - Neue E-Mails
- `"Gmail: Updated"` - Aktualisierte E-Mails
- `"INBOX label found despite filter"` - Filter-Probleme
- `"filter_active":true` - Filter-Status

### Statistiken
```powershell
# Anzahl verarbeiteter E-Mails heute
(Get-Content "storage\logs\laravel.log" | Select-String "$(Get-Date -Format 'yyyy-MM-dd').*Gmail Email Labels").Count

# Anzahl E-Mails mit INBOX Label
(Get-Content "storage\logs\laravel.log" | Select-String "has_inbox.*true").Count
```

## ✅ Zusammenfassung

**Einfachste Methode:** `php show_gmail_logs.php`
**Log-Datei:** `storage\logs\laravel.log`
**Echtzeit:** Option 7 im Log Viewer
**Neue Logs:** `php test_gmail_logging.php`
