<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\GmailEmail;
use App\Filament\Resources\GmailEmailResource;

Artisan::command('test:gmail-badge', function () {
    $this->info("=== Gmail Badge Display Test ===");
    $this->newLine();

    try {
        // E-Mail-Statistiken abrufen
        $unreadCount = GmailEmail::unread()->where('is_trash', false)->count();
        $readCount = GmailEmail::read()->where('is_trash', false)->count();
        $totalCount = GmailEmail::where('is_trash', false)->count();
        
        $this->info("📊 E-Mail-Statistiken:");
        $this->line("- Ungelesene E-Mails: {$unreadCount}");
        $this->line("- Gelesene E-Mails: {$readCount}");
        $this->line("- Gesamt (ohne Papierkorb): {$totalCount}");
        $this->newLine();
        
        // Badge-Funktionen testen
        $this->info("🏷️ Badge-Funktionen:");
        
        $badge = GmailEmailResource::getNavigationBadge();
        $this->line("- Badge-Text: " . ($badge ?? 'null'));
        
        $badgeColor = GmailEmailResource::getNavigationBadgeColor();
        $this->line("- Badge-Farbe: " . ($badgeColor ?? 'null'));
        
        $badgeTooltip = GmailEmailResource::getNavigationBadgeTooltip();
        $this->line("- Badge-Tooltip: " . ($badgeTooltip ?? 'null'));
        $this->newLine();
        
        // Detaillierte Badge-Logik testen
        $this->info("🎨 Badge-Farb-Logik:");
        if ($unreadCount > 10) {
            $this->line("- Farbe: danger (rot) - Mehr als 10 ungelesene E-Mails");
        } elseif ($unreadCount > 0) {
            $this->line("- Farbe: warning (gelb) - {$unreadCount} ungelesene E-Mails");
        } else {
            $this->line("- Farbe: primary (blau) - Keine ungelesenen E-Mails");
        }
        
        // Badge-Format erklären
        $this->newLine();
        $this->info("📋 Badge-Format:");
        $this->line("- Format: 'ungelesen/gelesen' (z.B. '5/23')");
        $this->line("- Aktuell: '{$unreadCount}/{$readCount}'");
        $this->line("- Tooltip zeigt: 'Ungelesen: {$unreadCount} | Gelesen: {$readCount}'");
        $this->newLine();
        
        // Beispiel-E-Mails anzeigen
        $this->info("📧 Beispiel-E-Mails:");
        
        $recentEmails = GmailEmail::where('is_trash', false)
            ->orderBy('gmail_date', 'desc')
            ->limit(5)
            ->get();
        
        if ($recentEmails->count() > 0) {
            foreach ($recentEmails as $email) {
                $status = $email->is_read ? '✅ Gelesen' : '📬 Ungelesen';
                $date = $email->gmail_date ? $email->gmail_date->format('d.m.Y H:i') : 'Unbekannt';
                $subject = \Str::limit($email->subject ?? 'Kein Betreff', 50);
                
                $this->line("  {$status} | {$date} | {$subject}");
            }
        } else {
            $this->line("  Keine E-Mails gefunden.");
        }
        
        $this->newLine();
        $this->info("✅ Badge-Test erfolgreich abgeschlossen!");
        $this->newLine();
        $this->info("🔍 Erwartetes Verhalten:");
        $this->line("- Badge zeigt 'ungelesen/gelesen' Format");
        $this->line("- Farbe ändert sich basierend auf ungelesenen E-Mails:");
        $this->line("  • Rot (danger): > 10 ungelesen");
        $this->line("  • Gelb (warning): 1-10 ungelesen");
        $this->line("  • Blau (primary): 0 ungelesen");
        $this->line("- Tooltip zeigt detaillierte Aufschlüsselung");
        
    } catch (Exception $e) {
        $this->error("❌ Fehler beim Badge-Test: " . $e->getMessage());
        $this->line("Stack Trace:");
        $this->line($e->getTraceAsString());
    }
})->purpose('Testet die Gmail Badge-Funktionalität');
