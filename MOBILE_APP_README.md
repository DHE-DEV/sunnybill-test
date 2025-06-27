# SunnyBill Mobile App

Eine moderne mobile Anwendung für das SunnyBill Solar Management System, entwickelt mit Laravel, Vue.js, Tailwind CSS und Framework7.

## 🚀 Features

### ✅ Implementiert
- **Responsive Design**: Optimiert für Smartphones und Tablets
- **Framework7 UI**: Native App-ähnliche Benutzeroberfläche
- **Vue.js Integration**: Moderne JavaScript-Framework-Integration
- **Tailwind CSS**: Utility-first CSS-Framework für schnelles Styling
- **Login System**: Demo-Login mit lokaler Authentifizierung
- **Dashboard**: Übersichtliche Darstellung wichtiger Kennzahlen
- **Navigation**: Bottom-Tab-Navigation für einfache Bedienung

### 📱 Seiten
1. **Home/Startseite**: Willkommensseite mit Schnellzugriff
2. **Login**: Anmeldeformular mit Demo-Zugangsdaten
3. **Dashboard**: Kennzahlen und Aktivitäten-Übersicht
4. **Solaranlagen**: Liste aller Solaranlagen mit Status
5. **Kunden**: Kundenverwaltung mit Suchfunktion
6. **Rechnungen**: Rechnungsübersicht mit Filteroptionen
7. **Profil**: Benutzereinstellungen und Account-Verwaltung

## 🛠 Technologie-Stack

- **Backend**: Laravel 11
- **Frontend**: Vue.js 3
- **UI Framework**: Framework7 8.3.3
- **CSS**: Tailwind CSS 4.0
- **Build Tool**: Vite 6.2.4
- **Icons**: Framework7 Icons

## 📱 Demo-Zugangsdaten

```
E-Mail: demo@sunnybill.de
Passwort: demo123
```

## 🚀 Installation & Setup

1. **Dependencies installieren**:
   ```bash
   npm install
   ```

2. **Development Server starten**:
   ```bash
   npm run dev
   ```

3. **Laravel Server starten**:
   ```bash
   php artisan serve
   ```

4. **Mobile App aufrufen**:
   ```
   http://localhost:8000/app
   ```

## 📱 App-Struktur

```
resources/
├── js/
│   ├── mobile-app.js              # Haupt-JavaScript-Datei
│   └── components/
│       ├── MobileApp.vue          # Haupt-App-Komponente
│       └── pages/
│           ├── HomePage.vue       # Startseite
│           ├── LoginPage.vue      # Login-Seite
│           ├── DashboardPage.vue  # Dashboard
│           ├── SolarPlantsPage.vue # Solaranlagen
│           ├── CustomersPage.vue  # Kunden
│           ├── InvoicesPage.vue   # Rechnungen
│           └── ProfilePage.vue    # Profil
├── css/
│   └── mobile-app.css             # App-spezifische Styles
└── views/
    └── mobile-app.blade.php       # Haupt-Template
```

## 🎨 Design-Features

- **Gradient-Hintergründe**: Moderne Farbverläufe
- **Card-basiertes Layout**: Übersichtliche Informationsdarstellung
- **Responsive Icons**: Framework7 Icons für konsistente Darstellung
- **Touch-optimiert**: Große Touch-Targets für mobile Geräte
- **Loading States**: Benutzerfreundliche Ladezustände
- **Toast Notifications**: Feedback für Benutzeraktionen

## 🔧 Konfiguration

### Vite-Konfiguration
Die `vite.config.js` wurde erweitert um:
- Vue.js Plugin-Support
- Mobile-App-spezifische Assets
- Framework7-Integration

### Route-Konfiguration
Neue Route in `routes/web.php`:
```php
Route::get('/app/{any?}', function () {
    return view('mobile-app');
})->where('any', '.*')->name('mobile.app');
```

## 📊 Demo-Daten

Die App enthält realistische Demo-Daten für:
- **12 Solaranlagen** mit verschiedenen Status
- **45 Kunden** mit unterschiedlichen Profilen
- **128 Rechnungen** in verschiedenen Zuständen
- **€125.430 Gesamtumsatz** als Beispiel-Kennzahl

## 🔮 Geplante Features

- [ ] API-Integration mit Laravel Backend
- [ ] Offline-Funktionalität (PWA)
- [ ] Push-Notifications
- [ ] Biometrische Authentifizierung
- [ ] Datenexport-Funktionen
- [ ] Erweiterte Filteroptionen
- [ ] Grafiken und Charts
- [ ] Kamera-Integration für Dokumentenerfassung

## 📱 Browser-Kompatibilität

- ✅ Chrome/Chromium (empfohlen)
- ✅ Safari (iOS/macOS)
- ✅ Firefox
- ✅ Edge
- ✅ Mobile Browser (iOS Safari, Chrome Mobile)

## 🎯 Verwendung

1. **App öffnen**: Navigieren Sie zu `/app`
2. **Anmelden**: Verwenden Sie die Demo-Zugangsdaten
3. **Navigation**: Nutzen Sie die Bottom-Tab-Navigation
4. **Features testen**: Erkunden Sie alle verfügbaren Seiten

## 🔒 Sicherheit

- Lokale Token-basierte Authentifizierung
- Session-Management
- Sichere Route-Behandlung
- Input-Validierung

## 📞 Support

Bei Fragen oder Problemen:
- E-Mail: support@sunnybill.de
- Telefon: +49 123 456 789

---

**Entwickelt mit ❤️ für eine nachhaltige Zukunft**