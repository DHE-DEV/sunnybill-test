<?php

use App\Filament\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\SupplierType;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Actions\BulkAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = actingAsAdmin();
});

describe('Supplier Resource - Index Page', function () {
    it('kann die Lieferanten-Übersichtsseite anzeigen', function () {
        Livewire::test(SupplierResource\Pages\ListSuppliers::class)
            ->assertSuccessful();
    });

    it('zeigt Lieferanten in der Tabelle an', function () {
        $supplierType = createSupplierType(['name' => 'Direktvermarkter']);
        $supplier = createSupplier([
            'company_name' => 'Test Lieferant GmbH',
            'supplier_type_id' => $supplierType->id,
            'city' => 'Berlin',
            'is_active' => true
        ]);

        Livewire::test(SupplierResource\Pages\ListSuppliers::class)
            ->assertCanSeeTableRecords([$supplier]);
    });

    it('kann nach Lieferanten suchen', function () {
        $supplierType = createSupplierType();
        $supplier1 = createSupplier([
            'company_name' => 'Solartech GmbH',
            'supplier_type_id' => $supplierType->id
        ]);
        $supplier2 = createSupplier([
            'company_name' => 'Windkraft AG',
            'supplier_type_id' => $supplierType->id
        ]);

        Livewire::test(SupplierResource\Pages\ListSuppliers::class)
            ->searchTable('Solartech')
            ->assertCanSeeTableRecords([$supplier1])
            ->assertCanNotSeeTableRecords([$supplier2]);
    });

    it('kann Lieferanten nach Typ filtern', function () {
        $direktvermarkter = createSupplierType(['name' => 'Direktvermarkter']);
        $installateur = createSupplierType(['name' => 'Installateur']);
        
        $supplier1 = createSupplier([
            'company_name' => 'Direktvermarkter GmbH',
            'supplier_type_id' => $direktvermarkter->id
        ]);
        $supplier2 = createSupplier([
            'company_name' => 'Installateur AG',
            'supplier_type_id' => $installateur->id
        ]);

        Livewire::test(SupplierResource\Pages\ListSuppliers::class)
            ->filterTable('supplier_type_id', $direktvermarkter->id)
            ->assertCanSeeTableRecords([$supplier1])
            ->assertCanNotSeeTableRecords([$supplier2]);
    });

    it('kann Lieferanten nach Aktivitätsstatus filtern', function () {
        $supplierType = createSupplierType();
        $activeSupplier = createSupplier([
            'company_name' => 'Aktiver Lieferant',
            'supplier_type_id' => $supplierType->id,
            'is_active' => true
        ]);
        $inactiveSupplier = createSupplier([
            'company_name' => 'Inaktiver Lieferant',
            'supplier_type_id' => $supplierType->id,
            'is_active' => false
        ]);

        Livewire::test(SupplierResource\Pages\ListSuppliers::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activeSupplier])
            ->assertCanNotSeeTableRecords([$inactiveSupplier]);
    });

    it('kann Lieferanten nach Lexoffice-Synchronisation filtern', function () {
        $supplierType = createSupplierType();
        $syncedSupplier = createSupplier([
            'company_name' => 'Synchronisierter Lieferant',
            'supplier_type_id' => $supplierType->id,
            'lexoffice_id' => 'lex-123'
        ]);
        $unsyncedSupplier = createSupplier([
            'company_name' => 'Nicht synchronisierter Lieferant',
            'supplier_type_id' => $supplierType->id,
            'lexoffice_id' => null
        ]);

        Livewire::test(SupplierResource\Pages\ListSuppliers::class)
            ->filterTable('lexoffice_synced', true)
            ->assertCanSeeTableRecords([$syncedSupplier])
            ->assertCanNotSeeTableRecords([$unsyncedSupplier]);
    });

    it('kann Lieferanten sortieren', function () {
        $supplierType = createSupplierType();
        $supplierA = createSupplier([
            'company_name' => 'A-Lieferant',
            'supplier_type_id' => $supplierType->id
        ]);
        $supplierZ = createSupplier([
            'company_name' => 'Z-Lieferant',
            'supplier_type_id' => $supplierType->id
        ]);

        Livewire::test(SupplierResource\Pages\ListSuppliers::class)
            ->sortTable('company_name')
            ->assertCanSeeTableRecords([$supplierA, $supplierZ], inOrder: true);
    });
});

describe('Supplier Resource - Create Page', function () {
    it('kann die Lieferanten-Erstellungsseite anzeigen', function () {
        Livewire::test(SupplierResource\Pages\CreateSupplier::class)
            ->assertSuccessful();
    });

    it('kann einen neuen Lieferanten erstellen', function () {
        $supplierType = createSupplierType(['name' => 'Direktvermarkter']);
        
        $supplierData = [
            'company_name' => 'Neue Lieferant GmbH',
            'supplier_type_id' => $supplierType->id,
            'contact_person' => 'Max Mustermann',
            'email' => 'max@neue-lieferant.de',
            'website' => 'https://neue-lieferant.de',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'country' => 'Deutschland',
            'tax_number' => '123/456/7890',
            'vat_id' => 'DE123456789',
            'notes' => 'Test Notizen',
            'is_active' => true,
        ];

        Livewire::test(SupplierResource\Pages\CreateSupplier::class)
            ->fillForm($supplierData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('suppliers', [
            'company_name' => 'Neue Lieferant GmbH',
            'email' => 'max@neue-lieferant.de',
            'supplier_type_id' => $supplierType->id,
        ]);
    });

    it('validiert erforderliche Felder beim Erstellen', function () {
        Livewire::test(SupplierResource\Pages\CreateSupplier::class)
            ->fillForm([
                'company_name' => '', // Erforderlich
            ])
            ->call('create')
            ->assertHasFormErrors(['company_name' => 'required']);
    });

    it('validiert E-Mail-Format beim Erstellen', function () {
        $supplierType = createSupplierType();
        
        Livewire::test(SupplierResource\Pages\CreateSupplier::class)
            ->fillForm([
                'company_name' => 'Test Lieferant',
                'supplier_type_id' => $supplierType->id,
                'email' => 'ungueltige-email',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'email']);
    });

    it('validiert Website-URL beim Erstellen', function () {
        $supplierType = createSupplierType();
        
        Livewire::test(SupplierResource\Pages\CreateSupplier::class)
            ->fillForm([
                'company_name' => 'Test Lieferant',
                'supplier_type_id' => $supplierType->id,
                'website' => 'ungueltige-url',
            ])
            ->call('create')
            ->assertHasFormErrors(['website' => 'url']);
    });

    it('generiert automatisch eine Lieferantennummer beim Erstellen', function () {
        $supplierType = createSupplierType();
        
        Livewire::test(SupplierResource\Pages\CreateSupplier::class)
            ->fillForm([
                'company_name' => 'Auto-Nummer Lieferant',
                'supplier_type_id' => $supplierType->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $supplier = Supplier::where('company_name', 'Auto-Nummer Lieferant')->first();
        expect($supplier->supplier_number)->not->toBeEmpty();
    });
});

describe('Supplier Resource - Edit Page', function () {
    it('kann die Lieferanten-Bearbeitungsseite anzeigen', function () {
        $supplier = createSupplierWithType();
        
        Livewire::test(SupplierResource\Pages\EditSupplier::class, ['record' => $supplier->getRouteKey()])
            ->assertSuccessful();
    });

    it('kann einen Lieferanten bearbeiten', function () {
        $supplier = createSupplierWithType([
            'company_name' => 'Alter Name',
            'email' => 'alt@example.com'
        ]);

        Livewire::test(SupplierResource\Pages\EditSupplier::class, ['record' => $supplier->getRouteKey()])
            ->fillForm([
                'company_name' => 'Neuer Name',
                'email' => 'neu@example.com',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $supplier->refresh();
        expect($supplier->company_name)->toBe('Neuer Name');
        expect($supplier->email)->toBe('neu@example.com');
    });

    it('validiert erforderliche Felder beim Bearbeiten', function () {
        $supplier = createSupplierWithType();

        Livewire::test(SupplierResource\Pages\EditSupplier::class, ['record' => $supplier->getRouteKey()])
            ->fillForm([
                'company_name' => '', // Erforderlich
            ])
            ->call('save')
            ->assertHasFormErrors(['company_name' => 'required']);
    });

    it('kann einen Lieferanten deaktivieren', function () {
        $supplier = createSupplierWithType(['is_active' => true]);

        Livewire::test(SupplierResource\Pages\EditSupplier::class, ['record' => $supplier->getRouteKey()])
            ->fillForm([
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $supplier->refresh();
        expect($supplier->is_active)->toBeFalse();
    });
});

describe('Supplier Resource - View Page', function () {
    it('kann die Lieferanten-Detailseite anzeigen', function () {
        $supplier = createSupplierWithType();
        
        Livewire::test(SupplierResource\Pages\ViewSupplier::class, ['record' => $supplier->getRouteKey()])
            ->assertSuccessful();
    });

    it('zeigt Lieferanteninformationen auf der Detailseite an', function () {
        $supplierType = createSupplierType(['name' => 'Direktvermarkter']);
        $supplier = createSupplier([
            'company_name' => 'Detail Test Lieferant',
            'supplier_type_id' => $supplierType->id,
            'email' => 'detail@test.com',
            'city' => 'München'
        ]);

        Livewire::test(SupplierResource\Pages\ViewSupplier::class, ['record' => $supplier->getRouteKey()])
            ->assertSee('Detail Test Lieferant');
    });
});

describe('Supplier Resource - Delete Operations', function () {
    it('kann einen Lieferanten löschen', function () {
        $supplier = createSupplierWithType();

        Livewire::test(SupplierResource\Pages\EditSupplier::class, ['record' => $supplier->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    });

    it('kann einen gelöschten Lieferanten wiederherstellen', function () {
        $supplier = createSupplierWithType();
        $supplierId = $supplier->id;
        $supplier->delete();

        // Teste die Wiederherstellung direkt über das Model
        $deletedSupplier = Supplier::withTrashed()->find($supplierId);
        expect($deletedSupplier)->not->toBeNull();
        expect($deletedSupplier->deleted_at)->not->toBeNull();

        // Stelle den Lieferanten wieder her
        $deletedSupplier->restore();
        
        // Prüfe, dass der Lieferant wiederhergestellt wurde
        $restoredSupplier = Supplier::find($supplierId);
        expect($restoredSupplier)->not->toBeNull();
        expect($restoredSupplier->deleted_at)->toBeNull();
    });

    it('kann mehrere Lieferanten gleichzeitig löschen', function () {
        $supplierType = createSupplierType();
        $supplier1 = createSupplier(['supplier_type_id' => $supplierType->id]);
        $supplier2 = createSupplier(['supplier_type_id' => $supplierType->id]);

        Livewire::test(SupplierResource\Pages\ListSuppliers::class)
            ->callTableBulkAction('delete', [$supplier1, $supplier2]);

        $this->assertSoftDeleted('suppliers', ['id' => $supplier1->id]);
        $this->assertSoftDeleted('suppliers', ['id' => $supplier2->id]);
    });
});

describe('Supplier Resource - Business Logic', function () {
    it('zeigt den korrekten Anzeigenamen an', function () {
        $supplier1 = createSupplierWithType([
            'name' => 'Max Mustermann',
            'company_name' => 'Mustermann GmbH'
        ]);
        
        $supplierType = createSupplierType();
        $supplier2 = createSupplier([
            'name' => 'Anna Schmidt',
            'company_name' => 'Schmidt Einzelunternehmen', // company_name ist erforderlich
            'supplier_type_id' => $supplierType->id
        ]);

        expect($supplier1->display_name)->toBe('Mustermann GmbH');
        expect($supplier2->display_name)->toBe('Schmidt Einzelunternehmen');
    });

    it('formatiert die vollständige Adresse korrekt', function () {
        $supplier = createSupplierWithType([
            'street' => 'Musterstraße 123',
            'address_line_2' => 'Hinterhaus',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'state' => 'Berlin',
            'country' => 'Deutschland'
        ]);

        $expectedAddress = "Musterstraße 123\nHinterhaus\n12345 Berlin, Berlin";
        expect($supplier->full_address)->toBe($expectedAddress);
    });

    it('prüft USt-IdNr korrekt', function () {
        $supplierWithVat = createSupplierWithType(['vat_id' => 'DE123456789']);
        $supplierWithoutVat = createSupplierWithType(['vat_id' => null]);

        expect($supplierWithVat->hasVatId())->toBeTrue();
        expect($supplierWithoutVat->hasVatId())->toBeFalse();
    });

    it('formatiert deutsche USt-IdNr korrekt', function () {
        $supplier = createSupplierWithType(['vat_id' => 'DE123456789']);

        expect($supplier->formatted_vat_id)->toBe('DE 123 456 789');
    });

    it('prüft Lexoffice-Synchronisation korrekt', function () {
        $syncedSupplier = createSupplierWithType(['lexoffice_id' => 'lex-123']);
        $unsyncedSupplier = createSupplierWithType(['lexoffice_id' => null]);

        expect($syncedSupplier->isSyncedWithLexoffice())->toBeTrue();
        expect($unsyncedSupplier->isSyncedWithLexoffice())->toBeFalse();
    });

    it('filtert aktive Lieferanten korrekt', function () {
        $supplierType = createSupplierType();
        $activeSupplier = createSupplier([
            'company_name' => 'Aktiver Lieferant',
            'supplier_type_id' => $supplierType->id,
            'is_active' => true
        ]);
        $inactiveSupplier = createSupplier([
            'company_name' => 'Inaktiver Lieferant',
            'supplier_type_id' => $supplierType->id,
            'is_active' => false
        ]);

        $activeSuppliers = Supplier::active()->get();
        $activeSupplierIds = $activeSuppliers->pluck('id')->toArray();

        expect($activeSupplierIds)->toContain($activeSupplier->id);
        expect($activeSupplierIds)->not->toContain($inactiveSupplier->id);
    });
});

describe('Supplier Resource - Table Columns', function () {
    it('zeigt alle wichtigen Tabellenspalten an', function () {
        $supplierType = createSupplierType(['name' => 'Direktvermarkter']);
        $supplier = createSupplier([
            'supplier_number' => 'L-2024-001',
            'creditor_number' => 'K1234',
            'contract_number' => 'V-2024-001',
            'company_name' => 'Tabellen Test GmbH',
            'supplier_type_id' => $supplierType->id,
            'contact_person' => 'Test Person',
            'email' => 'test@tabelle.de',
            'city' => 'Hamburg',
            'is_active' => true
        ]);

        Livewire::test(SupplierResource\Pages\ListSuppliers::class)
            ->assertTableColumnExists('supplier_number')
            ->assertTableColumnExists('creditor_number')
            ->assertTableColumnExists('contract_number')
            ->assertTableColumnExists('company_name')
            ->assertTableColumnExists('supplierType.name')
            ->assertTableColumnExists('contact_person')
            ->assertTableColumnExists('email')
            ->assertTableColumnExists('city')
            ->assertTableColumnExists('is_active')
            ->assertTableColumnExists('lexoffice_synced');
    });
});