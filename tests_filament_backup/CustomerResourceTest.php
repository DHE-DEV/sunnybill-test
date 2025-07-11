<?php

use App\Models\User;
use App\Models\Customer;
use App\Filament\Resources\CustomerResource;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;

beforeEach(function () {
    // Erstelle einen Admin-Benutzer für Filament-Tests
    $this->adminUser = createAdminUser([
        'email' => 'admin@test.com',
        'name' => 'Test Admin',
    ]);
    
    // Authentifiziere den Benutzer für Filament
    $this->actingAs($this->adminUser);
});

describe('CustomerResource List Page', function () {
    it('kann die Kunden-Listenseite laden', function () {
        // Erstelle Testdaten
        $customers = Customer::factory()->count(3)->create();
        
        // Teste die Listenseite
        Livewire::test(CustomerResource\Pages\ListCustomers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($customers);
    });
    
    it('zeigt Kundendaten in der Tabelle an', function () {
        $customer = Customer::factory()->create([
            'name' => 'Max Mustermann',
            'email' => 'max@example.com',
            'customer_type' => 'private',
            'is_active' => true,
        ]);
        
        Livewire::test(CustomerResource\Pages\ListCustomers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$customer])
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('email')
            ->assertTableColumnExists('customer_type')
            ->assertSeeInOrder(['Max Mustermann', 'max@example.com']);
    });
    
    it('kann Kunden nach Namen filtern', function () {
        $customer1 = Customer::factory()->create(['name' => 'Max Mustermann']);
        $customer2 = Customer::factory()->create(['name' => 'Anna Schmidt']);
        
        Livewire::test(CustomerResource\Pages\ListCustomers::class)
            ->searchTable('Max')
            ->assertCanSeeTableRecords([$customer1])
            ->assertCanNotSeeTableRecords([$customer2]);
    });
    
    it('kann zwischen aktiven und deaktivierten Kunden filtern', function () {
        $activeCustomer = Customer::factory()->create(['is_active' => true]);
        $deactivatedCustomer = Customer::factory()->deactivated()->create();
        
        Livewire::test(CustomerResource\Pages\ListCustomers::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activeCustomer])
            ->assertCanNotSeeTableRecords([$deactivatedCustomer]);
    });
});

describe('CustomerResource Create Page', function () {
    it('kann einen neuen Privatkunden erstellen', function () {
        $customerData = [
            'customer_type' => 'private',
            'name' => 'Test Kunde',
            'email' => 'test@example.com',
            'phone' => '+49 123 456789',
            'street' => 'Teststraße 123',
            'postal_code' => '12345',
            'city' => 'Teststadt',
        ];
        
        Livewire::test(CustomerResource\Pages\CreateCustomer::class)
            ->fillForm($customerData)
            ->call('create')
            ->assertHasNoFormErrors();
        
        expect(Customer::where('email', 'test@example.com')->first())
            ->not->toBeNull()
            ->name->toBe('Test Kunde')
            ->customer_type->toBe('private');
    });
    
    it('validiert erforderliche Felder beim Erstellen', function () {
        Livewire::test(CustomerResource\Pages\CreateCustomer::class)
            ->fillForm([
                'customer_type' => 'private',
                // Name fehlt absichtlich
                'email' => 'invalid-email', // Ungültige E-Mail
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'email']);
    });
    
    it('kann einen Geschäftskunden erstellen', function () {
        $customerData = [
            'customer_type' => 'business',
            'name' => 'Musterfirma GmbH',
            'company_name' => 'Musterfirma GmbH',
            'contact_person' => 'Hans Müller',
            'email' => 'info@musterfirma.de',
            'phone' => '+49 123 456789',
            'street' => 'Industriestraße 456',
            'postal_code' => '54321',
            'city' => 'Industriestadt',
            'vat_id' => 'DE123456789',
        ];
        
        Livewire::test(CustomerResource\Pages\CreateCustomer::class)
            ->fillForm($customerData)
            ->call('create')
            ->assertHasNoFormErrors();
        
        $customer = Customer::where('email', 'info@musterfirma.de')->first();
        
        expect($customer)
            ->not->toBeNull()
            ->customer_type->toBe('business')
            ->company_name->toBe('Musterfirma GmbH')
            ->vat_id->toBe('DE123456789');
    });
});

describe('CustomerResource Edit Page', function () {
    it('kann einen bestehenden Kunden bearbeiten', function () {
        $customer = Customer::factory()->create([
            'name' => 'Alter Name',
            'email' => 'alt@example.com',
        ]);
        
        Livewire::test(CustomerResource\Pages\EditCustomer::class, [
            'record' => $customer->getRouteKey(),
        ])
            ->fillForm([
                'name' => 'Neuer Name',
                'email' => 'neu@example.com',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
        
        expect($customer->fresh())
            ->name->toBe('Neuer Name')
            ->email->toBe('neu@example.com');
    });
    
    it('kann einen Kunden deaktivieren', function () {
        $customer = Customer::factory()->create(['is_active' => true]);
        
        Livewire::test(CustomerResource\Pages\EditCustomer::class, [
            'record' => $customer->getRouteKey(),
        ])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();
        
        expect($customer->fresh())
            ->is_active->toBeFalse()
            ->deactivated_at->not->toBeNull();
    });
    
    it('zeigt Kundendaten korrekt im Formular an', function () {
        $customer = Customer::factory()->create([
            'name' => 'Test Kunde',
            'email' => 'test@example.com',
            'customer_type' => 'private',
        ]);
        
        Livewire::test(CustomerResource\Pages\EditCustomer::class, [
            'record' => $customer->getRouteKey(),
        ])
            ->assertFormSet([
                'name' => 'Test Kunde',
                'email' => 'test@example.com',
                'customer_type' => 'private',
            ]);
    });
});

describe('CustomerResource Delete Actions', function () {
    it('kann einen Kunden löschen', function () {
        $customer = Customer::factory()->create();
        
        Livewire::test(CustomerResource\Pages\EditCustomer::class, [
            'record' => $customer->getRouteKey(),
        ])
            ->callAction(DeleteAction::class)
            ->assertSuccessful();
        
        expect(Customer::find($customer->id))->toBeNull();
        expect(Customer::withTrashed()->find($customer->id))->not->toBeNull();
    });
    
    it('kann einen gelöschten Kunden wiederherstellen', function () {
        $customer = Customer::factory()->create();
        $customer->delete(); // Soft delete
        
        expect(Customer::find($customer->id))->toBeNull();
        expect(Customer::withTrashed()->find($customer->id))->not->toBeNull();
        
        // Wiederherstellen
        $customer->restore();
        
        expect(Customer::find($customer->id))->not->toBeNull();
    });
});

describe('CustomerResource Validation', function () {
    it('validiert E-Mail-Format', function () {
        Livewire::test(CustomerResource\Pages\CreateCustomer::class)
            ->fillForm([
                'customer_type' => 'private',
                'name' => 'Test Kunde',
                'email' => 'ungueltige-email',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    });
    
    it('validiert eindeutige E-Mail-Adresse', function () {
        Customer::factory()->create(['email' => 'existing@example.com']);
        
        Livewire::test(CustomerResource\Pages\CreateCustomer::class)
            ->fillForm([
                'customer_type' => 'private',
                'name' => 'Test Kunde',
                'email' => 'existing@example.com',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    });
    
    it('validiert Postleitzahl-Format', function () {
        Livewire::test(CustomerResource\Pages\CreateCustomer::class)
            ->fillForm([
                'customer_type' => 'private',
                'name' => 'Test Kunde',
                'email' => 'test@example.com',
                'postal_code' => '123', // Zu kurz
            ])
            ->call('create')
            ->assertHasFormErrors(['postal_code']);
    });
});