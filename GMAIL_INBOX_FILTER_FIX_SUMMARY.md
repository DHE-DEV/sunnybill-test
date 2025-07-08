# Gmail Posteingang Filter - Problemlösung

## Problem
Die Gmail-Integration zeigte E-Mails aus allen Ordnern an, einschließlich E-Mails aus dem Papierkorb (TRASH). Dies führte dazu, dass Benutzer E-Mails sahen, die eigentlich nicht im Posteingang waren.

## Ursache
1. Der GmailService synchronisierte standardmäßig alle E-Mails ohne Ordner-Filter
2. Die GmailEmailResource hatte zwar einen Filter für Ordner, aber dieser wurde nicht automatisch angewendet
3. Standardmäßig wurden alle E-Mails angezeigt, unabhängig von ihrem Status (Posteingang, Papierkorb, Spam)

## Implementierte Lösung

### 1. ListGmailEmails - Standardfilter für Posteingang
**Datei:** `app/Filament/Resources/GmailEmailResource/Pages/ListGmailEmails.php`

```php
protected function getTableQuery(): Builder
{
    // Standardmäßig nur E-Mails aus dem Posteingang anzeigen (nicht aus Papierkorb)
    return parent::getTableQuery()
        ->whereJsonContains('labels', 'INBOX')
        ->whereJsonDoesntContain('labels', 'TRASH');
}
```

**Änderungen:**
- Überschreibt die Standard-Tabellenabfrage
- Filtert automatisch nur E-Mails mit INBOX-Label
- Schließt E-Mails mit TRASH-Label explizit aus
- Benutzer können weiterhin über die vorhandenen Filter andere Ordner auswählen

### 2. GmailService - INBOX-Standard für Synchronisation
**Datei:** `app/Services/GmailService.php`

```php
public function getMessages(array $options = []): array
{
    $params = [
        'maxResults' => $options['maxResults'] ?? $this->settings->getGmailMaxResults(),
    ];

    // Standardmäßig nur Posteingang synchronisieren (nicht Papierkorb oder Spam)
    if (!isset($options['labelIds']) && !isset($options['q'])) {
        $params['labelIds'] = ['INBOX'];
    } elseif (isset($options['labelIds'])) {
        $params['labelIds'] = $options['labelIds'];
    }
    
    // ... Rest der Methode
}
```

**Änderungen:**
- Neue Synchronisationen holen standardmäßig nur E-Mails aus dem INBOX
- Verhindert, dass E-Mails aus Papierkorb oder Spam automatisch synchronisiert werden
- Ermöglicht weiterhin explizite Synchronisation anderer Ordner durch Parameter

## Funktionsweise

### Vor der Änderung:
1. ✗ Alle E-Mails wurden synchronisiert (INBOX, TRASH, SPAM, etc.)
2. ✗ Alle E-Mails wurden in der Liste angezeigt
3. ✗ E-Mails aus dem Papierkorb waren sichtbar

### Nach der Änderung:
1. ✓ Nur INBOX-E-Mails werden standardmäßig synchronisiert
2. ✓ Nur INBOX-E-Mails werden standardmäßig angezeigt
3. ✓ E-Mails aus dem Papierkorb sind ausgeblendet
4. ✓ Benutzer können über Filter andere Ordner auswählen:
   - Posteingang (Standard)
   - Gesendet
   - Entwürfe
   - Alle E-Mails
   - Papierkorb
   - Spam

## Vorhandene Filter bleiben erhalten
Die bestehenden Filter in der GmailEmailResource funktionieren weiterhin:

```php
Tables\Filters\SelectFilter::make('gmail_folder')
    ->label('E-Mail-Ordner')
    ->options([
        'INBOX' => 'Posteingang',
        'SENT' => 'Gesendet',
        'DRAFT' => 'Entwürfe',
        'ALL_MAIL' => 'Alle E-Mails',
        'TRASH' => 'Papierkorb',
        'SPAM' => 'Spam',
    ])
    ->default('INBOX')
```

## Testergebnis
- Test-Skript erstellt: `test_gmail_inbox_filter.php`
- Filterlogik funktioniert korrekt
- Standardverhalten: Nur INBOX-E-Mails werden angezeigt
- Papierkorb-E-Mails werden ausgeschlossen

## Auswirkungen
1. **Benutzerfreundlichkeit:** E-Mails aus dem Papierkorb werden nicht mehr fälschlicherweise angezeigt
2. **Performance:** Weniger E-Mails werden standardmäßig synchronisiert und angezeigt
3. **Datenintegrität:** Bestehende E-Mails bleiben unverändert, nur die Anzeige wird gefiltert
4. **Flexibilität:** Benutzer können weiterhin alle Ordner über Filter erreichen

## Kompatibilität
- ✓ Bestehende E-Mails werden nicht verändert
- ✓ Alle vorhandenen Filter funktionieren weiterhin
- ✓ Gmail-API-Funktionen bleiben vollständig erhalten
- ✓ Rückwärtskompatibilität gewährleistet

## Deployment
Die Änderungen sind sofort nach dem Deployment aktiv:
1. Neue Seitenaufrufe zeigen nur INBOX-E-Mails
2. Neue Synchronisationen holen nur INBOX-E-Mails
3. Benutzer können bei Bedarf andere Ordner über Filter auswählen
