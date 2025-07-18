# VoltMaster Mobile App

Eine React Native/Expo App für die Aufgabenverwaltung im VoltMaster System.

## 🚀 Quick Start

### Voraussetzungen

1. **Node.js** (Version 16 oder höher)
2. **Expo CLI** global installiert:
   ```bash
   npm install -g @expo/cli
   ```

### Installation

1. **In das mobile-app Verzeichnis wechseln:**
   ```bash
   cd mobile-app
   ```

2. **Dependencies installieren:**
   ```bash
   npm install
   ```

### App starten

**Für Entwicklung (empfohlen):**
```bash
npm start
```
oder
```bash
expo start
```

**Für spezifische Plattformen:**
```bash
# Android
npm run android

# iOS
npm run ios

# Web Browser
npm run web
```

## 📱 App verwenden

### Mit automatischem Token (Entwicklung)

Wenn ein `APP_TOKEN` in der `.env` Datei gesetzt ist:
- App startet automatisch ohne Login-Screen
- Direkte Anmeldung mit dem konfigurierten Token
- Sofortiger Zugang zur Aufgabenverwaltung

### Mit manuellem Token

Wenn kein `APP_TOKEN` in der `.env` Datei gesetzt ist:
- Login-Screen wird angezeigt
- Token manuell eingeben
- Token wird sicher gespeichert für zukünftige Nutzung

## 🔧 Konfiguration

### .env Datei

```env
# API-Konfiguration
EXPO_PUBLIC_API_URL=https://sunnybill-test.test
EXPO_PUBLIC_API_TOKEN_NAME=VoltMaster

# Automatischer Token (optional)
APP_TOKEN=sb_YourTokenHere

# Entwicklungseinstellungen
EXPO_PUBLIC_DEBUG=true
EXPO_PUBLIC_LOG_LEVEL=debug
```

### Token erhalten

1. Web-Interface öffnen: `https://sunnybill-test.test`
2. Als Administrator anmelden
3. Zu "App-Token" navigieren
4. "Neues Token erstellen" klicken
5. Token kopieren und in .env einfügen

## 📋 Verfügbare Funktionen

- ✅ Automatische Authentifizierung
- ✅ Aufgaben anzeigen und verwalten
- ✅ Benutzer-Profil anzeigen
- ✅ Responsive Design
- ✅ Offline-Unterstützung
- ✅ Push-Benachrichtigungen (geplant)

## 🛠️ Entwicklung

### Ordnerstruktur

```
src/
├── components/     # Wiederverwendbare Komponenten
├── config/         # Konfigurationsdateien
├── context/        # React Context (Auth, etc.)
├── hooks/          # Custom Hooks
├── navigation/     # Navigation Setup
├── screens/        # Screen-Komponenten
├── services/       # API Services
├── types/          # TypeScript Definitionen
└── utils/          # Utility-Funktionen
```

### Wichtige Dateien

- `src/context/AuthContext.tsx` - Authentifizierung
- `src/services/ApiService.ts` - API-Kommunikation
- `src/services/TaskService.ts` - Aufgaben-Management
- `src/config/api.ts` - API-Konfiguration

## 🐛 Troubleshooting

### App startet nicht
```bash
# Cache leeren
expo start --clear

# oder
npx expo start --clear
```

### Token-Probleme
- Token in .env prüfen
- Backend-Erreichbarkeit testen
- Token-Gültigkeit in Admin-Interface prüfen

### API-Verbindung
- URL in .env prüfen
- Backend läuft auf korrekter URL
- SSL-Zertifikate (bei HTTPS)

## 📞 Support

Bei Problemen wenden Sie sich an das Entwicklungsteam.
