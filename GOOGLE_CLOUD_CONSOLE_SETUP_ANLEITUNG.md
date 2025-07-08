# Google Cloud Console Setup für Gmail OAuth2-Integration

## Ihre Domain: https://sunnybill-test.chargedata.eu/

## Schritt-für-Schritt Anleitung:

### 1. Autorisierte Weiterleitungs-URIs konfigurieren

**Wo:** Google Cloud Console → APIs & Services → Credentials → Ihr OAuth 2.0 Client

**Exakte URI die Sie eingeben müssen:**
```
https://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback
```

**Wichtig:**
- ✅ Exakt diese Schreibweise (keine Leerzeichen, kein Slash am Ende)
- ✅ `https://` (nicht http://)
- ✅ Genau dieser Pfad: `/admin/gmail/oauth/callback`

### 2. OAuth Consent Screen konfigurieren

**Wo:** APIs & Services → OAuth consent screen

**Pflichtfelder:**
- **App name**: `SunnyBill Gmail Integration`
- **User support email**: Ihre E-Mail-Adresse
- **Developer contact information**: Ihre E-Mail-Adresse

**Optional aber empfohlen:**
- **App domain**: `sunnybill-test.chargedata.eu`
- **Authorized domains**: `chargedata.eu`
- **App logo**: Ihr SunnyBill-Logo (falls vorhanden)

### 3. Scopes hinzufügen

**Wo:** OAuth consent screen → Scopes → ADD OR REMOVE SCOPES

**Benötigte Scopes:**
```
https://www.googleapis.com/auth/gmail.readonly
https://www.googleapis.com/auth/gmail.modify
https://www.googleapis.com/auth/userinfo.email
```

**So finden Sie die Scopes:**
1. "ADD OR REMOVE SCOPES" klicken
2. Nach "gmail" suchen
3. Die drei oben genannten Scopes auswählen
4. "UPDATE" klicken

### 4. Test Users hinzufügen (falls App im Testing-Modus)

**Wo:** OAuth consent screen → Test users

**Was tun:**
- Ihre Gmail-Adresse als Testbenutzer hinzufügen
- Alle E-Mail-Adressen hinzufügen, die die Integration testen sollen

### 5. Gmail API aktivieren

**Wo:** APIs & Services → Library

**Schritte:**
1. Nach "Gmail API" suchen
2. "Gmail API" anklicken
3. "ENABLE" klicken

### 6. Credentials herunterladen

**Wo:** APIs & Services → Credentials → Ihr OAuth 2.0 Client

**Schritte:**
1. Download-Symbol (↓) klicken
2. JSON-Datei herunterladen
3. Client ID und Client Secret notieren

## Häufige Fehlerquellen:

### ❌ Falsche Redirect URI:
- `https://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback/` (Slash am Ende)
- `http://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback` (http statt https)
- `https://sunnybill-test.chargedata.eu/oauth/callback` (falscher Pfad)

### ❌ OAuth Consent Screen unvollständig:
- App-Name fehlt
- Support-E-Mail fehlt
- Developer-Kontakt fehlt
- Scopes nicht hinzugefügt

### ❌ Gmail API nicht aktiviert:
- API muss explizit aktiviert werden
- Kann bis zu 5 Minuten dauern bis aktiv

### ❌ Test Users fehlen:
- Bei Apps im "Testing"-Modus müssen Benutzer explizit hinzugefügt werden
- Nur hinzugefügte E-Mail-Adressen können die App autorisieren

## Checkliste:

- [ ] Redirect URI korrekt eingetragen: `https://sunnybill-test.chargedata.eu/admin/gmail/oauth/callback`
- [ ] OAuth Consent Screen vollständig ausgefüllt
- [ ] Alle 3 Gmail-Scopes hinzugefügt
- [ ] Gmail API aktiviert
- [ ] Test Users hinzugefügt (falls im Testing-Modus)
- [ ] Client ID und Client Secret in SunnyBill eingetragen

## Nach der Konfiguration:

1. **Warten Sie 5-10 Minuten** - Google braucht Zeit um Änderungen zu propagieren
2. **Testen Sie die Integration** in SunnyBill unter Firmeneinstellungen
3. **Prüfen Sie die Logs** falls Fehler auftreten

## Troubleshooting:

### "Zugriff blockiert: Die Anfrage dieser App ist ungültig"
- Redirect URI prüfen (häufigster Fehler)
- OAuth Consent Screen vollständig ausfüllen
- 5-10 Minuten warten nach Änderungen

### "Error 400: redirect_uri_mismatch"
- Redirect URI stimmt nicht exakt überein
- Groß-/Kleinschreibung beachten
- Keine Leerzeichen oder zusätzliche Zeichen

### "Error 403: access_denied"
- App ist im Testing-Modus und Benutzer ist nicht als Testbenutzer hinzugefügt
- Scopes sind nicht korrekt konfiguriert

## Support:

Bei weiteren Problemen:
1. Google Cloud Console Logs prüfen
2. SunnyBill Application Logs prüfen (`storage/logs/laravel.log`)
3. Browser Developer Tools für detaillierte Fehlermeldungen
