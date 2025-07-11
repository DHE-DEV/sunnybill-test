<?php

use App\Filament\Resources\SolarPlantResource\Pages;
use App\Models\SolarPlant;
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

it('kann die Solaranlagen-Liste rendern', function () {
    Livewire::test(Pages\ListSolarPlants::class)
        ->assertSuccessful();
});

it('kann Solaranlagen auflisten', function () {
    $plants = SolarPlant::factory()->count(3)->create();

    Livewire::test(Pages\ListSolarPlants::class)
        ->assertCanSeeTableRecords($plants)
        ->assertCountTableRecords(3);
});

it('kann eine Solaranlage erstellen', function () {
    $newPlant = SolarPlant::factory()->make();

    Livewire::test(Pages\CreateSolarPlant::class)
        ->fillForm([
            'name' => $newPlant->name,
            'location' => $newPlant->location,
            'total_capacity_kw' => $newPlant->total_capacity_kw,
            'status' => $newPlant->status,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('solar_plants', [
        'name' => $newPlant->name,
        'location' => $newPlant->location,
    ]);
});

it('kann eine Solaranlage bearbeiten', function () {
    $plant = SolarPlant::factory()->create();
    $newData = SolarPlant::factory()->make();

    Livewire::test(Pages\EditSolarPlant::class, [
        'record' => $plant->getRouteKey(),
    ])
    ->fillForm([
        'name' => $newData->name,
        'location' => $newData->location,
        'total_capacity_kw' => $newData->total_capacity_kw,
        'status' => $newData->status,
        'installation_date' => now()->toDateString(),
        'commissioning_date' => now()->addDay()->toDateString(),
    ])
    ->call('save')
    ->assertHasNoFormErrors();

    $this->assertDatabaseHas('solar_plants', [
        'id' => $plant->id,
        'name' => $newData->name,
    ]);
});

it('kann nach Solaranlagen suchen', function () {
    $plantToFind = SolarPlant::factory()->create(['name' => 'Find Me Solar Plant']);
    $otherPlant = SolarPlant::factory()->create(['name' => 'Ignore Me Solar Plant']);

    Livewire::test(Pages\ListSolarPlants::class)
        ->searchTable($plantToFind->name)
        ->assertCanSeeTableRecords([$plantToFind])
        ->assertDontSee($otherPlant->name);
});
