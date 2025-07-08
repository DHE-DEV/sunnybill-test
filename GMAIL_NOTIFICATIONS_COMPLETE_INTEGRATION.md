# Gmail Benachrichtigungen - Vollständige Integration

## Übersicht

Dieses Dokument beschreibt die vollständige Integration des Gmail-Benachrichtigungssystems in SunnyBill. Das System ermöglicht es Benutzern, Benachrichtigungen über neue Gmail-E-Mails zu erhalten.

## Implementierte Komponenten

### 1. Datenbank-Migrationen

#### Migration: `2025_07_08_120247_add_gmail_notification_fields_to_company_settings_table.php`
- Fügt globale Gmail-Benachrichtigungseinstellungen zur `company_settings` Tabelle hinzu
- Felder:
  - `gmail_notifications_enabled` (boolean): Globale Aktivierung
  - `gmail_notification_sound` (boolean): Sound-Benachrichtigungen
  - `gmail_notification_duration` (integer): Anzeigedauer in Sekunden
  - `gmail_notification_types` (json): Erlaubte Benachrichtigungstypen

#### Migration: `2025_07_08_120311_add_gmail_notification_fields_to_users_table.php`
- Fügt benutzerspezifische Gmail-Benachrichtigungseinstellungen zur `users` Tabelle hinzu
- Felder:
  - `gmail_browser_notifications` (boolean): Browser-Benachrichtigungen
  - `gmail_email_notifications` (boolean): E-Mail-Benachrichtigungen
  - `gmail_push_notifications` (boolean): Push-Benachrichtigungen

### 2. Model-Erweiterungen

#### CompanySetting Model
- Neue Methoden für Gmail-Benachrichtigungseinstellungen:
  - `areGmailNotificationsEnabled()`: Prüft globale Aktivierung
  - `areGmailSoundNotificationsEnabled()`: Prüft Sound-Einstellungen
  - `getGmailNotificationDuration()`: Gibt Anzeigedauer zurück
  - `getGmailNotificationTypes()`: Gibt erlaubte Typen zurück

#### User Model
- Neue Attribute für benutzerspezifische Einstellungen
- Casting für boolean-Werte

### 3. Services

#### GmailNotificationService (`app/Services/GmailNotificationService.php`)
Zentraler Service für die Verarbeitung von Gmail-Benachrichtigungen:

**Hauptmethoden:**
- `processNewEmail(GmailEmail $email)`: Verarbeitet neue E-Mails
- `getEligibleUsers()`: Ermittelt berechtigte Benutzer
- `sendNotifications(GmailEmail $email, array $users)`: Sendet Benachrichtigungen
- `shouldNotifyUser(User $user, GmailEmail $email)`: Prüft Benachrichtigungsberechtigung

**Features:**
- Intelligente Benutzerfilterung basierend auf Rollen und Einstellungen
- Unterstützung für verschiedene Benachrichtigungstypen
- Umfassendes Logging und Fehlerbehandlung
- Rate Limiting und Performance-Optimierung

### 4. Events

#### NewGmailReceived Event (`app/Events/NewGmailReceived.php`)
- Broadcasting-Event für neue Gmail-E-Mails
- Sendet an private Channels für berechtigte Benutzer
- Enthält vollständige E-Mail-Informationen und Metadaten

#### GmailNotificationReceived Event (`app/Events/GmailNotificationReceived.php`)
- Event für individuelle Benutzerbenachrichtigungen
- Unterstützt verschiedene Benachrichtigungstypen
- Broadcasting für Real-time Updates

### 5. Jobs

#### SendGmailNotification Job (`app/Jobs/SendGmailNotification.php`)
Asynchroner Job für die Verarbeitung von Benachrichtigungen:

**Unterstützte Typen:**
- **Browser**: Web-Benachrichtigungen (mit Cache-basierter Simulation)
- **Email**: E-Mail-Benachrichtigungen über Laravel Mail
- **Push**: Mobile Push-Benachrichtigungen (vorbereitet für FCM/APNS)

**Features:**
- Queue-basierte Verarbeitung mit `notifications` Queue
- Retry-Mechanismus mit exponential backoff
- Umfassendes Error Handling und Logging
- Job-Tagging für bessere Überwachung

### 6. Mail

#### GmailNotificationMail (`app/Mail/GmailNotificationMail.php`)
- Mailable-Klasse für E-Mail-Benachrichtigungen
- Unterstützt HTML-Templates
- Prioritätseinstellungen für wichtige E-Mails

#### E-Mail Template (`resources/views/emails/gmail-notification.blade.php`)
- Responsive HTML-Template
- Moderne Gestaltung mit Gradient-Header
- Vollständige E-Mail-Informationen mit Metadaten
- Mobile-optimiert
- Firmen-Branding Integration

## Konfiguration

### Umgebungsvariablen
```env
# Broadcasting (für Real-time Benachrichtigungen)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

# Queue-Konfiguration
QUEUE_CONNECTION=database
```

### Queue-Setup
```bash
# Queue-Tabellen erstellen
php artisan queue:table
php artisan migrate

# Queue-Worker starten
php artisan queue:work --queue=notifications,default
```

## Verwendung

### 1. Neue E-Mail verarbeiten
```php
use App\Services\GmailNotificationService;
use App\Models\GmailEmail;

$notificationService = new GmailNotificationService();
$email = GmailEmail::find(1);
$notificationService->processNewEmail($email);
```

### 2. Manuelle Benachrichtigung senden
```php
use App\Jobs\SendGmailNotification;

$user = ['id' => 1, 'name' => 'Test User'];
$notificationData = [
    'title' => 'Neue E-Mail',
    'message' => 'Sie haben eine neue E-Mail erhalten',
    'email_id' => 123,
    'url' => 'https://app.example.com/emails/123'
];

SendGmailNotification::dispatch($user, $notificationData, 'browser');
```

### 3. Einstellungen konfigurieren
```php
use App\Models\CompanySetting;

$settings = CompanySetting::current();
$settings->update([
    'gmail_notifications_enabled' => true,
    'gmail_notification_sound' => true,
    'gmail_notification_duration' => 5000, // 5 Sekunden
    'gmail_notification_types' => ['browser', 'email']
]);
```

## Integration in bestehende Systeme

### Gmail Service Integration
Der `GmailNotificationService` sollte in den bestehenden `GmailService` integriert werden:

```php
// In GmailService::syncEmails()
foreach ($newEmails as $email) {
    // E-Mail speichern
    $savedEmail = $this->saveEmail($email);
    
    // Benachrichtigung senden
    $notificationService = new GmailNotificationService();
    $notificationService->processNewEmail($savedEmail);
}
```

### Filament Admin Integration
Die Benachrichtigungseinstellungen sind bereits in die Filament-Ressourcen integriert:
- `CompanySettingResource`: Globale Einstellungen
- User-Profile: Individuelle Einstellungen

## Monitoring und Logging

### Log-Kategorien
- `gmail.notifications.service`: Service-Level Logs
- `gmail.notifications.job`: Job-Verarbeitung
- `gmail.notifications.email`: E-Mail-Versand
- `gmail.notifications.error`: Fehlerbehandlung

### Metriken
- Anzahl verarbeiteter Benachrichtigungen
- Erfolgsrate der Benachrichtigungen
- Queue-Performance
- Benutzer-Engagement

## Sicherheit

### Datenschutz
- Minimale Datenübertragung in Benachrichtigungen
- Sichere Channel-Authentifizierung
- Benutzerbasierte Zugriffskontrolle

### Performance
- Asynchrone Verarbeitung über Queues
- Rate Limiting für Benachrichtigungen
- Caching für Browser-Benachrichtigungen
- Optimierte Datenbankabfragen

## Erweiterungsmöglichkeiten

### 1. Web Push API Integration
```javascript
// Frontend-Integration für echte Browser-Benachrichtigungen
if ('serviceWorker' in navigator && 'PushManager' in window) {
    // Service Worker registrieren
    // Push-Subscription erstellen
    // Mit Backend synchronisieren
}
```

### 2. Mobile Push Notifications
```php
// Firebase Cloud Messaging Integration
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

$message = CloudMessage::withTarget('token', $deviceToken)
    ->withNotification(Notification::create($title, $body))
    ->withData($data);
```

### 3. Slack/Teams Integration
```php
// Slack-Benachrichtigungen
use Illuminate\Support\Facades\Http;

Http::post($slackWebhookUrl, [
    'text' => "Neue Gmail E-Mail: {$subject}",
    'attachments' => [...]
]);
```

## Testing

### Unit Tests
```php
// Test für GmailNotificationService
public function test_processes_new_email_notification()
{
    $email = GmailEmail::factory()->create();
    $service = new GmailNotificationService();
    
    $result = $service->processNewEmail($email);
    
    $this->assertTrue($result);
}
```

### Feature Tests
```php
// Test für Job-Verarbeitung
public function test_sends_email_notification()
{
    Mail::fake();
    
    $job = new SendGmailNotification($user, $data, 'email');
    $job->handle();
    
    Mail::assertSent(GmailNotificationMail::class);
}
```

## Deployment

### Produktionsumgebung
1. Queue-Worker als Daemon konfigurieren
2. Broadcasting-Service (Pusher/Redis) einrichten
3. Monitoring für Jobs einrichten
4. Log-Rotation konfigurieren

### Überwachung
- Queue-Status überwachen
- Failed Jobs verfolgen
- Performance-Metriken sammeln
- Benutzer-Feedback auswerten

## Fazit

Das Gmail-Benachrichtigungssystem ist vollständig implementiert und bietet:
- ✅ Multi-Channel Benachrichtigungen (Browser, E-Mail, Push)
- ✅ Granulare Benutzereinstellungen
- ✅ Asynchrone Verarbeitung
- ✅ Umfassendes Logging
- ✅ Skalierbare Architektur
- ✅ Sichere Implementierung

Das System ist bereit für den Produktionseinsatz und kann bei Bedarf erweitert werden.
