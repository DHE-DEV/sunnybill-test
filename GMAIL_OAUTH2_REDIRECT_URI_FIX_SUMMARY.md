# Gmail OAuth2 Redirect URI Fix - Zusammenfassung

## Problem
Bei der Gmail-Autorisierung trat der Fehler "Zugriff blockiert: Die Anfrage dieser App ist ungültig" auf.

## Ursache
Die Redirect URI in der Anwendung stimmte nicht mit der in der Google Cloud Console konfigurierten URI überein:
- **Anwendung verwendete**: `http://sunnybill-test.test/admin/gmail/oauth/callback` (lokale Entwicklungsumgebung)
- **Google Cloud Console erwartete**: `https://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback` (Produktionsumgebung)

## Durchgeführte Fixes

### 1. APP_URL in .env korrigiert
**Datei**: `.env`
```diff
- APP_URL=http://sunnybill-test.test
- APP_ASSETS_URL=http://sunnybill-test.test
+ APP_URL=https://sunnybill-test.chargedata.eu
+ APP_ASSETS_URL=https://sunnybill-test.chargedata.eu
```

### 2. Fehlende Methode im CompanySetting Model hinzugefügt
**Datei**: `app/Models/CompanySetting.php`
```php
/**
 * Gibt das Gmail Token Ablaufdatum zurück
 */
public function getGmailTokenExpiresAt(): ?\Carbon\Carbon
{
    try {
        return $this->gmail_token_expires_at;
    } catch (\Exception $e) {
        return null;
    }
}
```

### 3. Debug-Script erstellt
**Datei**: `test_gmail_oauth_debug.php`
- Umfassende Diagnose der Gmail OAuth2-Konfiguration
- Prüfung der Redirect URI
- Validierung der OAuth2-Parameter
- Token-Status-Überprüfung
- Verbindungstest

### 4. Setup-Anleitung erstellt
**Datei**: `GOOGLE_CLOUD_CONSOLE_SETUP_ANLEITUNG.md`
- Detaillierte Schritt-für-Schritt-Anleitung für Google Cloud Console
- Checkliste für alle erforderlichen Konfigurationen
- Troubleshooting-Tipps

## Aktueller Status

### ✅ Behoben
- **Redirect URI**: Jetzt korrekt `https://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback`
- **OAuth2-Parameter**: Alle erforderlichen Parameter werden generiert
- **Scopes**: Alle Gmail-Scopes sind konfiguriert
- **Model-Methoden**: Alle erforderlichen Methoden sind implementiert

### 📋 Google Cloud Console Konfiguration erforderlich
In der Google Cloud Console muss die **Autorisierte Weiterleitungs-URI** eingetragen werden:

**Exakte URI**: `https://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback`

**Wo**: Google Cloud Console → APIs & Services → Credentials → OAuth 2.0 Client ID → Authorized redirect URIs

### 🔧 Zusätzliche Empfehlungen
1. **OAuth Consent Screen** vollständig ausfüllen
2. **Gmail API** aktivieren
3. **Scopes** hinzufügen:
   - `https://www.googleapis.com/auth/gmail.readonly`
   - `https://www.googleapis.com/auth/gmail.modify`
   - `https://www.googleapis.com/auth/userinfo.email`
4. **Test Users** hinzufügen (falls App im Testing-Modus)

## Debug-Kommandos

### Gmail-Konfiguration testen
```bash
php artisan tinker --execute="require 'test_gmail_oauth_debug.php';"
```

### OAuth2-URL generieren (zum Testen)
```bash
php artisan tinker --execute="
use App\Services\GmailService;
\$service = new GmailService();
echo \$service->getAuthorizationUrl(url('/admin/gmail/oauth/callback'));
"
```

## Nächste Schritte

1. **Google Cloud Console konfigurieren** (siehe `GOOGLE_CLOUD_CONSOLE_SETUP_ANLEITUNG.md`)
2. **5-10 Minuten warten** nach Änderungen in Google Cloud Console
3. **Gmail-Autorisierung testen** unter: https://sunnybill-test.chargedata.eu/admin/company-settings
4. **Debug-Script ausführen** bei Problemen

## Sicherheitsfeatures

- ✅ **CSRF-Schutz**: State-Parameter wird verwendet
- ✅ **HTTPS**: Sichere Verbindung für OAuth2
- ✅ **Token-Erneuerung**: Automatische Refresh-Token-Verwendung
- ✅ **Fehlerbehandlung**: Umfassende Exception-Behandlung

## Dateien geändert

1. `.env` - APP_URL korrigiert
2. `app/Models/CompanySetting.php` - Methode `getGmailTokenExpiresAt()` hinzugefügt
3. `test_gmail_oauth_debug.php` - Debug-Script erstellt
4. `GOOGLE_CLOUD_CONSOLE_SETUP_ANLEITUNG.md` - Setup-Anleitung erstellt
5. `GMAIL_OAUTH2_REDIRECT_URI_FIX_SUMMARY.md` - Diese Zusammenfassung

## Testergebnisse

```
=== Gmail OAuth2 Debug Test ===

1. Firmeneinstellungen prüfen...
   Gmail aktiviert: ✅ Ja
   Client ID vorhanden: ✅ Ja
   Client Secret vorhanden: ✅ Ja

2. GmailService-Konfiguration prüfen...
   Service konfiguriert: ✅ Ja

3. OAuth2-Autorisierungs-URL generieren...
   Redirect URI: https://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback
   ✅ OAuth2-URL erfolgreich generiert
   ✅ Redirect URI ist korrekt
   ✅ Alle Scopes vorhanden

4. Token-Status prüfen...
   ❌ Keine Autorisierung vorhanden (erwartet vor erster Autorisierung)

=== NÄCHSTE SCHRITTE ===
3. Gmail-Autorisierung durchführen:
   → https://sunnybill-test.chargedata.eu/admin/company-settings
   → Gmail-Tab öffnen
   → 'Gmail autorisieren' klicken
```

Die technischen Probleme sind behoben. Nach der Konfiguration der Redirect URI in der Google Cloud Console sollte die Gmail-Autorisierung funktionieren.
