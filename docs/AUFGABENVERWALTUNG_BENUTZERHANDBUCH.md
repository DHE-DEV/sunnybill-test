# Aufgabenverwaltung - Benutzerhandbuch

**Version:** 1.0  
**Datum:** 17. Juli 2025  
**Bearbeiter:** System Administrator

---

## Inhaltsverzeichnis

1. [Einführung](#1-einführung)
2. [Zugang zur Aufgabenverwaltung](#2-zugang-zur-aufgabenverwaltung)
3. [Aufgaben-Übersicht](#3-aufgaben-übersicht)
4. [Neue Aufgabe erstellen](#4-neue-aufgabe-erstellen)
5. [Aufgaben bearbeiten](#5-aufgaben-bearbeiten)
6. [Aufgaben-Status und Prioritäten](#6-aufgaben-status-und-prioritäten)
7. [Zuordnungen und Verantwortlichkeiten](#7-zuordnungen-und-verantwortlichkeiten)
8. [Unteraufgaben](#8-unteraufgaben)
9. [Termine und Fälligkeiten](#9-termine-und-fälligkeiten)
10. [Zeiterfassung](#10-zeiterfassung)
11. [Filter und Suche](#11-filter-und-suche)
12. [Bulk-Aktionen](#12-bulk-aktionen)
13. [Notizen und Dokumente](#13-notizen-und-dokumente)
14. [Wiederkehrende Aufgaben](#14-wiederkehrende-aufgaben)
15. [Labels und Kategorisierung](#15-labels-und-kategorisierung)
16. [Aufgaben duplizieren](#16-aufgaben-duplizieren)
17. [Berichtswesen](#17-berichtswesen)
18. [Tipps und Best Practices](#18-tipps-und-best-practices)
19. [Häufig gestellte Fragen](#19-häufig-gestellte-fragen)

---

## 1. Einführung

Die Aufgabenverwaltung ist ein zentrales Modul des SunnyBill-Systems, das eine effiziente Verwaltung, Verfolgung und Koordination von Arbeitsaufgaben ermöglicht. Das System unterstützt Teams bei der Organisation komplexer Projekte und bietet umfassende Funktionen für die Aufgabenplanung und -verfolgung.

### 1.1 Hauptfunktionen

- **Aufgabenerstellung und -verwaltung**: Erstellen, bearbeiten und löschen von Aufgaben
- **Hierarchische Strukturierung**: Haupt- und Unteraufgaben für komplexe Projekte
- **Status-Tracking**: Vollständige Verfolgung des Aufgabenfortschritts
- **Zeiterfassung**: Schätzung und Erfassung von Arbeitszeiten
- **Terminplanung**: Fälligkeitsdaten und Terminüberwachung
- **Zuordnungssystem**: Flexible Zuweisung von Verantwortlichkeiten
- **Filterfunktionen**: Erweiterte Such- und Filtermöglichkeiten
- **Berichtswesen**: Umfassende Reporting-Funktionen

### 1.2 Zielgruppe

Dieses Handbuch richtet sich an:
- Projektmanager und Teamleiter
- Mitarbeiter, die Aufgaben bearbeiten
- Administratoren des Systems
- Alle Benutzer, die mit der Aufgabenverwaltung arbeiten

---

## 2. Zugang zur Aufgabenverwaltung

### 2.1 Navigation

1. Melden Sie sich im SunnyBill-System an
2. Klicken Sie im Hauptmenü auf "Aufgaben"
3. Die Aufgaben-Übersicht wird angezeigt

### 2.2 Berechtigungen

- **Lesen**: Alle Benutzer können Aufgaben einsehen
- **Erstellen**: Berechtigung zum Erstellen neuer Aufgaben
- **Bearbeiten**: Berechtigung zum Bearbeiten bestehender Aufgaben
- **Löschen**: Berechtigung zum Löschen von Aufgaben
- **Admin**: Vollzugriff auf alle Aufgaben-Funktionen

---

## 3. Aufgaben-Übersicht

### 3.1 Tabellenansicht

Die Hauptansicht zeigt alle Aufgaben in einer übersichtlichen Tabelle mit folgenden Spalten:

**Standardspalten:**
- **Titel**: Aufgabentitel mit Kurzbeschreibung
- **Typ**: Aufgabentyp (konfigurierbar)
- **Priorität**: Niedrig, Mittel, Hoch, Dringend
- **Status**: Aktueller Bearbeitungsstatus
- **Fällig am**: Fälligkeitsdatum mit Farbkodierung
- **Zuständig**: Zugewiesene Person
- **Unteraufgaben**: Anzahl der Unteraufgaben

**Zusätzliche Spalten (ein-/ausblendbar):**
- **Nr.**: Automatisch generierte Aufgabennummer
- **Gehört zu**: Übergeordnete Aufgabe
- **Inhaber**: Aufgabeninhaber
- **Kunde**: Zugeordneter Kunde
- **Lieferant**: Zugeordneter Lieferant
- **Solaranlage**: Zugeordnete Solaranlage
- **Geschätzt**: Geschätzte Arbeitszeit
- **Tatsächlich**: Tatsächlich benötigte Zeit
- **Erstellt von**: Ersteller der Aufgabe
- **Erstellt am**: Erstellungsdatum
- **Abgeschlossen am**: Abschlussdatum

### 3.2 Farbkodierung

- **Rot**: Überfällige Aufgaben
- **Orange**: Heute fällige Aufgaben
- **Blau**: Normale Aufgaben
- **Grau**: Abgeschlossene Aufgaben

### 3.3 Sortierung

- Klicken Sie auf Spaltenüberschriften zum Sortieren
- Standardsortierung: Nach Fälligkeitsdatum (aufsteigend)
- Sortierung wird in der Sitzung gespeichert

---

## 4. Neue Aufgabe erstellen

### 4.1 Grunddaten

1. Klicken Sie auf "Neue Aufgabe"
2. Füllen Sie die Grunddaten aus:

**Pflichtfelder:**
- **Titel**: Aussagekräftiger Aufgabentitel
- **Aufgabentyp**: Wählen Sie aus vorkonfigurierten Typen
- **Priorität**: Niedrig, Mittel, Hoch, Dringend
- **Status**: Standardmäßig "Offen"

**Optionale Felder:**
- **Beschreibung**: Detaillierte Aufgabenbeschreibung
- **Aufgabennummer**: Wird automatisch generiert

### 4.2 Termine und Zeit

- **Fälligkeitsdatum**: Wann soll die Aufgabe abgeschlossen sein
- **Fälligkeitszeit**: Spezifische Uhrzeit (optional)
- **Geschätzte Minuten**: Zeitschätzung für die Bearbeitung
- **Tatsächliche Minuten**: Wird während der Bearbeitung erfasst

### 4.3 Zuordnungen

- **Zugewiesen an**: Bearbeiter der Aufgabe
- **Inhaber**: Verantwortlicher für die Aufgabe
- **Kunde**: Zuordnung zu einem Kunden
- **Lieferant**: Zuordnung zu einem Lieferanten
- **Solaranlage**: Zuordnung zu einer Solaranlage
- **Übergeordnete Aufgabe**: Für Unteraufgaben

### 4.4 Erweiterte Optionen

- **Labels**: Frei definierbare Schlagwörter
- **Wiederkehrend**: Checkbox für wiederkehrende Aufgaben
- **Wiederholungsmuster**: Muster für Wiederholungen
- **Sortierreihenfolge**: Numerischer Wert für Sortierung

---

## 5. Aufgaben bearbeiten

### 5.1 Aufgabe öffnen

1. Klicken Sie auf eine Aufgabe in der Übersicht
2. Wählen Sie "Bearbeiten" aus dem Aktionsmenü
3. Oder klicken Sie auf den Aufgabentitel

### 5.2 Bearbeitungsoptionen

- **Alle Felder bearbeitbar**: Änderungen an allen Aufgabenfeldern
- **Statusänderung**: Direktes Ändern des Status
- **Zeitzuweisung**: Aktualisierung der Arbeitszeiten
- **Zuordnungen ändern**: Neuzuweisung von Verantwortlichkeiten

### 5.3 Speichern und Abbrechen

- **Speichern**: Alle Änderungen werden übernommen
- **Abbrechen**: Änderungen werden verworfen
- **Automatisches Speichern**: Bei kritischen Änderungen

---

## 6. Aufgaben-Status und Prioritäten

### 6.1 Status-Übersicht

**Offen**: Aufgabe wurde erstellt, aber noch nicht begonnen
- Standardstatus für neue Aufgaben
- Bereit zur Bearbeitung

**In Bearbeitung**: Aufgabe wird aktiv bearbeitet
- Zeigt laufende Arbeit an
- Kann Zeiterfassung auslösen

**Warte auf Extern**: Aufgabe wartet auf externe Rückmeldung
- Blockiert durch externe Faktoren
- Verfolgt Abhängigkeiten

**Warte auf Intern**: Aufgabe wartet auf interne Rückmeldung
- Blockiert durch interne Faktoren
- Eskalation möglich

**Abgeschlossen**: Aufgabe wurde erfolgreich beendet
- Automatische Zeitstempel
- Archivierung möglich

**Abgebrochen**: Aufgabe wurde eingestellt
- Dokumentation des Abbruchgrunds
- Nachverfolgung möglich

### 6.2 Prioritäten

**Niedrig**: Routine-Aufgaben ohne Zeitdruck
- Kann später bearbeitet werden
- Keine besondere Dringlichkeit

**Mittel**: Standard-Priorität für normale Aufgaben
- Reguläre Bearbeitungszeit
- Standardeinstellung

**Hoch**: Wichtige Aufgaben mit erhöhter Priorität
- Bevorzugte Bearbeitung
- Überwachung erforderlich

**Dringend**: Kritische Aufgaben mit höchster Priorität
- Sofortige Bearbeitung erforderlich
- Eskalation und Benachrichtigung

### 6.3 Status-Workflow

1. **Offen** → **In Bearbeitung** (Arbeit beginnen)
2. **In Bearbeitung** → **Warte auf Extern/Intern** (Blockierung)
3. **Warte auf Extern/Intern** → **In Bearbeitung** (Fortsetzung)
4. **In Bearbeitung** → **Abgeschlossen** (Erfolgreiche Beendigung)
5. **Jeder Status** → **Abgebrochen** (Einstellung der Arbeit)

---

## 7. Zuordnungen und Verantwortlichkeiten

### 7.1 Rollen-Konzept

**Ersteller**: Person, die die Aufgabe erstellt hat
- Automatisch bei Erstellung zugewiesen
- Kann nicht geändert werden
- Vollzugriff auf die Aufgabe

**Inhaber**: Verantwortlicher für die Aufgabe
- Überwacht den Fortschritt
- Kann Aufgabe zuweisen
- Berichtspflicht

**Zugewiesen an**: Bearbeiter der Aufgabe
- Führt die eigentliche Arbeit aus
- Kann Status ändern
- Zeiterfassung

### 7.2 Zuordnungslogik

- **Mehrfachzuordnung**: Aufgaben können mehreren Personen zugewiesen werden
- **Benachrichtigungen**: Automatische Mitteilungen bei Zuordnungsänderungen
- **Berechtigung**: Nur Inhaber und Ersteller können Zuordnungen ändern

### 7.3 Externe Zuordnungen

**Kunde**: Verknüpfung mit Kundendatensatz
- Kundenspezifische Aufgaben
- Projektbezogene Zuordnung
- Abrechnungsrelevanz

**Lieferant**: Verknüpfung mit Lieferantendatensatz
- Lieferantenbezogene Aufgaben
- Bestellprozesse
- Qualitätskontrolle

**Solaranlage**: Verknüpfung mit Anlagendatensatz
- Anlagenspezifische Wartung
- Installationsprozesse
- Betriebsaufgaben

---

## 8. Unteraufgaben

### 8.1 Hierarchische Struktur

- **Hauptaufgaben**: Übergeordnete Aufgaben ohne Parent
- **Unteraufgaben**: Aufgaben mit übergeordneter Aufgabe
- **Mehrstufigkeit**: Unbegrenzte Hierarchie-Ebenen

### 8.2 Unteraufgabe erstellen

1. Wählen Sie eine bestehende Aufgabe als Hauptaufgabe
2. Erstellen Sie eine neue Aufgabe
3. Wählen Sie die Hauptaufgabe im Feld "Übergeordnete Aufgabe"
4. Speichern Sie die Unteraufgabe

### 8.3 Unteraufgaben-Verwaltung

**Relation Manager**: Direkter Zugang zu Unteraufgaben
- Übersicht aller Unteraufgaben
- Direktes Bearbeiten möglich
- Status-Übersicht

**Vererbung**: Automatische Übernahme von Attributen
- Kunde, Lieferant, Solaranlage
- Priorität (optional)
- Labels (optional)

**Fortschrittsberechnung**: Automatische Berechnung des Gesamtfortschritts
- Prozentuale Darstellung
- Gewichtung möglich
- Abhängigkeiten beachten

---

## 9. Termine und Fälligkeiten

### 9.1 Fälligkeitsdaten

**Fälligkeitsdatum**: Zieldatum für die Aufgabenerledigung
- Datumswahl über Kalender
- Farbkodierung in der Übersicht
- Automatische Benachrichtigungen

**Fälligkeitszeit**: Spezifische Uhrzeit für die Fälligkeit
- Minutengenaue Planung
- Terminkalender-Integration
- Reminder-Funktionen

### 9.2 Überfällige Aufgaben

**Automatische Erkennung**: System erkennt überfällige Aufgaben
- Rote Farbkodierung
- Warnmeldungen
- Eskalation möglich

**Filter**: Spezielle Filter für überfällige Aufgaben
- "Überfällig" Filter
- "Heute fällig" Filter
- Kombinierbare Filter

### 9.3 Terminplanung

**Projektplanung**: Integration in größere Projekte
- Meilenstein-Verknüpfung
- Abhängigkeiten beachten
- Kritischer Pfad

**Ressourcenplanung**: Berücksichtigung von Kapazitäten
- Personelle Ressourcen
- Technische Ressourcen
- Terminkonkurrenz

---

## 10. Zeiterfassung

### 10.1 Zeitschätzung

**Geschätzte Minuten**: Vorab-Schätzung der benötigten Zeit
- Planungsgrundlage
- Kapazitätsplanung
- Vergleichswerte

**Schätzungsgenauigkeit**: Verbesserung durch Erfahrung
- Historische Daten
- Anpassung bei Bedarf
- Lerneffekt

### 10.2 Zeiterfassung

**Tatsächliche Minuten**: Erfassung der tatsächlich benötigten Zeit
- Manuelle Eingabe
- Automatische Erfassung (optional)
- Nachträgliche Korrektur

**Zeitbuchung**: Detaillierte Zeiterfassung
- Stunden und Minuten
- Tätigkeitsbeschreibung
- Datum und Uhrzeit

### 10.3 Zeitauswertung

**Abweichungsanalyse**: Vergleich Schätzung vs. Realität
- Prozentuale Abweichung
- Trend-Analyse
- Verbesserungsmaßnahmen

**Reporting**: Zeitbasierte Berichte
- Personenbezogene Auswertung
- Projektbezogene Auswertung
- Periodische Berichte

---

## 11. Filter und Suche

### 11.1 Standardfilter

**Aufgabentyp**: Filterung nach konfigurierten Typen
- Dropdown-Auswahl
- Mehrfachauswahl
- Dynamische Ladung

**Status**: Filterung nach Bearbeitungsstatus
- Alle Status verfügbar
- Kombinierbar
- Speicherbar

**Priorität**: Filterung nach Prioritätsstufen
- Niedrig bis Dringend
- Kombinierbar
- Farbkodierung

**Zugewiesen an**: Filterung nach Bearbeitern
- Personenauswahl
- Suchfunktion
- Mehrfachauswahl

### 11.2 Erweiterte Filter

**Überfällig**: Spezialfilter für überfällige Aufgaben
- Ein-/Ausschaltbar
- Automatische Aktualisierung
- Benachrichtigungen

**Heute fällig**: Filter für heute fällige Aufgaben
- Tagesaktuelle Ansicht
- Prioritätsfokus
- Terminübersicht

**Hohe Priorität**: Filter für High-Priority-Aufgaben
- Hoch und Dringend
- Eskalations-Ansicht
- Management-Fokus

**Hauptaufgaben**: Filter für übergeordnete Aufgaben
- Projektübersicht
- Strukturierte Ansicht
- Planungsfokus

### 11.3 Personalisierte Filter

**Meine Aufgaben**: Alle eigenen Aufgaben
- Erstellt, zugewiesen, Inhaber
- Persönliche Arbeitsansicht
- Priorisierung

**Mir zugewiesen**: Nur zugewiesene Aufgaben
- Aktuelle Arbeitsliste
- Bearbeitungsfokus
- Terminübersicht

**Ich bin Inhaber**: Aufgaben in eigener Verantwortung
- Überwachungsansicht
- Managementfokus
- Fortschrittskontrolle

### 11.4 Suche

**Volltext-Suche**: Suche in allen Textfeldern
- Titel, Beschreibung, Notizen
- Automatische Vervollständigung
- Erweiterte Suchoperatoren

**Globale Suche**: Systemweite Suche
- Alle Module durchsuchen
- Schnellzugriff
- Kontextuelle Ergebnisse

---

## 12. Bulk-Aktionen

### 12.1 Mehrfachauswahl

**Auswahl**: Checkboxen für Mehrfachauswahl
- Einzelauswahl
- Alle auswählen
- Bereichsauswahl

**Aktionen**: Verfügbare Bulk-Aktionen
- Status ändern
- Zuweisen
- Löschen
- Duplizieren

### 12.2 Bulk-Bearbeitung

**Status ändern**: Mehrere Aufgaben gleichzeitig
- Neuen Status auswählen
- Bestätigung erforderlich
- Protokollierung

**Zuweisen**: Mehrere Aufgaben neu zuweisen
- Person auswählen
- Benachrichtigung
- Übertragung

**Löschen**: Mehrere Aufgaben löschen
- Sicherheitsabfrage
- Soft-Delete
- Wiederherstellung

### 12.3 Bulk-Duplizierung

**Duplizieren**: Mehrere Aufgaben kopieren
- Alle Attribute kopieren
- Unteraufgaben einbeziehen
- Anpassungen möglich

**Anwendungsfälle**:
- Routine-Aufgaben
- Projektvorlagen
- Saisonale Arbeiten

---

## 13. Notizen und Dokumente

### 13.1 Notizen-System

**Aufgabennotizen**: Detaillierte Notizen zu Aufgaben
- Chronologische Reihenfolge
- Versionierung
- Benachrichtigungen

**Erstellung**: Neue Notizen hinzufügen
- Rich-Text-Editor
- Formatierungsoptionen
- Anhänge möglich

**Erwähnungen**: Personen in Notizen erwähnen
- @-Notation
- Automatische Benachrichtigung
- Verknüpfung

### 13.2 Dokumenten-Management

**Dateien anhängen**: Dokumente zu Aufgaben
- Verschiedene Dateiformate
- Drag-and-Drop
- Versionskontrolle

**Dokumententypen**:
- Bilder (JPG, PNG, GIF)
- Dokumente (PDF, DOC, XLS)
- Archive (ZIP, RAR)
- Andere Formate

**Dokumentenorganisation**:
- Ordnerstruktur
- Tagging-System
- Suchfunktion

### 13.3 Relation Manager

**Notizen-Tab**: Alle Notizen zur Aufgabe
- Chronologische Ansicht
- Bearbeitungshistorie
- Filterfunktionen

**Dokumente-Tab**: Alle Dokumente zur Aufgabe
- Vorschau-Funktion
- Download-Links
- Metadaten

---

## 14. Wiederkehrende Aufgaben

### 14.1 Konzept

**Wiederholungsmuster**: Regelmäßige Aufgaben
- Täglich, wöchentlich, monatlich
- Benutzerdefinierte Intervalle
- Flexible Konfiguration

**Automatisierung**: Automatische Erstellung
- Terminbasierte Generierung
- Statusbasierte Auslösung
- Konditionelle Erstellung

### 14.2 Konfiguration

**Wiederholungseinstellungen**:
- **Täglich**: Jeden Tag, Werktage, bestimmte Tage
- **Wöchentlich**: Bestimmte Wochentage, Intervalle
- **Monatlich**: Bestimmte Tage, Monatsende
- **Jährlich**: Bestimmte Daten, Jahrestage

**Muster-Syntax**:
- `daily`: Täglich
- `weekly`: Wöchentlich
- `monthly`: Monatlich
- `yearly`: Jährlich
- `custom`: Benutzerdefiniert

### 14.3 Verwaltung

**Serien-Verwaltung**: Alle Wiederholungen verwalten
- Serien bearbeiten
- Einzelaufgaben ändern
- Serien beenden

**Ausnahmen**: Unterbrechungen in Serien
- Einzelne Termine überspringen
- Temporäre Änderungen
- Sonderbehandlung

---

## 15. Labels und Kategorisierung

### 15.1 Label-System

**Frei definierbare Tags**: Flexible Kategorisierung
- Keine Beschränkung
- Mehrere Labels pro Aufgabe
- Automatische Vervollständigung

**Anwendungsbereiche**:
- Projektbezogene Labels
- Prioritätslabels
- Abteilungslabels
- Statuslabels

### 15.2 Label-Verwaltung

**Hinzufügen**: Labels zu Aufgaben hinzufügen
- Eingabe über Tastatur
- Auswahl aus Vorschlägen
- Automatische Erstellung

**Entfernen**: Labels von Aufgaben entfernen
- Einzelnes Entfernen
- Alle Labels entfernen
- Bulk-Entfernung

### 15.3 Label-Auswertung

**Filterung**: Nach Labels filtern
- Schnellfilter
- Kombinierte Filter
- Erweiterte Suche

**Berichte**: Label-basierte Berichte
- Häufigkeit
- Verteilung
- Trends

---

## 16. Aufgaben duplizieren

### 16.1 Duplizierungsoptionen

**Einzelduplizierung**: Einzelne Aufgaben kopieren
- Alle Attribute kopieren
- Unteraufgaben einbeziehen
- Anpassungen vor Speicherung

**Bulk-Duplizierung**: Mehrere Aufgaben kopieren
- Stapelverarbeitung
- Konsistente Kopierung
- Effizienzsteigerung

### 16.2 Duplizierungsumfang

**Kopierte Attribute**:
- Titel und Beschreibung
- Aufgabentyp
- Priorität
- Zuordnungen
- Labels
- Termine (optional)

**Nicht kopierte Attribute**:
- Aufgabennummer (neu generiert)
- Erstellungsdatum
- Bearbeitungshistorie
- Spezifische Notizen

### 16.3 Anwendungsfälle

**Projektvorlagen**: Standardaufgaben-Sets
- Wiederkehrende Projekte
- Checklisten
- Arbeitsabläufe

**Saisonale Arbeiten**: Regelmäßige Aufgaben
- Wartungsarbeiten
- Berichte
- Prüfungen

---

## 17. Berichtswesen

### 17.1 Standard-Berichte

**Aufgabenverteilung**: Verteilung nach Personen
- Zugewiesene Aufgaben
- Workload-Analyse
- Kapazitätsplanung

**Status-Berichte**: Fortschrittsberichte
- Abschlussquoten
- Verzögerungsanalyse
- Trend-Entwicklung

**Prioritäts-Berichte**: Prioritätsverteilung
- Dringlichkeitsanalyse
- Ressourcenallokation
- Eskalationsberichte

### 17.2 Zeitbasierte Berichte

**Zeiterfassung**: Arbeitszeit-Berichte
- Geschätzt vs. Tatsächlich
- Effizienzanalyse
- Kostenschätzung

**Terminberichte**: Fälligkeits-Berichte
- Überfällige Aufgaben
- Kommende Termine
- Terminplanung

### 17.3 Benutzerdefinierte Berichte

**Filter-Berichte**: Basierend auf Filtern
- Gespeicherte Filter
- Automatische Aktualisierung
- Exportfunktionen

**Dashboard-Integration**: Berichte im Dashboard
- Grafische Darstellung
- Echtzeitdaten
- Interaktive Elemente

---

## 18. Tipps und Best Practices

### 18.1 Aufgabenerstellung

**Aussagekräftige Titel**: Klare, verständliche Titel
- Aktionsverben verwenden
- Spezifisch formulieren
- Kontext einbeziehen

**Detaillierte Beschreibungen**: Vollständige Informationen
- Ziele definieren
- Anforderungen auflisten
- Ressourcen benennen

**Realistische Schätzungen**: Angemessene Zeitschätzungen
- Puffer einplanen
- Komplexität berücksichtigen
- Erfahrungswerte nutzen

### 18.2 Aufgabenorganisation

**Hierarchische Struktur**: Komplexe Aufgaben unterteilen
- Logische Aufteilung
- Überschaubare Teilaufgaben
- Klare Abhängigkeiten

**Konsistente Kategorisierung**: Einheitliche Labels
- Standardisierte Begriffe
- Konsistente Anwendung
- Teamweite Abstimmung

**Regelmäßige Aktualisierung**: Status pflegen
- Tägliche Überprüfung
- Zeitnahe Aktualisierung
- Kommunikation sicherstellen

### 18.3 Teamarbeit

**Klare Zuordnungen**: Eindeutige Verantwortlichkeiten
- Eine Person pro Aufgabe
- Klare Rollendefinition
- Vertretungsregelungen

**Regelmäßige Kommunikation**: Abstimmung im Team
- Statusmeetings
- Notizen-System nutzen
- Probleme frühzeitig ansprechen

**Dokumentation**: Vollständige Dokumentation
- Arbeitsergebnisse festhalten
- Entscheidungen dokumentieren
- Wissen teilen

---

## 19. Häufig gestellte Fragen

### 19.1 Allgemeine Fragen

**F: Wie kann ich alle meine Aufgaben auf einmal sehen?**
A: Verwenden Sie den Filter "Meine Aufgaben" in der Aufgaben-Übersicht. Dieser zeigt alle Aufgaben an, die Sie erstellt haben, die Ihnen zugewiesen sind oder bei denen Sie als Inhaber eingetragen sind.

**F: Warum kann ich eine Aufgabe nicht bearbeiten?**
A: Überprüfen Sie Ihre Berechtigungen. Sie können nur Aufgaben bearbeiten, die Sie erstellt haben, die Ihnen zugewiesen sind oder bei denen Sie als Inhaber eingetragen sind. Bei Problemen wenden Sie sich an Ihren Administrator.

**F: Wie kann ich eine Aufgabe als dringend markieren?**
A: Bearbeiten Sie die Aufgabe und setzen Sie die Priorität auf "Dringend". Dringend markierte Aufgaben werden in der Übersicht rot hervorgehoben.

**F: Kann ich Aufgaben per E-Mail erhalten?**
A: Ja, das System sendet automatisch E-Mail-Benachrichtigungen bei Aufgabenzuweisungen, Statusänderungen und Erwähnungen in Notizen.

### 19.2 Technische Fragen

**F: Warum werden meine Filter nicht gespeichert?**
A: Stellen Sie sicher, dass Cookies in Ihrem Browser aktiviert sind. Die Filtereinstellungen werden in der Browsersitzung gespeichert.

**F: Wie kann ich eine Aufgabe wiederherstellen, die ich versehentlich gelöscht habe?**
A: Gelöschte Aufgaben werden zunächst in den Papierkorb verschoben. Verwenden Sie den "Papierkorb" Filter und stellen Sie die Aufgabe wieder her.

**F: Warum kann ich keine Dokumente hochladen?**
A: Überprüfen Sie die Dateigröße und das Format. Unterstützte Formate sind PDF, DOC, XLS, JPG, PNG. Die maximale Dateigröße ist in den Systemeinstellungen definiert.

### 19.3 Zeiterfassung

**F: Wie genau sollte ich die Arbeitszeit schätzen?**
A: Schätzen Sie realistisch und planen Sie Puffer ein. Nutzen Sie historische Daten ähnlicher Aufgaben als Referenz.

**F: Wird die Arbeitszeit automatisch erfasst?**
A: Nein, die Zeiterfassung erfolgt manuell. Tragen Sie die tatsächlich benötigte Zeit nach Abschluss der Aufgabe ein.

**F: Kann ich nachträglich Zeiten korrigieren?**
A: Ja, Sie können jederzeit die erfassten Zeiten korrigieren, solange Sie Bearbeitungsrechte für die Aufgabe haben.

### 19.4 Problemlösungen

**F: Die Aufgabenübersicht lädt sehr langsam. Was kann ich tun?**
A: Reduzieren Sie die Anzahl der angezeigten Aufgaben durch Filter. Blenden Sie nicht benötigte Spalten aus. Kontaktieren Sie bei anhaltenden Problemen den Administrator.

**F: Ich erhalte keine Benachrichtigungen. Was ist zu tun?**
A: Überprüfen Sie Ihre E-Mail-Einstellungen im Benutzerprofil. Stellen Sie sicher, dass SunnyBill-E-Mails nicht im Spam-Ordner landen.

**F: Kann ich Aufgaben offline bearbeiten?**
A: Nein, das System erfordert eine Internetverbindung. Alle Änderungen werden in Echtzeit synchronisiert.

---

## Anhang

### A.1 Tastaturkürzel

- **Strg + N**: Neue Aufgabe erstellen
- **Strg + S**: Aufgabe speichern
- **Strg + F**: Suche aktivieren
- **Esc**: Bearbeitung abbrechen
- **Enter**: Aufgabe öffnen (in Übersicht)

### A.2 Wichtige Begriffe

**Aufgabentyp**: Vorkonfigurierte Kategorien für Aufgaben
**Bulk-Aktion**: Massenbearbeitung mehrerer Aufgaben
**Label**: Frei definierbare Schlagwörter
**Relation Manager**: Verwaltung verknüpfter Daten
**Soft Delete**: Löschung mit Wiederherstellungsmöglichkeit

### A.3 Systemanforderungen

**Browser**: Chrome, Firefox, Safari, Edge (aktuelle Versionen)
**Internet**: Stabile Internetverbindung erforderlich
**Auflösung**: Mindestens 1024x768 Pixel
**JavaScript**: Muss aktiviert sein

### A.4 Support und Hilfe

Bei Fragen zur Aufgabenverwaltung wenden Sie sich an:

**Technischer Support**:
- E-Mail: support@sunnybill.de
- Telefon: +49 (0) 123 456 789
- Servicezeiten: Mo-Fr 8:00-18:00 Uhr

**Schulungen**:
- Regelmäßige Webinare
- Individuelle Schulungen
- Online-Tutorials

**Dokumentation**:
- Aktuelle Version online verfügbar
- Changelog bei Updates
- Best-Practice-Beispiele

---

**Ende der Dokumentation**

*Dieses Handbuch wurde am 17. Juli 2025 erstellt und entspricht der aktuellen Version des SunnyBill-Systems. Änderungen und Ergänzungen vorbehalten.*
