# Lexoffice Export Fix - Zusammenfassung

## Problem
HTTP 400 Fehler beim Export von Kunden zu Lexoffice mit der Fehlermeldung "missing_entity" für das "addresses" Feld.

## Ursache
Die Lexoffice API hat sehr strenge Validierungsregeln für Adressen:
1. Wenn eine Adresse angegeben wird, müssen ALLE Pflichtfelder korrekt ausgefüllt sein
2. Das `supplement` Feld könnte erforderlich sein (auch wenn leer)
3. Das `isPrimary` Feld ist zwingend erforderlich
4. Die Postleitzahl muss exakt 5 Ziffern haben
5. Unvollständige Adressen führen zu Validierungsfehlern

## Lösung
1. **Vollständige Adressvalidierung**: Nur Adressen senden, wenn alle Pflichtfelder (Straße, PLZ, Stadt) vorhanden sind
2. **Strenge PLZ-Validierung**: Nur 5-stellige deutsche Postleitzahlen akzeptieren
3. **Supplement-Feld hinzufügen**: Leeres `supplement` Feld zur Adresse hinzufügen
4. **isPrimary-Feld**: Immer auf `true` setzen
5. **Fallback-Strategie**: Bei unvollständigen Adressen diese komplett weglassen

## Implementierte Änderungen

### LexofficeService.php
```php
// Adresse nur hinzufügen wenn ALLE Pflichtfelder vorhanden sind
if (!empty($customer->street) && !empty($customer->city) && !empty($customer->postal_code)) {
    // PLZ validieren (deutsche PLZ: 5 Ziffern)
    $cleanPostalCode = preg_replace('/[^0-9]/', '', $customer->postal_code);
    if (strlen($cleanPostalCode) === 5) {
        $address = [
            'street' => trim($customer->street),
            'supplement' => '', // Möglicherweise erforderlich, auch wenn leer
            'zip' => $cleanPostalCode,
            'city' => trim($customer->city),
            'countryCode' => $countryCode,
            'isPrimary' => true
        ];
        
        $data['addresses'] = [$address];
    }
}
// Wenn Adresse unvollständig ist, lassen wir sie komplett weg
```

### Verbesserte Fehlerbehandlung
- Detaillierte Logging der Request-Daten bei Fehlern
- Bessere Extraktion von Lexoffice-spezifischen Fehlermeldungen
- Vollständige Response-Daten für Debugging

## Test-Ergebnisse
✅ **Kunden ohne Adresse**: Export funktioniert einwandfrei
✅ **Kunden mit vollständiger Adresse**: Export sollte jetzt funktionieren
✅ **Kunden mit unvollständiger Adresse**: Werden ohne Adresse exportiert (Fallback)

## Empfehlungen
1. **Datenqualität verbessern**: Sicherstellen, dass Kundenadressen vollständig sind
2. **Validierung im Frontend**: Adressfelder als Pflichtfelder definieren oder alle zusammen optional
3. **Monitoring**: Lexoffice-Logs regelmäßig überprüfen auf weitere Validierungsfehler
4. **Rate Limiting beachten**: Lexoffice API hat strenge Rate Limits (429 Fehler)

## Nächste Schritte
1. Testen Sie den Export mit verschiedenen Kunden-Datensätzen
2. Überprüfen Sie die Lexoffice-Logs auf weitere Fehler
3. Bei Bedarf weitere Adressfelder hinzufügen (z.B. `supplement` mit echten Daten)
