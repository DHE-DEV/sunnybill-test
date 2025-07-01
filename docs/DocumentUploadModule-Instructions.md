# 📋 Anweisungen für die Verwendung des Dokumenten-Upload-Moduls

## 🎯 Für zukünftige Aufgaben

Wenn Sie mir in Zukunft Aufgaben geben, die mit **Dokumenten-Upload**, **Datei-Upload** oder **Dokumentenverwaltung** zu tun haben, verwenden Sie eine dieser Formulierungen:

### ✅ Einfache Anweisungen

**"Verwende das Dokumenten-Upload-Modul"**
- Ich werde automatisch das [`DocumentUploadTrait`](../app/Traits/DocumentUploadTrait.php) verwenden

**"Nutze das Upload-Modul"**
- Ich werde die modularen Komponenten einsetzen

**"Implementiere mit dem Upload-System"**
- Ich werde die bestehende Infrastruktur nutzen

### 🔧 Spezifische Anweisungen

**"Erstelle einen RelationManager mit Upload-Funktionalität"**
```php
// Ich werde automatisch erstellen:
class XyzDocumentsRelationManager extends RelationManager
{
    use DocumentUploadTrait;
    protected static string $relationship = 'documents';
    
    protected function getDocumentUploadConfig(): array
    {
        return [/* angepasste Konfiguration */];
    }
}
```

**"Füge eine Standalone Upload-Komponente hinzu"**
```blade
// Ich werde automatisch erstellen:
<livewire:document-upload-component 
    :model="$model" 
    relationship="documents"
    :config="[/* Konfiguration */]"
/>
```

**"Implementiere Upload für [Kategorie]"**
```php
// Ich werde automatisch die passende Konfiguration wählen:
DocumentUploadConfig::forImages()     // für Bilder
DocumentUploadConfig::forDocuments()  // für Dokumente
DocumentUploadConfig::forArchives()   // für Archive
```

## 🚫 Was Sie NICHT mehr sagen müssen

Sie müssen **nicht mehr** explizit erwähnen:
- "Erstelle FileUpload-Felder"
- "Implementiere Metadaten-Extraktion"
- "Füge Kategorien hinzu"
- "Erstelle Upload-Formulare"
- "Implementiere Download-Funktionen"

**Das ist alles automatisch im Modul enthalten!**

## 📝 Beispiel-Formulierungen

### Statt:
> "Erstelle einen RelationManager für Lieferanten-Dokumente mit FileUpload, Kategorien, Metadaten-Extraktion, Download-Buttons und Tabelle"

### Sagen Sie einfach:
> "Erstelle einen RelationManager für Lieferanten-Dokumente mit dem Upload-Modul"

### Statt:
> "Implementiere eine Upload-Seite mit Drag & Drop, Kategorien, Vorschau und Statistiken"

### Sagen Sie einfach:
> "Erstelle eine Upload-Seite mit dem Upload-Modul und aktiviere alle Features"

## ⚙️ Konfiguration spezifizieren

Wenn Sie spezielle Anforderungen haben, können Sie diese zusätzlich angeben:

**"Verwende das Upload-Modul mit folgenden Anpassungen:"**
- `directory: 'special-docs'`
- `maxSize: 50MB`
- `nur PDF-Dateien`
- `Kategorien: Vertrag, Rechnung, Zertifikat`
- `Drag & Drop aktivieren`
- `Statistiken anzeigen`

## 🔍 Automatische Erkennung

Ich erkenne automatisch Upload-Anforderungen bei diesen Begriffen:
- "Dokumente", "Dateien", "Upload", "Hochladen"
- "Anhänge", "Attachments", "Files"
- "PDF", "Bilder", "Archive"
- "RelationManager für Dokumente"
- "Dokumentenverwaltung"

## 🎯 Schlüsselwörter für automatische Modul-Verwendung

| Schlüsselwort | Aktion |
|---------------|--------|
| **"Upload-Modul"** | Verwendet das komplette Modul |
| **"DocumentUploadTrait"** | Verwendet nur das Trait |
| **"Upload-Komponente"** | Erstellt Standalone-Komponente |
| **"für Bilder"** | Verwendet `DocumentUploadConfig::forImages()` |
| **"für Dokumente"** | Verwendet `DocumentUploadConfig::forDocuments()` |
| **"minimal"** | Verwendet `DocumentUploadConfig::minimal()` |
| **"alle Features"** | Verwendet `DocumentUploadConfig::full()` |

## 📚 Referenz-Links

- **Vollständige Dokumentation**: [`docs/DocumentUploadModule.md`](./DocumentUploadModule.md)
- **Zusammenfassung**: [`docs/DocumentUploadModule-Summary.md`](./DocumentUploadModule-Summary.md)
- **Test-Command**: `php artisan module:test-document-upload`

## 🎉 Fazit

**Einfach sagen: "Verwende das Upload-Modul"** - und ich werde automatisch die beste Lösung mit allen Features implementieren!

---

*Diese Anweisungen sind Teil des Dokumenten-Upload-Moduls und helfen bei der effizienten Kommunikation für zukünftige Upload-Aufgaben.*