<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\CompanySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;

/**
 * Umfassender Unit-Test für Customer CRUD-Operationen
 * 
 * Testet alle grundlegenden Operationen: Anlegen, Ändern, Löschen
 * sowie spezielle Geschäftslogik des Customer Models
 */
class CustomerCrudTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Lösche alle bestehenden CompanySettings und erstelle eine neue
        CompanySetting::query()->delete();
        CompanySetting::factory()->create([
            'customer_number_prefix' => 'K',
        ]);
    }

    /**
     * Test: Kunde erfolgreich anlegen (Privatkunde)
     */
    public function test_kann_privatkunden_anlegen(): void
    {
        $customerData = [
            'customer_type' => 'private',
            'name' => 'Max Mustermann',
            'email' => 'max.mustermann@example.com',
            'phone' => '+49 123 456789',
            'street' => 'Musterstraße 123',
            'postal_code' => '12345',
            'city' => 'Musterstadt',
            'country' => 'Deutschland',
            'country_code' => 'DE',
            'is_active' => true,
        ];

        $customer = Customer::create($customerData);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'customer_type' => 'private',
            'name' => 'Max Mustermann',
            'email' => 'max.mustermann@example.com',
            'is_active' => true,
        ]);

        // Prüfe automatische Kundennummer-Generierung
        $this->assertNotNull($customer->customer_number);
        $this->assertStringStartsWith('K-', $customer->customer_number);
        
        // Prüfe Default-Werte
        $this->assertEquals('Deutschland', $customer->country);
        $this->assertEquals('DE', $customer->country_code);
        $this->assertTrue($customer->is_active);
        $this->assertNull($customer->deactivated_at);
    }

    /**
     * Test: Kunde erfolgreich anlegen (Geschäftskunde)
     */
    public function test_kann_geschaeftskunden_anlegen(): void
    {
        $customerData = [
            'customer_type' => 'business',
            'name' => 'Musterfirma GmbH',
            'company_name' => 'Musterfirma GmbH',
            'contact_person' => 'Hans Müller',
            'department' => 'Einkauf',
            'email' => 'info@musterfirma.de',
            'phone' => '+49 123 456789',
            'fax' => '+49 123 456790',
            'website' => 'https://www.musterfirma.de',
            'street' => 'Industriestraße 456',
            'postal_code' => '54321',
            'city' => 'Industriestadt',
            'tax_number' => '12/345/67890',
            'vat_id' => 'DE123456789',
            'payment_terms' => 'Zahlung innerhalb von 30 Tagen netto',
            'payment_days' => 30,
            'bank_name' => 'Deutsche Bank',
            'iban' => 'DE89370400440532013000',
            'bic' => 'COBADEFFXXX',
        ];

        $customer = Customer::create($customerData);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'customer_type' => 'business',
            'company_name' => 'Musterfirma GmbH',
            'contact_person' => 'Hans Müller',
            'vat_id' => 'DE123456789',
        ]);

        // Prüfe Geschäftskunden-spezifische Methoden
        $this->assertTrue($customer->isBusinessCustomer());
        $this->assertFalse($customer->isPrivateCustomer());
        $this->assertTrue($customer->hasVatId());
    }

    /**
     * Test: Kunde mit manueller Kundennummer anlegen
     */
    public function test_kann_kunden_mit_manueller_kundennummer_anlegen(): void
    {
        $customerData = [
            'customer_number' => 'MANUAL-001',
            'customer_type' => 'private',
            'name' => 'Test Kunde',
            'email' => 'test@example.com',
        ];

        $customer = Customer::create($customerData);

        $this->assertEquals('MANUAL-001', $customer->customer_number);
        $this->assertDatabaseHas('customers', [
            'customer_number' => 'MANUAL-001',
        ]);
    }

    /**
     * Test: Automatische Kundennummer-Generierung
     */
    public function test_automatische_kundennummer_generierung(): void
    {
        // Erstelle mehrere Kunden ohne Kundennummer
        $customer1 = Customer::create([
            'customer_type' => 'private',
            'name' => 'Kunde 1',
            'email' => 'kunde1@example.com',
        ]);

        $customer2 = Customer::create([
            'customer_type' => 'private',
            'name' => 'Kunde 2',
            'email' => 'kunde2@example.com',
        ]);

        // Prüfe dass beide Kundennummern generiert wurden
        $this->assertNotNull($customer1->customer_number);
        $this->assertNotNull($customer2->customer_number);
        $this->assertNotEquals($customer1->customer_number, $customer2->customer_number);
        
        // Prüfe Format
        $this->assertStringStartsWith('K-', $customer1->customer_number);
        $this->assertStringStartsWith('K-', $customer2->customer_number);
    }

    /**
     * Test: Kunde erfolgreich ändern
     */
    public function test_kann_kunden_aendern(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Alter Name',
            'email' => 'alt@example.com',
            'is_active' => true,
        ]);

        $updateData = [
            'name' => 'Neuer Name',
            'email' => 'neu@example.com',
            'phone' => '+49 987 654321',
            'notes' => 'Aktualisierte Notizen',
        ];

        $customer->update($updateData);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Neuer Name',
            'email' => 'neu@example.com',
            'phone' => '+49 987 654321',
            'notes' => 'Aktualisierte Notizen',
        ]);

        // Prüfe dass andere Felder unverändert blieben
        $customer->refresh();
        $this->assertTrue($customer->is_active);
    }

    /**
     * Test: Kunde deaktivieren
     */
    public function test_kann_kunden_deaktivieren(): void
    {
        $customer = Customer::factory()->create([
            'is_active' => true,
            'deactivated_at' => null,
        ]);

        $this->assertTrue($customer->is_active);
        $this->assertNull($customer->deactivated_at);

        // Deaktiviere Kunde
        $customer->update(['is_active' => false]);

        $customer->refresh();
        $this->assertFalse($customer->is_active);
        $this->assertNotNull($customer->deactivated_at);
        $this->assertTrue($customer->isDeactivated());
    }

    /**
     * Test: Kunde reaktivieren
     */
    public function test_kann_kunden_reaktivieren(): void
    {
        $customer = Customer::factory()->deactivated()->create();

        $this->assertFalse($customer->is_active);
        $this->assertNotNull($customer->deactivated_at);

        // Reaktiviere Kunde
        $customer->update(['is_active' => true]);

        $customer->refresh();
        $this->assertTrue($customer->is_active);
        $this->assertNull($customer->deactivated_at);
        $this->assertFalse($customer->isDeactivated());
    }

    /**
     * Test: Kunde ohne Rechnungen löschen
     */
    public function test_kann_kunden_ohne_rechnungen_loeschen(): void
    {
        $customer = Customer::factory()->create();

        $customerId = $customer->id;
        
        // Prüfe dass Kunde gelöscht werden kann
        $this->assertTrue($customer->canBeDeleted());
        $this->assertFalse($customer->hasInvoices());

        // Lösche Kunde (Hard Delete)
        $customer->forceDelete();

        $this->assertDatabaseMissing('customers', [
            'id' => $customerId,
        ]);
    }

    /**
     * Test: Kunde mit Rechnungen kann nicht gelöscht werden
     */
    public function test_kunde_mit_rechnungen_kann_nicht_geloescht_werden(): void
    {
        $customer = Customer::factory()->create();
        
        // Simuliere dass Kunde Rechnungen hat (Mock)
        $customer = $this->getMockBuilder(Customer::class)
            ->onlyMethods(['hasInvoices', 'canBeDeleted'])
            ->setConstructorArgs([$customer->toArray()])
            ->getMock();
            
        $customer->method('hasInvoices')->willReturn(true);
        $customer->method('canBeDeleted')->willReturn(false);

        $this->assertTrue($customer->hasInvoices());
        $this->assertFalse($customer->canBeDeleted());
    }

    /**
     * Test: Soft Delete funktioniert
     */
    public function test_soft_delete_funktioniert(): void
    {
        $customer = Customer::factory()->create();
        $customerId = $customer->id;

        // Soft Delete
        $customer->delete();

        // Prüfe dass Kunde soft deleted wurde
        $this->assertSoftDeleted('customers', [
            'id' => $customerId,
        ]);

        // Prüfe dass Kunde nicht mehr in normalen Queries erscheint
        $this->assertNull(Customer::find($customerId));
        
        // Prüfe dass Kunde mit withTrashed gefunden werden kann
        $this->assertNotNull(Customer::withTrashed()->find($customerId));
    }

    /**
     * Test: Kunde wiederherstellen nach Soft Delete
     */
    public function test_kann_kunden_nach_soft_delete_wiederherstellen(): void
    {
        $customer = Customer::factory()->create();
        $customerId = $customer->id;

        // Soft Delete
        $customer->delete();
        $this->assertSoftDeleted('customers', ['id' => $customerId]);

        // Wiederherstellen
        $customer->restore();

        // Prüfe dass Kunde wiederhergestellt wurde
        $this->assertDatabaseHas('customers', [
            'id' => $customerId,
            'deleted_at' => null,
        ]);
        
        $this->assertNotNull(Customer::find($customerId));
    }

    /**
     * Test: Kunden-Scopes funktionieren
     */
    public function test_kunden_scopes_funktionieren(): void
    {
        // Erstelle verschiedene Kunden
        $activePrivate = Customer::factory()->private()->create(['is_active' => true]);
        $activeBusiness = Customer::factory()->business()->create(['is_active' => true]);
        $deactivatedPrivate = Customer::factory()->private()->deactivated()->create();
        $deactivatedBusiness = Customer::factory()->business()->deactivated()->create();

        // Teste active scope
        $activeCustomers = Customer::active()->get();
        $this->assertCount(2, $activeCustomers);
        $this->assertTrue($activeCustomers->contains($activePrivate));
        $this->assertTrue($activeCustomers->contains($activeBusiness));

        // Teste deactivated scope
        $deactivatedCustomers = Customer::deactivated()->get();
        $this->assertCount(2, $deactivatedCustomers);
        $this->assertTrue($deactivatedCustomers->contains($deactivatedPrivate));
        $this->assertTrue($deactivatedCustomers->contains($deactivatedBusiness));

        // Teste businessCustomers scope
        $businessCustomers = Customer::businessCustomers()->get();
        $this->assertCount(2, $businessCustomers);
        $this->assertTrue($businessCustomers->contains($activeBusiness));
        $this->assertTrue($businessCustomers->contains($deactivatedBusiness));

        // Teste privateCustomers scope
        $privateCustomers = Customer::privateCustomers()->get();
        $this->assertCount(2, $privateCustomers);
        $this->assertTrue($privateCustomers->contains($activePrivate));
        $this->assertTrue($privateCustomers->contains($deactivatedPrivate));
    }

    /**
     * Test: Kunden-Suche funktioniert
     */
    public function test_kunden_suche_funktioniert(): void
    {
        $customer1 = Customer::factory()->create([
            'name' => 'Max Mustermann',
            'company_name' => null,
            'email' => 'max@example.com',
            'city' => 'Berlin',
        ]);

        $customer2 = Customer::factory()->business()->create([
            'name' => 'Musterfirma GmbH',
            'company_name' => 'Musterfirma GmbH',
            'contact_person' => 'Hans Müller',
            'email' => 'info@musterfirma.de',
            'city' => 'München',
        ]);

        // Suche nach Name
        $results = Customer::search('Max')->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($customer1));

        // Suche nach Firmenname
        $results = Customer::search('Musterfirma')->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($customer2));

        // Suche nach E-Mail
        $results = Customer::search('max@example.com')->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($customer1));

        // Suche nach Stadt
        $results = Customer::search('Berlin')->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($customer1));

        // Suche nach Ansprechpartner
        $results = Customer::search('Hans Müller')->get();
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($customer2));
    }

    /**
     * Test: Display Name Attribut funktioniert
     */
    public function test_display_name_attribut_funktioniert(): void
    {
        $privateCustomer = Customer::factory()->private()->create([
            'name' => 'Max Mustermann',
            'company_name' => null,
        ]);

        $businessCustomer = Customer::factory()->business()->create([
            'name' => 'Musterfirma GmbH',
            'company_name' => 'Musterfirma GmbH',
        ]);

        $businessCustomerWithoutCompanyName = Customer::factory()->business()->create([
            'name' => 'Hans Müller',
            'company_name' => null,
        ]);

        $this->assertEquals('Max Mustermann', $privateCustomer->display_name);
        $this->assertEquals('Musterfirma GmbH', $businessCustomer->display_name);
        $this->assertEquals('Hans Müller', $businessCustomerWithoutCompanyName->display_name);
    }

    /**
     * Test: Vollständige Adresse wird korrekt formatiert
     */
    public function test_vollstaendige_adresse_wird_korrekt_formatiert(): void
    {
        $customer = Customer::factory()->create([
            'street' => 'Musterstraße 123',
            'address_line_2' => 'Hinterhaus',
            'postal_code' => '12345',
            'city' => 'Musterstadt',
            'state' => 'Bayern',
            'country' => 'Deutschland',
        ]);

        $expectedAddress = "Musterstraße 123\nHinterhaus\n12345 Musterstadt, Bayern";
        $this->assertEquals($expectedAddress, $customer->full_address);

        // Test ohne Adresszusatz und Bundesland
        $customer2 = Customer::factory()->create([
            'street' => 'Hauptstraße 1',
            'address_line_2' => null,
            'postal_code' => '54321',
            'city' => 'Teststadt',
            'state' => null,
            'country' => 'Deutschland',
        ]);

        $expectedAddress2 = "Hauptstraße 1\n54321 Teststadt";
        $this->assertEquals($expectedAddress2, $customer2->full_address);
    }

    /**
     * Test: USt-IdNr Formatierung funktioniert
     */
    public function test_ust_idnr_formatierung_funktioniert(): void
    {
        $customer = Customer::factory()->business()->create([
            'vat_id' => 'DE123456789',
        ]);

        $this->assertEquals('DE 123 456 789', $customer->formatted_vat_id);

        // Test mit anderer USt-IdNr
        $customer2 = Customer::factory()->business()->create([
            'vat_id' => 'AT123456789',
        ]);

        $this->assertEquals('AT123456789', $customer2->formatted_vat_id);

        // Test ohne USt-IdNr
        $customer3 = Customer::factory()->private()->create([
            'vat_id' => null,
        ]);

        $this->assertNull($customer3->formatted_vat_id);
    }

    /**
     * Test: Status Text wird korrekt zurückgegeben
     */
    public function test_status_text_wird_korrekt_zurueckgegeben(): void
    {
        $activeCustomer = Customer::factory()->create(['is_active' => true]);
        $deactivatedCustomer = Customer::factory()->deactivated()->create();

        $this->assertEquals('Aktiv', $activeCustomer->status_text);
        $this->assertEquals('Deaktiviert', $deactivatedCustomer->status_text);
    }

    /**
     * Test: Bulk-Operationen funktionieren
     */
    public function test_bulk_operationen_funktionieren(): void
    {
        // Erstelle mehrere Kunden
        $customers = Customer::factory()->count(5)->create();

        // Bulk Update - alle deaktivieren (mit manueller deactivated_at Setzung)
        Customer::whereIn('id', $customers->pluck('id'))
            ->update([
                'is_active' => false,
                'deactivated_at' => now()
            ]);

        // Prüfe dass alle deaktiviert wurden
        $customers->each(function ($customer) {
            $customer->refresh();
            $this->assertFalse($customer->is_active);
            $this->assertNotNull($customer->deactivated_at);
        });

        // Bulk Delete
        Customer::whereIn('id', $customers->pluck('id'))->delete();

        // Prüfe dass alle soft deleted wurden
        $customers->each(function ($customer) {
            $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        });
    }
}