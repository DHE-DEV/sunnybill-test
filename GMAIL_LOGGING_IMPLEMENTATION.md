# Gmail Logging Implementation

## Übersicht
Detailliertes Logging für Gmail E-Mail Synchronisation wurde implementiert, um zu sehen welche E-Mail welche Labels hat.

## Implementierte Features

### 1. Detailliertes E-Mail Label Logging
**Datei**: `app/Services/GmailService.php`
**Methode**: `logEmailLabels()`

Für jede synchronisierte E-Mail wird folgendes geloggt:
- **Gmail ID**: Eindeutige Gmail Message ID
- **Subject**: E-Mail Betreff
- **From**: Absender E-Mail-Adresse
- **Total Labels**: Anzahl aller Labels
- **All Labels**: Vollständige Liste aller Labels
- **System Labels**: Gmail System Labels (INBOX, SENT, DRAFT, TRASH, SPAM, UNREAD, STARRED, IMPORTANT)
- **Category Labels**: Gmail Kategorie Labels (CATEGORY_PERSONAL, CATEGORY_SOCIAL, etc.)
- **User Labels**: Benutzerdefinierte Labels
- **Boolean Flags**:
  - `has_inbox`: Hat INBOX Label
  - `is_unread`: Hat UNREAD Label
  - `is_important`: Hat IMPORTANT Label
  - `is_starred`: Hat STARRED Label
- **Filter Status**: Ob der INBOX Filter aktiviert ist

### 2. Erweiterte Sync-Logs
**Zusätzliche Logs bei E-Mail Erstellung/Update**:
- Gmail ID
- Subject
- Labels Array

### 3. Filter-Warnung
**Automatische Warnung** wenn E-Mail mit INBOX Label gefunden wird, obwohl Filter aktiviert ist:
```php
Log::warning("Gmail: Email with INBOX label found despite filter being active", [
    'gmail_id' => $messageId,
    'subject' => $subject,
    'labels' => $labels
]);
```

## Log-Beispiel
```json
{
    "level": "info",
    "message": "Gmail Email Labels",
    "context": {
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
}
```

## Verwendung

### 1. Log-Datei prüfen
```bash
tail -f storage/logs/laravel.log | grep "Gmail Email Labels"
```

### 2. Test ausführen
```bash
php test_gmail_logging.php
```

### 3. Logs in Echtzeit verfolgen
```bash
tail -f storage/logs/laravel.log
```

## Nutzen

### Debugging
- Sehen welche Labels jede E-Mail hat
- Prüfen ob Filter korrekt funktioniert
- Verstehen der Gmail Label-Struktur

### Monitoring
- Überwachung der E-Mail-Synchronisation
- Erkennung von Problemen mit Labels
- Analyse der E-Mail-Kategorisierung

### Entwicklung
- Besseres Verständnis der Gmail API Responses
- Hilfe bei der Implementierung neuer Filter
- Debugging von Label-basierten Features

## Log-Level
- **INFO**: Normale E-Mail Label Informationen
- **WARNING**: E-Mails mit INBOX Label trotz aktivem Filter
- **ERROR**: Fehler bei der E-Mail-Verarbeitung

## Status: ✅ IMPLEMENTIERT UND GETESTET

Das Logging ist vollständig implementiert und liefert detaillierte Informationen über alle E-Mail Labels während der Synchronisation.
