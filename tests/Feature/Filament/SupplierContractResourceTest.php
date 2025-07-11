<?php

use App\Filament\Resources\SupplierContractResource\Pages;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    // Create teams if they don't exist
    $managerTeam = Team::firstOrCreate(['name' => 'Manager']);
    $user = User::factory()->create();
    $user->teams()->attach($managerTeam);
    $this->actingAs($user);
});

it('kann die Vertrags-Liste rendern', function () {
    Livewire::test(Pages\ListSupplierContracts::class)
        ->assertSuccessful();
});

it('kann Verträge auflisten', function () {
    $contracts = SupplierContract::factory()->count(3)->create();

    Livewire::test(Pages\ListSupplierContracts::class)
        ->assertCanSeeTableRecords($contracts)
        ->assertCountTableRecords(3);
});

it('kann einen Vertrag erstellen', function () {
    $supplier = Supplier::factory()->create();
    $newContract = SupplierContract::factory()->make([
        'supplier_id' => $supplier->id,
    ]);

    Livewire::test(Pages\CreateSupplierContract::class)
        ->fillForm([
            'supplier_id' => $newContract->supplier_id,
            'contract_number' => $newContract->contract_number,
            'title' => $newContract->title,
            'status' => $newContract->status,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('supplier_contracts', [
        'supplier_id' => $newContract->supplier_id,
        'contract_number' => $newContract->contract_number,
        'title' => $newContract->title,
    ]);
});

it('kann einen Vertrag bearbeiten', function () {
    $contract = SupplierContract::factory()->create();
    $newData = SupplierContract::factory()->make();

    Livewire::test(Pages\EditSupplierContract::class, [
        'record' => $contract->getRouteKey(),
    ])
    ->fillForm([
        'title' => $newData->title,
        'description' => $newData->description,
        'status' => $newData->status,
    ])
    ->call('save')
    ->assertHasNoFormErrors();

    $this->assertDatabaseHas('supplier_contracts', [
        'id' => $contract->id,
        'title' => $newData->title,
    ]);
});

it('kann nach Verträgen suchen', function () {
    $contractToFind = SupplierContract::factory()->create(['title' => 'Find Me Contract']);
    $otherContract = SupplierContract::factory()->create(['title' => 'Ignore Me Contract']);

    Livewire::test(Pages\ListSupplierContracts::class)
        ->searchTable($contractToFind->title)
        ->assertCanSeeTableRecords([$contractToFind])
        ->assertDontSee($otherContract->title);
});
