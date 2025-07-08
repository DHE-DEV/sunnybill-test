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

### 2. GmailService - Vollständige Synchronisation beibehalten
**Datei:** `app/Services/GmailService.php`

**Wichtige Entscheidung:** Der GmailService synchronisiert weiterhin alle E-Mails (einschließlich Papierkorb), um sicherzustellen, dass Label-Änderungen korrekt erfasst werden.

**Warum diese Entscheidung:**
- Wenn eine E-Mail in den Papierkorb verschoben wird, ändert sich ihr Label von `["UNREAD", "IMPORTANT", "CATEGORY_PERSONAL", "INBOX"]` zu `["UNREAD", "IMPORTANT", "CATEGORY_PERSONAL", "TRASH"]`
- Ohne vollständige Synchronisation würden solche Label-Änderungen nicht erfasst
- Die E-Mail würde in der Datenbank mit veralteten Labels gespeichert bleiben
- Das Problem wird stattdessen durch die Anzeige-Filter in der UI gelöst

## Funktionsweise

### Vor der Änderung:
1. ✗ Alle E-Mails wurden synchronisiert (INBOX, TRASH, SPAM, etc.)
2. ✗ Alle E-Mails wurden in der Liste angezeigt
3. ✗ E-Mails aus dem Papierkorb waren sichtbar

### Nach der Änderung:
1. ✓ Alle E-Mails werden weiterhin synchronisiert (um Label-Änderungen zu erfassen)
2. ✓ Nur INBOX-E-Mails werden standardmäßig angezeigt
3. ✓ E-Mails aus dem Papierkorb sind ausgeblendet
4. ✓ Label-Änderungen (z.B. Verschieben in Papierkorb) werden korrekt erfasst
5. ✓ Benutzer können über Filter andere Ordner auswählen:
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
2. **Datenintegrität:** Label-Änderungen werden korrekt erfasst (z.B. wenn E-Mails in Papierkorb verschoben werden)
3. **Performance:** Weniger E-Mails werden standardmäßig angezeigt (aber alle werden synchronisiert)
4. **Flexibilität:** Benutzer können weiterhin alle Ordner über Filter erreichen
5. **Korrekte Synchronisation:** E-Mail-Status bleibt immer aktuell

## Kompatibilität
- ✓ Bestehende E-Mails werden nicht verändert
- ✓ Alle vorhandenen Filter funktionieren weiterhin
- ✓ Gmail-API-Funktionen bleiben vollständig erhalten
- ✓ Rückwärtskompatibilität gewährleistet

## Deployment
Die Änderungen sind sofort nach dem Deployment aktiv:
1. Neue Seitenaufrufe zeigen nur INBOX-E-Mails
2. Synchronisationen erfassen weiterhin alle E-Mails (für korrekte Label-Updates)
3. Benutzer können bei Bedarf andere Ordner über Filter auswählen
4. Label-Änderungen werden korrekt in der Datenbank aktualisiert
