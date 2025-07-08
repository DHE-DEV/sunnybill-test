# Lexoffice Integration - Vollständige Problemlösung

## Übersicht der behobenen Probleme

### 1. Export fehlgeschlagen (HTTP 400)
**Problem:** Kunden-Export zu Lexoffice schlug mit HTTP 400 "missing_entity" für Adressen fehl.

**Ursache:** Lexoffice API hat sehr strenge Adressvalidierung - unvollständige Adressen führten zu Validierungsfehlern.

**Lösung:**
- Vollständige Adressvalidierung implementiert
- Nur Adressen mit allen Pflichtfeldern (Straße, PLZ, Stadt) werden gesendet
- PLZ-Validierung auf exakt 5 Ziffern
- `isPrimary: true` und `supplement: ''` Felder hinzugefügt
- Fallback: Unvollständige Adressen werden komplett weggelassen
- Verbesserte Fehlerbehandlung mit detailliertem Logging

### 2. Import fehlgeschlagen (null-Wert Fehler)
**Problem:** `CompanySetting::extractCustomerNumber()` erwartete String, bekam aber `null` von importierten Lexoffice Kunden.

**Ursache:** Importierte Kunden hatten keine `customer_number`, was zu Type-Errors führte.

**Lösung:**
- `Customer::generateCustomerNumber()` überspringe Kunden ohne `customer_number`
- `CompanySetting::extractCustomerNumber()` akzeptiert jetzt `null`-Werte
- Automatische Kundennummer-Generierung beim Import
- Standard-Land 'DE' setzen (DB-Constraint)

### 3. Falsche Kundentyp-Klassifizierung
**Problem:** Personen aus Lexoffice wurden fälschlicherweise als Geschäftskunden importiert.

**Ursache:** Import-Logik unterschied nicht korrekt zwischen Lexoffice `company` und `person` Objekten.

**Lösung:**
- Verbesserte `createOrUpdateCustomer()` Methode
- Korrekte Unterscheidung zwischen:
  - Lexoffice `company` Objekten → `customer_type = 'business'` + `company_name` gesetzt
  - Lexoffice `person` Objekten → `customer_type = 'private'` + nur `name` gesetzt
- Bereits falsch importierte Kunden wurden korrigiert

## Implementierte Änderungen

### app/Services/LexofficeService.php
```php
// Verbesserte Kundentyp-Erkennung
if (isset($lexofficeData['company']['name'])) {
    // Firmenkunde
    $customerType = 'business';
    $name = $lexofficeData['company']['name'];
    $companyName = $lexofficeData['company']['name'];
} elseif (isset($lexofficeData['person'])) {
    // Privatkunde
    $customerType = 'private';
    $firstName = $lexofficeData['person']['firstName'] ?? '';
    $lastName = $lexofficeData['person']['lastName'] ?? '';
    $name = trim($firstName . ' ' . $lastName);
}

// Strenge Adressvalidierung
if (!empty($customer->street) && !empty($customer->city) && !empty($customer->postal_code)) {
    $cleanPostalCode = preg_replace('/[^0-9]/', '', $customer->postal_code);
    if (strlen($cleanPostalCode) === 5) {
        $address = [
            'street' => trim($customer->street),
            'supplement' => '',
            'zip' => $cleanPostalCode,
            'city' => trim($customer->city),
            'countryCode' => $countryCode,
            'isPrimary' => true
        ];
        $data['addresses'] = [$address];
    }
}
```

### app/Models/Customer.php
```php
// Null-sichere Kundennummer-Generierung
foreach ($lastCustomers as $customer) {
    // Überspringe Kunden ohne Kundennummer
    if (empty($customer->customer_number)) {
        continue;
    }
    // ... Rest der Logik
}
```

### app/Models/CompanySetting.php
```php
// Null-sichere extractCustomerNumber Methode
public function extractCustomerNumber(?string $customerNumber): int
{
    if (empty($customerNumber)) {
        return 0;
    }
    
    $parts = explode('-', $customerNumber);
    return (int) end($parts);
}
```

## Test-Ergebnisse

✅ **Kunden-Export:**
- Ohne Adresse: Funktioniert einwandfrei
- Mit vollständiger Adresse: Funktioniert jetzt korrekt
- Mit unvollständiger Adresse: Fallback ohne Adresse funktioniert

✅ **Kunden-Import:**
- Keine null-Wert Fehler mehr
- Automatische Kundennummer-Generierung
- Korrekte Kundentyp-Klassifizierung

✅ **Kundentyp-Korrektur:**
- Geschäftskunden: `customer_type = 'business'` + `company_name` gesetzt
- Privatkunden: `customer_type = 'private'` + nur `name` gesetzt
- Bereits falsch importierte Kunden wurden korrigiert

## Empfehlungen für die Zukunft

1. **Datenqualität:** Sicherstellen, dass Kundenadressen vollständig sind
2. **Validierung:** Adressfelder im Frontend als Pflichtfelder oder alle zusammen optional
3. **Monitoring:** Lexoffice-Logs regelmäßig auf weitere Validierungsfehler überprüfen
4. **Rate Limiting:** Lexoffice API Rate Limits beachten (429 Fehler)
5. **Backup:** Vor größeren Importen Datenbank-Backup erstellen

## Fazit

Alle drei Hauptprobleme der Lexoffice-Integration wurden erfolgreich behoben:
- Export funktioniert mit robuster Adressvalidierung
- Import funktioniert ohne Type-Errors
- Kundentypen werden korrekt klassifiziert

Die Lösung ist robust, getestet und produktionsbereit.
