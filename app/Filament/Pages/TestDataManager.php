<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\SolarPlant;
use App\Models\PlantParticipation;
use App\Models\Invoice;
use App\Models\Article;
use App\Models\Supplier;
use App\Models\CustomerNote;
use App\Models\SupplierNote;
use App\Models\SolarPlantNote;
use App\Models\SolarPlantMilestone;
use App\Models\CustomerMonthlyCredit;
use App\Models\PlantMonthlyResult;
use App\Models\SolarPlantSupplier;
use App\Models\InvoiceItem;
use App\Models\PhoneNumber;
use App\Models\Address;
use App\Models\CustomerEmployee;
use App\Models\SupplierEmployee;
use App\Models\ArticleVersion;
use App\Models\InvoiceVersion;
use App\Models\TaxRateVersion;

class TestDataManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static string $view = 'filament.pages.test-data-manager';
    protected static ?string $title = 'Testdaten-Manager';
    protected static ?string $navigationLabel = 'Testdaten-Manager';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 5;

    public function getHeaderActions(): array
    {
        return [
            Action::make('createTestData')
                ->label('Testdaten erstellen')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Testdaten erstellen')
                ->modalDescription('Möchten Sie neue Testdaten erstellen? Dies fügt 20 Kunden, 10 Solaranlagen, 3 Lieferanten mit Notizen und Zuordnungen hinzu.')
                ->modalSubmitActionLabel('Ja, erstellen')
                ->action(function () {
                    try {
                        Artisan::call('testdata:manage', ['action' => 'create']);
                        
                        Notification::make()
                            ->title('Testdaten erfolgreich erstellt!')
                            ->body('20 Kunden, 10 Solaranlagen, 3 Lieferanten mit Notizen und Zuordnungen wurden erstellt.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Erstellen der Testdaten')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('deleteAllData')
                ->label('Daten löschen')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('ACHTUNG: Alle Daten löschen!')
                ->modalDescription('Dies löscht ALLE vorhandenen Kunden, Lieferanten, Rechnungen, Gutschriften, Solaranlagen, Notizen, Projekttermine, Monatlichen Ereignisse, Kundenbeteiligungen und verknüpfte Daten unwiderruflich. Sie erhalten einen leeren Stand zum Testen. Diese Aktion kann nicht rückgängig gemacht werden!')
                ->modalSubmitActionLabel('Ja, alle Daten löschen')
                ->action(function () {
                    try {
                        // Foreign Key Checks temporär deaktivieren
                        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                        
                        // Lösche alle Daten in der richtigen Reihenfolge (abhängige Tabellen zuerst)
                        
                        // 1. Versionen (abhängig von Hauptentitäten)
                        InvoiceVersion::query()->delete();
                        ArticleVersion::query()->delete();
                        TaxRateVersion::query()->delete();
                        
                        // 2. Rechnungsposten (abhängig von Rechnungen und Artikeln)
                        InvoiceItem::query()->delete();
                        
                        // 3. Rechnungen (abhängig von Kunden)
                        Invoice::query()->delete();
                        
                        // 4. Kundengutschriften (abhängig von Kunden und Solaranlagen)
                        CustomerMonthlyCredit::query()->delete();
                        
                        // 5. Monatliche Ergebnisse (abhängig von Solaranlagen)
                        PlantMonthlyResult::query()->delete();
                        
                        // 6. Kundenbeteiligungen (abhängig von Kunden und Solaranlagen)
                        PlantParticipation::query()->delete();
                        
                        // 7. Solaranlagen-Lieferanten-Zuordnungen
                        SolarPlantSupplier::query()->delete();
                        
                        // 8. Notizen
                        CustomerNote::query()->delete();
                        SupplierNote::query()->delete();
                        SolarPlantNote::query()->delete();
                        
                        // 9. Projekttermine/Meilensteine
                        SolarPlantMilestone::query()->delete();
                        
                        // 10. Telefonnummern (polymorphe Beziehung)
                        PhoneNumber::query()->delete();
                        
                        // 11. Adressen (polymorphe Beziehung)
                        Address::query()->delete();
                        
                        // 12. Mitarbeiter
                        CustomerEmployee::query()->delete();
                        SupplierEmployee::query()->delete();
                        
                        // 13. Hauptentitäten (mit SoftDeletes - alle Datensätze einzeln force löschen)
                        Customer::withTrashed()->get()->each(function ($customer) {
                            $customer->forceDelete();
                        });
                        Supplier::withTrashed()->get()->each(function ($supplier) {
                            $supplier->forceDelete();
                        });
                        SolarPlant::withTrashed()->get()->each(function ($solarPlant) {
                            $solarPlant->forceDelete();
                        });
                        Article::withTrashed()->get()->each(function ($article) {
                            $article->forceDelete();
                        });
                        
                        Notification::make()
                            ->title('Alle Daten erfolgreich gelöscht!')
                            ->body('Alle Kunden, Lieferanten, Rechnungen, Solaranlagen, Notizen und verwandten Daten wurden gelöscht. Sie haben jetzt einen leeren Stand.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Löschen der Daten')
                            ->body('Fehler: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        // Foreign Key Checks immer wieder aktivieren
                        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                    }
                }),

            Action::make('resetAllData')
                ->label('Alle Daten zurücksetzen')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('ACHTUNG: Alle Daten löschen!')
                ->modalDescription('Dies löscht ALLE vorhandenen Daten unwiderruflich und erstellt neue Testdaten. Diese Aktion kann nicht rückgängig gemacht werden!')
                ->modalSubmitActionLabel('Ja, alles löschen und neu erstellen')
                ->action(function () {
                    try {
                        Artisan::call('testdata:manage', ['action' => 'reset']);
                        
                        Notification::make()
                            ->title('Daten erfolgreich zurückgesetzt!')
                            ->body('Alle Daten wurden gelöscht und neue Testdaten erstellt.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Zurücksetzen der Daten')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function getStats(): array
    {
        return [
            'customers' => Customer::count(),
            'suppliers' => Supplier::count(),
            'solar_plants' => SolarPlant::count(),
            'assignments' => PlantParticipation::count(),
            'invoices' => Invoice::count(),
            'articles' => Article::count(),
        ];
    }

    public function getTestDataInfo(): array
    {
        return [
            'description' => 'Das Testdaten-System erstellt konsistente Beispieldaten für die Entwicklung und Tests.',
            'features' => [
                '20 Kunden mit realistischen Namen und Adressen',
                '10 Solaranlagen mit verschiedenen Kapazitäten',
                '6 Solaranlagen werden auf Kunden aufgeteilt',
                '2 Kunden erhalten Anteile an allen 6 Anlagen',
                'Mindestanteil pro Kunde: 10%',
                'Automatische Notizen für jeden Kunden (1-3 pro Kunde)',
                '30 Solaranlagen-Notizen (3 pro Anlage) mit verschiedenen Typen',
                '3 Lieferanten mit 5 Mitarbeitern und Kontaktdaten',
                '14 Lieferanten-Zuordnungen zu Solaranlagen mit verschiedenen Rollen',
                '4 Lieferanten-Notizen mit Beispielinhalten',
                '10 Beispiel-Rechnungen mit verschiedenen Artikeln',
                '9 Standard-Artikel (inkl. Einspeisevergütung -0,081234 €/kWh)',
            ],
            'reset_info' => 'Der Reset löscht alle vorhandenen Daten und erstellt identische Testdaten neu.',
        ];
    }
}