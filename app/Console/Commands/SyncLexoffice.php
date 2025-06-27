<?php

namespace App\Console\Commands;

use App\Services\LexofficeService;
use Illuminate\Console\Command;

class SyncLexoffice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:lexoffice
                            {--customers : Nur Kunden synchronisieren}
                            {--articles : Nur Artikel synchronisieren}
                            {--export-customers : Kunden zu Lexoffice exportieren}
                            {--export-articles : Artikel zu Lexoffice exportieren}
                            {--test : Verbindung testen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronisiert Daten mit Lexoffice API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = new LexofficeService();

        // Verbindung testen
        if ($this->option('test')) {
            $this->info('Teste Lexoffice-Verbindung...');
            $result = $service->testConnection();
            
            if ($result['success']) {
                $this->info("✅ Verbindung erfolgreich!");
                $this->line("Unternehmen: {$result['company']}");
                $this->line("E-Mail: {$result['email']}");
            } else {
                $this->error("❌ Verbindung fehlgeschlagen: {$result['error']}");
                return 1;
            }
            return 0;
        }

        $this->info('🚀 Starte Lexoffice-Synchronisation...');

        // Nur Kunden
        if ($this->option('customers')) {
            $this->syncCustomers($service);
            return 0;
        }

        // Nur Artikel
        if ($this->option('articles')) {
            $this->syncArticles($service);
            return 0;
        }

        // Kunden exportieren
        if ($this->option('export-customers')) {
            $this->exportCustomers($service);
            return 0;
        }

        // Artikel exportieren
        if ($this->option('export-articles')) {
            $this->exportArticles($service);
            return 0;
        }

        // Alles synchronisieren
        $this->syncCustomers($service);
        $this->syncArticles($service);

        $this->info('✅ Synchronisation abgeschlossen!');
        return 0;
    }

    private function syncCustomers(LexofficeService $service): void
    {
        $this->info('📥 Importiere Kunden von Lexoffice...');
        
        $result = $service->importCustomers();
        
        if ($result['success']) {
            $this->info("✅ {$result['imported']} Kunden erfolgreich importiert");
            
            if (!empty($result['errors'])) {
                $this->warn('⚠️  Einige Fehler aufgetreten:');
                foreach ($result['errors'] as $error) {
                    $this->line("   - {$error}");
                }
            }
        } else {
            $this->error("❌ Kunden-Import fehlgeschlagen: {$result['error']}");
        }
    }

    private function syncArticles(LexofficeService $service): void
    {
        $this->info('📥 Importiere Artikel von Lexoffice...');
        
        $result = $service->importArticles();
        
        if ($result['success']) {
            $this->info("✅ {$result['imported']} Artikel erfolgreich importiert");
            
            if (!empty($result['errors'])) {
                $this->warn('⚠️  Einige Fehler aufgetreten:');
                foreach ($result['errors'] as $error) {
                    $this->line("   - {$error}");
                }
            }
        } else {
            $this->error("❌ Artikel-Import fehlgeschlagen: {$result['error']}");
        }
    }

    private function exportCustomers(LexofficeService $service): void
    {
        $this->info('📤 Exportiere Kunden zu Lexoffice...');
        
        $customers = \App\Models\Customer::all();
        $exported = 0;
        $updated = 0;
        $errors = [];

        foreach ($customers as $customer) {
            $result = $service->exportCustomer($customer);
            
            if ($result['success']) {
                if ($result['action'] === 'create') {
                    $exported++;
                    $this->line("✅ Kunde '{$customer->name}' erstellt");
                } else {
                    $updated++;
                    $this->line("🔄 Kunde '{$customer->name}' aktualisiert");
                }
            } else {
                $errors[] = "Kunde '{$customer->name}': {$result['error']}";
                $this->line("❌ Kunde '{$customer->name}': {$result['error']}");
            }
        }

        $this->info("✅ Export abgeschlossen: {$exported} erstellt, {$updated} aktualisiert");
        
        if (!empty($errors)) {
            $this->warn('⚠️  Einige Fehler aufgetreten:');
            foreach ($errors as $error) {
                $this->line("   - {$error}");
            }
        }
    }

    private function exportArticles(LexofficeService $service): void
    {
        $this->info('📤 Exportiere Artikel zu Lexoffice...');
        
        $articles = \App\Models\Article::all();
        $exported = 0;
        $updated = 0;
        $errors = [];

        foreach ($articles as $article) {
            $result = $service->exportArticle($article);
            
            if ($result['success']) {
                if ($result['action'] === 'create') {
                    $exported++;
                    $this->line("✅ Artikel '{$article->name}' erstellt");
                } else {
                    $updated++;
                    $this->line("🔄 Artikel '{$article->name}' aktualisiert");
                }
            } else {
                $errors[] = "Artikel '{$article->name}': {$result['error']}";
                $this->line("❌ Artikel '{$article->name}': {$result['error']}");
            }
        }

        $this->info("✅ Export abgeschlossen: {$exported} erstellt, {$updated} aktualisiert");
        
        if (!empty($errors)) {
            $this->warn('⚠️  Einige Fehler aufgetreten:');
            foreach ($errors as $error) {
                $this->line("   - {$error}");
            }
        }
    }
}
