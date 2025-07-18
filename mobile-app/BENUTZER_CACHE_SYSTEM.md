# Benutzer-Cache-System für die Mobile App

## Überblick

Das Benutzer-Cache-System wurde implementiert, um die Performance der App zu verbessern, indem Benutzerdaten lokal gespeichert und nur bei Bedarf vom Server abgerufen werden.

## Funktionsweise

### 1. Lokale Speicherung
- **Web**: Verwendet `localStorage` für Benutzerdaten und Timestamp
- **Mobile**: Verwendet `SecureStore` für sichere Speicherung
- **Daten**: Vollständiges `AppProfile` Objekt mit Benutzerinformationen

### 2. Cache-Strategie

#### Beim App-Start (`checkAuthStatus`)
1. Token wird aus .env oder gespeichertem Token geladen
2. **Cache-Prüfung**: Lokale Benutzerdaten werden auf Aktualität geprüft
3. **Cache-Hit**: Wenn Daten aktuell sind (< 30 Minuten alt):
   - Lokale Daten werden sofort geladen
   - Token wird trotzdem validiert
   - Keine Server-Anfrage für Profildaten
4. **Cache-Miss**: Wenn Daten veraltet oder nicht vorhanden:
   - Fresh-Daten werden vom Server geladen
   - Neue Daten werden lokal gespeichert

#### Bei Login (`login`)
- Profildaten werden vom Server geladen
- Daten werden automatisch lokal gespeichert
- Timestamp wird für Cache-Validierung gesetzt

#### Bei Logout (`logout`)
- Lokale Benutzerdaten werden vollständig gelöscht
- Cache wird geleert

### 3. Cache-Verwaltung

#### Automatische Aktualisierung
- `refreshProfile()`: Lädt neue Daten vom Server und aktualisiert den Cache
- Cache wird automatisch bei erfolgreichen API-Aufrufen erneuert

#### Manuelle Cache-Invalidierung
- `invalidateUserCache()`: Löscht lokale Daten und erzwingt Neuladung
- Nützlich bei Entwicklung oder wenn Daten-Synchronisation erforderlich ist

### 4. Konfiguration

#### Cache-Gültigkeitsdauer
```typescript
const isDataStale = (timestamp: number, maxAgeMinutes: number = 30): boolean => {
  // Standard: 30 Minuten
  // Kann bei Bedarf angepasst werden
}
```

## Verwendung

### Basis-Hook
```typescript
import { useAuth } from '../context/AuthContext';

const { user, profile, invalidateUserCache } = useAuth();
```

### Cache manuell invalidieren
```typescript
const handleForceRefresh = async () => {
  await invalidateUserCache();
  // Daten werden neu geladen
};
```

### Prüfung auf geladene Daten
```typescript
const { isLoading, isAuthenticated, user } = useAuth();

if (isLoading) {
  return <LoadingSpinner />;
}

if (isAuthenticated && user) {
  return <UserProfile user={user} />;
}
```

## Performance-Vorteile

### 1. Schnellere App-Starts
- Lokale Daten werden sofort angezeigt
- Keine Wartezeit auf Server-Antworten
- Bessere Benutzererfahrung

### 2. Reduzierte Server-Last
- Weniger API-Aufrufe
- Bandbreiten-Einsparung
- Geringere Serverkosten

### 3. Offline-Fähigkeit
- Benutzerdaten sind auch offline verfügbar
- Robustere App-Funktion

## Sicherheitsaspekte

### 1. Sichere Speicherung
- **Mobile**: `SecureStore` mit Hardware-Verschlüsselung
- **Web**: `localStorage` (weniger sicher, aber Standard)

### 2. Token-Validierung
- Token wird bei Cache-Hits trotzdem validiert
- Ungültige Token führen zu automatischem Logout
- Schutz vor kompromittierten Tokens

### 3. Automatische Bereinigung
- Cache wird bei Logout gelöscht
- Veraltete Daten werden automatisch erneuert

## Debugging

### Console-Logs
```javascript
// Cache-Hit
console.log('Using cached user data');

// Cache-Miss
console.log('Loading fresh user data from server');

// Cache-Invalidierung
console.log('Invalidating user cache - forcing fresh data load');
```

### Lokale Speicher-Schlüssel
- `user_data`: Serialisierte Benutzerdaten
- `user_data_timestamp`: Timestamp der letzten Aktualisierung
- `app_token`: Authentifizierungs-Token

## Wartung

### Cache-Dauer anpassen
```typescript
// In isDataStale Funktion
const maxAge = maxAgeMinutes * 60 * 1000; // Standard: 30 Minuten
```

### Cache-Strategien erweitern
- Verschiedene Cache-Dauern für verschiedene Datentypen
- Intelligente Aktualisierung basierend auf Benutzeraktivität
- Hintergrund-Synchronisation

## Fehlerbehebung

### Cache-Probleme
1. **Daten nicht aktuell**: `invalidateUserCache()` aufrufen
2. **Speicher-Fehler**: Prüfe Speicherplatz und Berechtigungen
3. **Token-Konflikte**: Logout und erneutes Login

### Entwickler-Tools
- Browser DevTools → Application → Local Storage
- React Native Debugger für SecureStore
- Console-Logs für Cache-Status

## Best Practices

1. **Cache-Invalidierung**: Nach wichtigen Profil-Änderungen
2. **Error-Handling**: Graceful Fallback bei Cache-Fehlern
3. **Testing**: Cache-Verhalten in Tests berücksichtigen
4. **Monitoring**: Cache-Hit-Rate überwachen
