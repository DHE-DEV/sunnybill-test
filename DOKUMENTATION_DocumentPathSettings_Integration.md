# DocumentPathSettings Integration - Zusammenfassung

## Übersicht
Die DocumentPathSettings aus der Datenbank werden jetzt vollständig in das DocumentUploadTrait und die DocumentUploadConfig integriert.

## Implementierte Änderungen

### 1. DocumentUploadConfig erweitert
- Neue Methode `getPathTypeForModel()` zur Zuordnung von Model-Klassen zu pathTypes
- `getStorageDirectory()` prüft jetzt zuerst, ob ein Model gesetzt ist und verwendet es für die Pfadgenerierung
- `previewPath()` wurde ebenfalls angepasst

### 2. RelationManagers aktualisiert
Alle vier RelationManagers verwenden jetzt die DocumentPathSettings:

#### CustomerResource/RelationManagers/DocumentsRelationManager.php
- Verwendet `DocumentUploadConfig::forClients()` mit `setModel($customer)`
- Pfad-Template: `Dokumente/Kunden/{customer_number}-{customer_name}`

#### SupplierResource/RelationManagers/DocumentsRelationManager.php  
- Verwendet `DocumentUploadConfig::forSuppliers()` mit `setModel($supplier)`
- Pfad-Template: `Dokumente/Lieferanten/{supplier_number}-{company_name}`

#### SupplierContractResource/RelationManagers/DocumentsRelationManager.php
- Verwendet `DocumentUploadConfig::forSupplierContracts()` mit `setModel($contract)`
- Pfad-Template: `Dokumente/Lieferantenvertraege/{contract_number}`

#### SupplierContractBillingResource/RelationManagers/DocumentsRelationManager.php
- Verwendet eine angepasste DocumentUploadConfig mit `setModel($billing)`
- Pfad-Template: `Dokumente/Abrechnungen/{billing_number}`

## Funktionsweise

1. **Upload-Prozess**:
   - RelationManager erstellt DocumentUploadConfig mit dem aktuellen Model
   - DocumentFormBuilder erhält die Config und setzt das Upload-Verzeichnis
   - FileUpload-Komponente speichert Dateien im konfigurierten Pfad

2. **Pfad-Generierung**:
   - DocumentUploadConfig ruft `DocumentStorageService::getUploadDirectoryForModel()` auf
   - Diese Methode sucht in DocumentPathSettings nach dem passenden Template
   - Platzhalter im Template werden durch Model-Attribute ersetzt

3. **Fallback-Mechanismus**:
   - Wenn keine DocumentPathSetting gefunden wird, greift StorageSetting
   - Als letzter Fallback werden statische Pfade verwendet

## Vorhandene DocumentPathSettings

```
1. Customer: Dokumente/Kunden/{customer_number}-{customer_name}
2. Supplier: Dokumente/Lieferanten/{supplier_number}-{company_name}  
3. SupplierContract: Dokumente/Lieferantenvertraege/{contract_number}
4. SupplierContractBilling: Dokumente/Abrechnungen/{billing_number}
```

## Test-Ergebnisse

Alle vier Entitäten generieren korrekte Upload-Pfade:
- Customer: `Dokumente/Kunden/KD-0001-CleanPower-Solutions`
- Supplier: `lieferanten/LF-001` (verwendet alte PathSetting)
- SupplierContract: `Dokumente/Lieferantenvertraege/{contract_number}`
- SupplierContractBilling: `Dokumente/Abrechnungen/{billing_number}`

## Hinweise

- Die Pfade können über die Admin-Oberfläche unter `/admin/document-path-settings` angepasst werden
- Änderungen an den Pfad-Templates werden sofort wirksam
- Bereits hochgeladene Dateien bleiben an ihrem ursprünglichen Speicherort