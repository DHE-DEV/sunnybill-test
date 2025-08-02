<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Task;
use App\Models\Project;
use App\Models\PlantParticipation;
use App\Models\SolarPlant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_created()
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'customer_type' => 'private',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('John Doe', $customer->name);
        $this->assertEquals('john@example.com', $customer->email);
        $this->assertEquals('private', $customer->customer_type);
        $this->assertEquals('active', $customer->status);
    }

    public function test_customer_can_be_business_type()
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john@company.com',
            'customer_type' => 'business',
            'company_name' => 'Doe Industries',
            'tax_number' => 'DE123456789',
            'status' => 'active',
        ]);

        $this->assertEquals('business', $customer->customer_type);
        $this->assertEquals('Doe Industries', $customer->company_name);
        $this->assertEquals('DE123456789', $customer->tax_number);
    }

    public function test_customer_has_fillable_attributes()
    {
        $fillable = [
            'name',
            'email',
            'phone',
            'customer_type',
            'company_name',
            'tax_number',
            'street',
            'city',
            'postal_code',
            'country',
            'status',
            'notes',
        ];

        $customer = new Customer();
        
        $this->assertEquals($fillable, $customer->getFillable());
    }

    public function test_customer_has_tasks_relationship()
    {
        $customer = Customer::factory()->create();
        $task = Task::factory()->create(['customer_id' => $customer->id]);

        $this->assertTrue($customer->tasks()->exists());
        $this->assertInstanceOf(Task::class, $customer->tasks()->first());
        $this->assertEquals($task->id, $customer->tasks()->first()->id);
    }

    public function test_customer_has_projects_relationship()
    {
        $customer = Customer::factory()->create();
        $project = Project::factory()->create(['customer_id' => $customer->id]);

        $this->assertTrue($customer->projects()->exists());
        $this->assertInstanceOf(Project::class, $customer->projects()->first());
        $this->assertEquals($project->id, $customer->projects()->first()->id);
    }

    public function test_customer_has_participations_relationship()
    {
        $customer = Customer::factory()->create();
        $solarPlant = SolarPlant::factory()->create();
        
        $participation = PlantParticipation::create([
            'customer_id' => $customer->id,
            'solar_plant_id' => $solarPlant->id,
            'percentage' => 25.0,
            'participation_kwp' => 5.0,
        ]);

        $this->assertTrue($customer->participations()->exists());
        $this->assertInstanceOf(PlantParticipation::class, $customer->participations()->first());
        $this->assertEquals($participation->id, $customer->participations()->first()->id);
    }

    public function test_customer_display_name_returns_company_name_for_business()
    {
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'customer_type' => 'business',
            'company_name' => 'Doe Industries',
        ]);

        $this->assertEquals('Doe Industries', $customer->display_name);
    }

    public function test_customer_display_name_returns_name_for_private()
    {
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'customer_type' => 'private',
            'company_name' => null,
        ]);

        $this->assertEquals('John Doe', $customer->display_name);
    }

    public function test_customer_display_name_fallback_to_name_when_no_company_name()
    {
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'customer_type' => 'business',
            'company_name' => null,
        ]);

        $this->assertEquals('John Doe', $customer->display_name);
    }

    public function test_customer_is_active_scope()
    {
        Customer::factory()->create(['status' => 'active']);
        Customer::factory()->create(['status' => 'inactive']);
        Customer::factory()->create(['status' => 'suspended']);

        $activeCustomers = Customer::active()->get();

        $this->assertCount(1, $activeCustomers);
        $this->assertEquals('active', $activeCustomers->first()->status);
    }

    public function test_customer_by_type_scope()
    {
        Customer::factory()->create(['customer_type' => 'private']);
        Customer::factory()->create(['customer_type' => 'business']);
        Customer::factory()->create(['customer_type' => 'business']);

        $businessCustomers = Customer::byType('business')->get();
        $privateCustomers = Customer::byType('private')->get();

        $this->assertCount(2, $businessCustomers);
        $this->assertCount(1, $privateCustomers);
    }

    public function test_customer_search_scope()
    {
        Customer::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Customer::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        Customer::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@test.com']);

        $searchResults = Customer::search('john')->get();

        $this->assertCount(2, $searchResults); // John Doe and Bob Johnson
    }

    public function test_customer_can_be_soft_deleted()
    {
        $customer = Customer::factory()->create();
        $customerId = $customer->id;

        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $customerId]);
        
        // Should not be found in regular queries
        $this->assertNull(Customer::find($customerId));
        
        // Should be found when including trashed
        $this->assertNotNull(Customer::withTrashed()->find($customerId));
    }

    public function test_customer_total_participations_attribute()
    {
        $customer = Customer::factory()->create();
        $solarPlant1 = SolarPlant::factory()->create();
        $solarPlant2 = SolarPlant::factory()->create();
        
        PlantParticipation::create([
            'customer_id' => $customer->id,
            'solar_plant_id' => $solarPlant1->id,
            'percentage' => 25.0,
            'participation_kwp' => 5.0,
        ]);
        
        PlantParticipation::create([
            'customer_id' => $customer->id,
            'solar_plant_id' => $solarPlant2->id,
            'percentage' => 30.0,
            'participation_kwp' => 7.5,
        ]);

        $customer = $customer->fresh(); // Reload from database
        
        $this->assertEquals(12.5, $customer->total_participation_kwp);
        $this->assertEquals(2, $customer->participation_count);
    }

    public function test_customer_validation_rules()
    {
        // Test that required fields are enforced
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Customer::create([
            // Missing required name and email
            'customer_type' => 'private',
        ]);
    }

    public function test_customer_casts_attributes_correctly()
    {
        $customer = Customer::factory()->create([
            'status' => 'active',
            'customer_type' => 'private',
        ]);

        $this->assertIsString($customer->status);
        $this->assertIsString($customer->customer_type);
    }
}