<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\InteractsWithApi;
use Tests\Traits\CreatesTestData;
use App\Models\SolarPlant;

class SolarPlantApiTest extends TestCase
{
    use InteractsWithApi, CreatesTestData;

    public function test_can_list_solar_plants()
    {
        $this->createMultiple('solarPlant', 5);

        $response = $this->apiGet('/solar-plants', ['solar-plants:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'plant_number',
                        'name',
                        'location',
                        'total_capacity_kw',
                        'status',
                        'commission_date',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'total',
                    'per_page'
                ]
            ]);
    }

    public function test_can_create_solar_plant()
    {
        $solarPlantData = [
            'plant_number' => 'PVA-001',
            'name' => 'Test Solar Plant',
            'location' => 'Munich, Germany',
            'total_capacity_kw' => 100.5,
            'status' => 'active',
            'commission_date' => '2024-01-15',
            'description' => 'Test solar plant description',
        ];

        $response = $this->apiPost('/solar-plants', $solarPlantData, ['solar-plants:create']);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'plant_number',
                    'name',
                    'location',
                    'total_capacity_kw',
                    'status',
                    'commission_date'
                ]
            ]);

        $this->assertDatabaseHas('solar_plants', [
            'plant_number' => 'PVA-001',
            'name' => 'Test Solar Plant',
            'location' => 'Munich, Germany',
            'total_capacity_kw' => 100.5,
            'status' => 'active',
        ]);
    }

    public function test_can_show_solar_plant()
    {
        $solarPlant = $this->createSolarPlant();

        $response = $this->apiGet("/solar-plants/{$solarPlant->id}", ['solar-plants:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'plant_number',
                    'name',
                    'location',
                    'total_capacity_kw',
                    'status',
                    'commission_date',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $solarPlant->id,
                    'plant_number' => $solarPlant->plant_number,
                    'name' => $solarPlant->name,
                ]
            ]);
    }

    public function test_can_update_solar_plant()
    {
        $solarPlant = $this->createSolarPlant();
        
        $updateData = [
            'name' => 'Updated Solar Plant',
            'location' => 'Berlin, Germany',
            'total_capacity_kw' => 150.0,
            'status' => 'maintenance',
        ];

        $response = $this->apiPut("/solar-plants/{$solarPlant->id}", $updateData, ['solar-plants:update']);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $solarPlant->id,
                    'name' => 'Updated Solar Plant',
                    'location' => 'Berlin, Germany',
                    'total_capacity_kw' => 150.0,
                    'status' => 'maintenance',
                ]
            ]);

        $this->assertDatabaseHas('solar_plants', [
            'id' => $solarPlant->id,
            'name' => 'Updated Solar Plant',
            'location' => 'Berlin, Germany',
            'total_capacity_kw' => 150.0,
            'status' => 'maintenance',
        ]);
    }

    public function test_can_delete_solar_plant()
    {
        $solarPlant = $this->createSolarPlant();

        $response = $this->apiDelete("/solar-plants/{$solarPlant->id}", ['solar-plants:delete']);

        $response->assertStatus(204);

        $this->assertSoftDeleted('solar_plants', [
            'id' => $solarPlant->id,
        ]);
    }

    public function test_can_get_solar_plant_components()
    {
        $solarPlant = $this->createSolarPlant();

        // Create components (modules, inverters, batteries)
        $solarPlant->solarModules()->create([
            'module_type' => 'Monocrystalline',
            'manufacturer' => 'SunPower',
            'model' => 'SPR-400',
            'power_rating_w' => 400,
            'quantity' => 10,
        ]);

        $response = $this->apiGet("/solar-plants/{$solarPlant->id}/components", ['solar-plants:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'modules' => [
                        '*' => [
                            'id',
                            'module_type',
                            'manufacturer',
                            'model',
                            'power_rating_w',
                            'quantity'
                        ]
                    ],
                    'inverters' => [],
                    'batteries' => []
                ]
            ]);
    }

    public function test_can_get_solar_plant_participations()
    {
        $solarPlant = $this->createSolarPlant();
        $customer = $this->createCustomer();

        // Create participation
        $solarPlant->participations()->create([
            'customer_id' => $customer->id,
            'percentage' => 25.0,
            'participation_kwp' => 25.0,
        ]);

        $response = $this->apiGet("/solar-plants/{$solarPlant->id}/participations", ['solar-plants:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_id',
                        'percentage',
                        'participation_kwp',
                        'customer' => [
                            'id',
                            'name',
                            'email'
                        ]
                    ]
                ]
            ]);
    }

    public function test_can_get_solar_plant_statistics()
    {
        $solarPlant = $this->createSolarPlant();

        $response = $this->apiGet("/solar-plants/{$solarPlant->id}/statistics", ['solar-plants:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_capacity_kw',
                    'total_participants',
                    'total_energy_generated_kwh',
                    'monthly_average_kwh',
                    'efficiency_percentage',
                    'co2_savings_kg',
                ]
            ]);
    }

    public function test_unauthorized_access_to_solar_plants()
    {
        $this->assertUnauthorized('GET', '/solar-plants');
        $this->assertUnauthorized('POST', '/solar-plants', ['name' => 'Test']);
    }

    public function test_forbidden_access_with_wrong_permissions()
    {
        $solarPlant = $this->createSolarPlant();
        
        $this->assertForbidden('GET', '/solar-plants', ['solar-plants:read']);
        $this->assertForbidden('POST', '/solar-plants', ['solar-plants:create'], ['name' => 'Test']);
        $this->assertForbidden('PUT', "/solar-plants/{$solarPlant->id}", ['solar-plants:update'], ['name' => 'Updated']);
        $this->assertForbidden('DELETE', "/solar-plants/{$solarPlant->id}", ['solar-plants:delete']);
    }

    public function test_validation_errors_when_creating_solar_plant()
    {
        $response = $this->apiPost('/solar-plants', [], ['solar-plants:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plant_number', 'name', 'location', 'total_capacity_kw']);
    }

    public function test_unique_plant_number_validation()
    {
        $existingPlant = $this->createSolarPlant(['plant_number' => 'PVA-001']);

        $solarPlantData = [
            'plant_number' => 'PVA-001', // Same plant number
            'name' => 'Another Plant',
            'location' => 'Berlin, Germany',
            'total_capacity_kw' => 50.0,
        ];

        $response = $this->apiPost('/solar-plants', $solarPlantData, ['solar-plants:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plant_number']);
    }

    public function test_solar_plant_not_found()
    {
        $response = $this->apiGet('/solar-plants/999', ['solar-plants:read']);
        
        $response->assertStatus(404);
    }

    public function test_can_get_monthly_results()
    {
        $solarPlant = $this->createSolarPlant();

        // Create some monthly results
        $solarPlant->monthlyResults()->create([
            'year' => 2024,
            'month' => 1,
            'energy_generated_kwh' => 1500.5,
            'energy_fed_into_grid_kwh' => 1200.0,
            'feed_in_tariff_cents_per_kwh' => 8.5,
            'revenue_euros' => 102.0,
        ]);

        $response = $this->apiGet("/solar-plants/{$solarPlant->id}/monthly-results", ['solar-plants:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'year',
                        'month',
                        'energy_generated_kwh',
                        'energy_fed_into_grid_kwh',
                        'feed_in_tariff_cents_per_kwh',
                        'revenue_euros'
                    ]
                ]
            ]);
    }
}