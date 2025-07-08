# Windows Benachrichtigungen - Vollständige Anleitung

## Übersicht
Diese Anleitung zeigt, wie Sie Windows-Benachrichtigungen optimal für Push-Benachrichtigungen von SunnyBill konfigurieren.

## 1. Grundlegende Windows-Benachrichtigungseinstellungen

### Windows 11 Einstellungen öffnen:
```
Windows-Taste + I → System → Benachrichtigungen
```

### Wichtige Einstellungen aktivieren:
- ✅ **Benachrichtigungen** (Hauptschalter)
- ✅ **Benachrichtigungen im Sperrbildschirm anzeigen**
- ✅ **Erinnerungen und eingehende VoIP-Anrufe im Sperrbildschirm anzeigen**
- ✅ **Benachrichtigungsklänge wiedergeben**
- ✅ **Benachrichtigungen beim Teilen des Bildschirms anzeigen**

## 2. Anzeigedauer von Benachrichtigungen verlängern

### Methode 1 - Windows-Einstellungen:
1. `Windows-Taste + I`
2. **System** → **Benachrichtigungen**
3. Scrollen Sie zu **"Weitere Optionen"**
4. **"Benachrichtigungen für eine bestimmte Zeit anzeigen"** aktivieren
5. **"Anzahl der Benachrichtigungen im Info-Center"** auf Maximum setzen (20)

### Methode 2 - Erweiterte Registry-Einstellung:
1. `Windows-Taste + R` → `regedit` eingeben
2. Navigieren zu: `HKEY_CURRENT_USER\Control Panel\Accessibility`
3. Rechtsklick → **Neu** → **DWORD-Wert (32-Bit)**
4. Name: `MessageDuration`
5. Wert: `30` (für 30 Sekunden Anzeigedauer)
6. Computer neu starten

### Methode 3 - Barrierefreiheit nutzen:
1. `Windows-Taste + I`
2. **Barrierefreiheit** → **Visuell** → **Benachrichtigungen**
3. **"Benachrichtigungen für längere Zeit anzeigen"** aktivieren

## 3. Browser-spezifische Einstellungen

### Microsoft Edge:
1. Edge öffnen → `edge://settings/content/notifications`
2. **"Vor dem Senden fragen"** aktivieren
3. **"Ruhige Benachrichtigungsanfragen verwenden"** deaktivieren
4. Für `sunnybill-test.test`: **"Zulassen"** setzen

### Chrome:
1. Chrome öffnen → `chrome://settings/content/notifications`
2. **"Vor dem Senden fragen"** aktivieren
3. Für `sunnybill-test.test`: **"Zulassen"** setzen

### Firefox:
1. Firefox öffnen → `about:preferences#privacy`
2. **Berechtigungen** → **Benachrichtigungen** → **Einstellungen**
3. Für `sunnybill-test.test`: **"Zulassen"** setzen

## 4. Fokus-Assistent konfigurieren

### Fokus-Assistent deaktivieren (für maximale Benachrichtigungen):
1. `Windows-Taste + U` oder Benachrichtigungszentrum
2. **Fokus-Assistent** → **Aus**
3. Oder: **Nur Alarme** für wichtige Benachrichtigungen

### Fokus-Assistent-Regeln anpassen:
1. `Windows-Taste + I`
2. **System** → **Fokus-Assistent**
3. **Automatische Regeln** konfigurieren
4. **Prioritätsliste** für wichtige Apps erstellen

## 5. Benachrichtigungsverlauf aktivieren

### Windows 11:
1. `Windows-Taste + I`
2. **System** → **Benachrichtigungen**
3. **"Benachrichtigungen nach dem Schließen im Info-Center anzeigen"** aktivieren
4. **"Anzahl der Benachrichtigungen im Info-Center"** erhöhen

### Verlauf einsehen:
- `Windows-Taste + N` für Benachrichtigungszentrum
- Oder Klick auf Benachrichtigungssymbol in Taskleiste

## 6. Erweiterte Konfiguration

### Benachrichtigungspriorität:
1. `Windows-Taste + I`
2. **Apps** → **Apps & Features**
3. Browser suchen (Edge/Chrome/Firefox)
4. **Erweiterte Optionen**
5. **App-Berechtigungen** → **Benachrichtigungen** → **Ein**

### Systemklänge anpassen:
1. `Windows-Taste + I`
2. **System** → **Sound**
3. **Erweiterte Soundoptionen**
4. **App-Lautstärkeregler** → Browser-Lautstärke erhöhen

## 7. Problembehandlung

### Benachrichtigungen erscheinen nicht:
1. **Windows-Benachrichtigungen prüfen**: `Windows-Taste + I` → System → Benachrichtigungen
2. **Browser-Berechtigungen prüfen**: Siehe Browser-spezifische Einstellungen oben
3. **Fokus-Assistent deaktivieren**: `Windows-Taste + U`
4. **Windows-Updates installieren**
5. **Browser neu starten**

### Benachrichtigungen verschwinden zu schnell:
1. **Registry-Einstellung** (Methode 2 oben) verwenden
2. **Barrierefreiheit** aktivieren (Methode 3 oben)
3. **Fokus-Assistent** komplett deaktivieren

### Benachrichtigungen sind zu leise:
1. **Systemlautstärke** erhöhen
2. **App-spezifische Lautstärke** für Browser erhöhen
3. **Benachrichtigungsklänge** in Windows-Einstellungen aktivieren

## 8. Optimale Konfiguration für SunnyBill

### Empfohlene Einstellungen:
```
✅ Windows-Benachrichtigungen: Ein
✅ Benachrichtigungen im Sperrbildschirm: Ein
✅ Fokus-Assistent: Aus (oder nur Alarme)
✅ Browser-Berechtigung für sunnybill-test.test: Zulassen
✅ MessageDuration Registry-Wert: 30 Sekunden
✅ Benachrichtigungsverlauf: Aktiviert
✅ Anzahl im Info-Center: Maximum (20)
```

### Test-Workflow:
1. **Browser-Berechtigung erteilen** (siehe Browser-Einstellungen)
2. **Test-Seite öffnen**: `https://sunnybill-test.test/test_push_notifications_simple.html`
3. **"Browser-Support prüfen"** → Status sollte "granted" sein
4. **"Test-Benachrichtigung senden"** → Desktop-Benachrichtigung sollte erscheinen
5. **Benachrichtigung im Windows-Info-Center prüfen** (`Windows-Taste + N`)

## 9. Häufige Probleme und Lösungen

| Problem | Lösung |
|---------|--------|
| Berechtigung "denied" | Browser-Einstellungen manuell ändern |
| Benachrichtigungen verschwinden sofort | Registry MessageDuration setzen |
| Keine Klänge | Benachrichtigungsklänge in Windows aktivieren |
| Benachrichtigungen im Vollbildmodus nicht sichtbar | "Beim Teilen des Bildschirms anzeigen" aktivieren |
| Verlauf nicht verfügbar | Benachrichtigungsverlauf in Windows aktivieren |

## 10. Zusätzliche Tipps

### Für Entwickler:
- **Test-API verwenden**: `/api/notifications/count/test` für Tests ohne Login
- **Debug-Script nutzen**: `debug_push_notifications_live.php`
- **Browser-Konsole überwachen**: F12 → Console für JavaScript-Fehler

### Für Endbenutzer:
- **Regelmäßig Info-Center prüfen**: `Windows-Taste + N`
- **Bei Problemen Browser neu starten**
- **Windows-Updates installieren** für beste Kompatibilität
- **SunnyBill-Dashboard** für dauerhaften Benachrichtigungsverlauf nutzen

## 11. Weiterführende Ressourcen

- **Windows-Dokumentation**: [Microsoft Support - Benachrichtigungen](https://support.microsoft.com/de-de/windows/benachrichtigungen-und-aktionen-in-windows-8b5e7ab5-d6c1-4b1f-8b6a-1b8b1b1b1b1b)
- **Browser-Dokumentation**: Siehe jeweilige Browser-Hilfe
- **SunnyBill-Dokumentation**: `PUSH_NOTIFICATIONS_DEBUG_FINAL.md`

---

**Status**: Vollständige Anleitung für optimale Windows-Benachrichtigungskonfiguration
**Letzte Aktualisierung**: 8. Januar 2025
**Kompatibilität**: Windows 11, Edge/Chrome/Firefox
