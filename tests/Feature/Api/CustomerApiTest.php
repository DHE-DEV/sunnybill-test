<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\InteractsWithApi;
use Tests\Traits\CreatesTestData;
use App\Models\Customer;

class CustomerApiTest extends TestCase
{
    use InteractsWithApi, CreatesTestData;

    public function test_can_list_customers()
    {
        $this->createMultiple('customer', 5);

        $response = $this->apiGet('/customers', ['customers:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'customer_type',
                        'company_name',
                        'status',
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

    public function test_can_create_customer()
    {
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'customer_type' => 'private',
            'phone' => '+49 123 456789',
            'status' => 'active',
        ];

        $response = $this->apiPost('/customers', $customerData, ['customers:create']);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'customer_type',
                    'phone',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'customer_type' => 'private',
            'status' => 'active',
        ]);
    }

    public function test_can_create_business_customer()
    {
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@company.com',
            'customer_type' => 'business',
            'company_name' => 'Doe Industries',
            'tax_number' => 'DE123456789',
            'status' => 'active',
        ];

        $response = $this->apiPost('/customers', $customerData, ['customers:create']);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'customer_type' => 'business',
                    'company_name' => 'Doe Industries',
                    'tax_number' => 'DE123456789',
                ]
            ]);

        $this->assertDatabaseHas('customers', [
            'customer_type' => 'business',
            'company_name' => 'Doe Industries',
            'tax_number' => 'DE123456789',
        ]);
    }

    public function test_can_show_customer()
    {
        $customer = $this->createCustomer();

        $response = $this->apiGet("/customers/{$customer->id}", ['customers:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'customer_type',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                ]
            ]);
    }

    public function test_can_update_customer()
    {
        $customer = $this->createCustomer();
        
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => 'inactive',
        ];

        $response = $this->apiPut("/customers/{$customer->id}", $updateData, ['customers:update']);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $customer->id,
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'status' => 'inactive',
                ]
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => 'inactive',
        ]);
    }

    public function test_can_delete_customer()
    {
        $customer = $this->createCustomer();

        $response = $this->apiDelete("/customers/{$customer->id}", ['customers:delete']);

        $response->assertStatus(204);

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_can_update_customer_status()
    {
        $customer = $this->createCustomer(['status' => 'active']);

        $response = $this->apiPatch("/customers/{$customer->id}/status", [
            'status' => 'inactive'
        ], ['customers:status']);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $customer->id,
                    'status' => 'inactive',
                ]
            ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => 'inactive',
        ]);
    }

    public function test_can_get_customer_participations()
    {
        $customer = $this->createCustomer();
        $solarPlant = $this->createSolarPlant();
        
        // Create participations relationship
        $customer->participations()->create([
            'solar_plant_id' => $solarPlant->id,
            'percentage' => 25.0,
            'participation_kwp' => 5.0,
        ]);

        $response = $this->apiGet("/customers/{$customer->id}/participations", ['customers:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'solar_plant_id',
                        'percentage',
                        'participation_kwp',
                        'solar_plant' => [
                            'id',
                            'name',
                            'plant_number'
                        ]
                    ]
                ]
            ]);
    }

    public function test_can_get_customer_projects()
    {
        $customer = $this->createCustomer();
        $project = $this->createProject(['customer_id' => $customer->id]);

        $response = $this->apiGet("/customers/{$customer->id}/projects", ['customers:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'status',
                        'customer_id'
                    ]
                ]
            ]);
    }

    public function test_can_get_customer_tasks()
    {
        $customer = $this->createCustomer();
        $task = $this->createTask(['customer_id' => $customer->id]);

        $response = $this->apiGet("/customers/{$customer->id}/tasks", ['customers:read']);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'status',
                        'customer_id'
                    ]
                ]
            ]);
    }

    public function test_unauthorized_access_to_customers()
    {
        $this->assertUnauthorized('GET', '/customers');
        $this->assertUnauthorized('POST', '/customers', ['name' => 'Test']);
    }

    public function test_forbidden_access_with_wrong_permissions()
    {
        $customer = $this->createCustomer();
        
        $this->assertForbidden('GET', '/customers', ['customers:read']);
        $this->assertForbidden('POST', '/customers', ['customers:create'], ['name' => 'Test']);
        $this->assertForbidden('PUT', "/customers/{$customer->id}", ['customers:update'], ['name' => 'Updated']);
        $this->assertForbidden('DELETE', "/customers/{$customer->id}", ['customers:delete']);
    }

    public function test_validation_errors_when_creating_customer()
    {
        $response = $this->apiPost('/customers', [], ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'customer_type']);
    }

    public function test_validation_errors_for_business_customer()
    {
        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@company.com',
            'customer_type' => 'business',
            // Missing company_name for business customer
        ];

        $response = $this->apiPost('/customers', $customerData, ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    public function test_customer_not_found()
    {
        $response = $this->apiGet('/customers/999', ['customers:read']);
        
        $response->assertStatus(404);
    }

    public function test_unique_email_validation()
    {
        $existingCustomer = $this->createCustomer(['email' => 'test@example.com']);

        $customerData = [
            'name' => 'Another Customer',
            'email' => 'test@example.com', // Same email
            'customer_type' => 'private',
        ];

        $response = $this->apiPost('/customers', $customerData, ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}