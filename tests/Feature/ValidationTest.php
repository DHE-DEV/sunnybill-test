<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\InteractsWithApi;
use Tests\Traits\CreatesTestData;
use App\Models\Customer;
use App\Models\Task;
use App\Models\Project;

class ValidationTest extends TestCase
{
    use InteractsWithApi, CreatesTestData;

    public function test_task_creation_validation()
    {
        // Test missing required fields
        $response = $this->apiPost('/tasks', [], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'status', 'priority']);
    }

    public function test_task_title_validation()
    {
        // Test title too short
        $response = $this->apiPost('/tasks', [
            'title' => 'A',
            'status' => 'open',
            'priority' => 'medium',
        ], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);

        // Test title too long
        $response = $this->apiPost('/tasks', [
            'title' => str_repeat('A', 256),
            'status' => 'open',
            'priority' => 'medium',
        ], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_task_status_validation()
    {
        $response = $this->apiPost('/tasks', [
            'title' => 'Valid Title',
            'status' => 'invalid_status',
            'priority' => 'medium',
        ], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_task_priority_validation()
    {
        $response = $this->apiPost('/tasks', [
            'title' => 'Valid Title',
            'status' => 'open',
            'priority' => 'invalid_priority',
        ], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_task_due_date_validation()
    {
        // Test invalid date format
        $response = $this->apiPost('/tasks', [
            'title' => 'Valid Title',
            'status' => 'open',
            'priority' => 'medium',
            'due_date' => 'invalid-date',
        ], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);

        // Test past date (if business rule requires future dates)
        $response = $this->apiPost('/tasks', [
            'title' => 'Valid Title',
            'status' => 'open',
            'priority' => 'medium',
            'due_date' => '2020-01-01',
        ], ['tasks:create']);

        // This might pass depending on business rules
    }

    public function test_customer_creation_validation()
    {
        // Test missing required fields
        $response = $this->apiPost('/customers', [], ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'customer_type']);
    }

    public function test_customer_email_validation()
    {
        // Test invalid email format
        $response = $this->apiPost('/customers', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'customer_type' => 'private',
        ], ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Test unique email constraint
        $existingCustomer = $this->createCustomer(['email' => 'test@example.com']);

        $response = $this->apiPost('/customers', [
            'name' => 'Another Customer',
            'email' => 'test@example.com',
            'customer_type' => 'private',
        ], ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_customer_type_validation()
    {
        $response = $this->apiPost('/customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'customer_type' => 'invalid_type',
        ], ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_type']);
    }

    public function test_business_customer_validation()
    {
        // Business customers should require company_name
        $response = $this->apiPost('/customers', [
            'name' => 'John Doe',
            'email' => 'john@business.com',
            'customer_type' => 'business',
            // Missing company_name
        ], ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name']);
    }

    public function test_project_creation_validation()
    {
        // Test missing required fields
        $response = $this->apiPost('/projects', [], ['projects:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status', 'customer_id']);
    }

    public function test_project_dates_validation()
    {
        $customer = $this->createCustomer();

        // Test end_date before start_date
        $response = $this->apiPost('/projects', [
            'name' => 'Test Project',
            'status' => 'planning',
            'customer_id' => $customer->id,
            'start_date' => '2024-12-31',
            'end_date' => '2024-01-01',
        ], ['projects:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_project_budget_validation()
    {
        $customer = $this->createCustomer();

        // Test negative budget
        $response = $this->apiPost('/projects', [
            'name' => 'Test Project',
            'status' => 'planning',
            'customer_id' => $customer->id,
            'budget' => -1000,
        ], ['projects:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);
    }

    public function test_project_progress_validation()
    {
        $project = $this->createProject();

        // Test progress > 100
        $response = $this->apiPatch("/projects/{$project->id}/progress", [
            'progress_percentage' => 150,
        ], ['projects:update']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['progress_percentage']);

        // Test negative progress
        $response = $this->apiPatch("/projects/{$project->id}/progress", [
            'progress_percentage' => -10,
        ], ['projects:update']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['progress_percentage']);
    }

    public function test_solar_plant_creation_validation()
    {
        // Test missing required fields
        $response = $this->apiPost('/solar-plants', [], ['solar-plants:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plant_number', 'name', 'location', 'total_capacity_kw']);
    }

    public function test_solar_plant_capacity_validation()
    {
        // Test negative capacity
        $response = $this->apiPost('/solar-plants', [
            'plant_number' => 'PVA-001',
            'name' => 'Test Plant',
            'location' => 'Test Location',
            'total_capacity_kw' => -50,
        ], ['solar-plants:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['total_capacity_kw']);

        // Test zero capacity
        $response = $this->apiPost('/solar-plants', [
            'plant_number' => 'PVA-002',
            'name' => 'Test Plant',
            'location' => 'Test Location',
            'total_capacity_kw' => 0,
        ], ['solar-plants:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['total_capacity_kw']);
    }

    public function test_solar_plant_unique_plant_number()
    {
        $existingPlant = $this->createSolarPlant(['plant_number' => 'PVA-001']);

        $response = $this->apiPost('/solar-plants', [
            'plant_number' => 'PVA-001', // Duplicate
            'name' => 'Another Plant',
            'location' => 'Another Location',
            'total_capacity_kw' => 50,
        ], ['solar-plants:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['plant_number']);
    }

    public function test_milestone_creation_validation()
    {
        $project = $this->createProject();

        // Test missing required fields
        $response = $this->apiPost("/projects/{$project->id}/milestones", [], ['milestones:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'due_date']);
    }

    public function test_milestone_progress_validation()
    {
        $project = $this->createProject();
        $milestone = $this->createProjectMilestone($project);

        // Test progress > 100
        $response = $this->apiPatch("/project-milestones/{$milestone->id}/progress", [
            'progress_percentage' => 150,
        ], ['milestones:update']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['progress_percentage']);
    }

    public function test_appointment_creation_validation()
    {
        $project = $this->createProject();

        // Test missing required fields
        $response = $this->apiPost("/projects/{$project->id}/appointments", [], ['appointments:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'start_date', 'end_date']);
    }

    public function test_appointment_dates_validation()
    {
        $project = $this->createProject();

        // Test end_date before start_date
        $response = $this->apiPost("/projects/{$project->id}/appointments", [
            'title' => 'Test Appointment',
            'start_date' => '2024-12-31 14:00:00',
            'end_date' => '2024-12-31 10:00:00',
        ], ['appointments:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_appointment_past_date_validation()
    {
        $project = $this->createProject();

        // Test appointment in the past
        $response = $this->apiPost("/projects/{$project->id}/appointments", [
            'title' => 'Test Appointment',
            'start_date' => '2020-01-01 14:00:00',
            'end_date' => '2020-01-01 16:00:00',
        ], ['appointments:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_validation_error_response_structure()
    {
        $response = $this->apiPost('/tasks', [], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'title',
                    'status',
                    'priority',
                ]
            ]);

        $responseData = $response->json();
        $this->assertIsString($responseData['message']);
        $this->assertIsArray($responseData['errors']);
        $this->assertIsArray($responseData['errors']['title']);
        $this->assertIsArray($responseData['errors']['status']);
        $this->assertIsArray($responseData['errors']['priority']);
    }

    public function test_numeric_validation()
    {
        // Test non-numeric values for numeric fields
        $response = $this->apiPost('/solar-plants', [
            'plant_number' => 'PVA-001',
            'name' => 'Test Plant',
            'location' => 'Test Location',
            'total_capacity_kw' => 'not-a-number',
        ], ['solar-plants:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['total_capacity_kw']);
    }

    public function test_string_length_validation()
    {
        $customer = $this->createCustomer();

        // Test string too long
        $response = $this->apiPost('/projects', [
            'name' => str_repeat('A', 256), // Assuming max 255 characters
            'status' => 'planning',
            'customer_id' => $customer->id,
        ], ['projects:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}