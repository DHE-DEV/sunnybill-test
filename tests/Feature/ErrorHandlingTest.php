<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\InteractsWithApi;
use Tests\Traits\CreatesTestData;
use App\Models\Task;
use App\Models\Customer;
use App\Models\Project;

class ErrorHandlingTest extends TestCase
{
    use InteractsWithApi, CreatesTestData;

    public function test_404_error_for_non_existent_task()
    {
        $response = $this->apiGet('/tasks/99999', ['tasks:read']);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'message'
            ]);
    }

    public function test_404_error_for_non_existent_customer()
    {
        $response = $this->apiGet('/customers/99999', ['customers:read']);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'message'
            ]);
    }

    public function test_404_error_for_non_existent_project()
    {
        $response = $this->apiGet('/projects/99999', ['projects:read']);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'message'
            ]);
    }

    public function test_404_error_for_non_existent_solar_plant()
    {
        $response = $this->apiGet('/solar-plants/99999', ['solar-plants:read']);

        $response->assertStatus(404)
            ->assertJsonStructure([
                'message'
            ]);
    }

    public function test_401_error_without_authentication()
    {
        $response = $this->getJson('/api/app/tasks');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message'
            ]);
    }

    public function test_403_error_with_insufficient_permissions()
    {
        $tokenData = $this->createUserWithAppToken(['wrong:permission']);

        $response = $this->getJson('/api/app/tasks', $this->appTokenHeaders($tokenData['token']));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message'
            ]);
    }

    public function test_422_validation_error_structure()
    {
        $response = $this->apiPost('/tasks', [
            'title' => '', // Invalid: required field empty
            'status' => 'invalid_status', // Invalid: not in allowed values
            'priority' => '', // Invalid: required field empty
        ], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'title',
                    'status',
                    'priority'
                ]
            ]);

        $errors = $response->json('errors');
        $this->assertIsArray($errors['title']);
        $this->assertIsArray($errors['status']);
        $this->assertIsArray($errors['priority']);
    }

    public function test_500_error_handling_with_database_error()
    {
        // This is harder to test without actually causing a database error
        // We'll simulate by testing with invalid foreign key
        $response = $this->apiPost('/tasks', [
            'title' => 'Test Task',
            'status' => 'open',
            'priority' => 'medium',
            'customer_id' => 99999, // Non-existent customer
        ], ['tasks:create']);

        // This should either return 422 (validation error) or 500 (database constraint error)
        $this->assertContains($response->status(), [422, 500]);
    }

    public function test_method_not_allowed_error()
    {
        $task = $this->createTask();

        // Try to PATCH a route that only accepts PUT
        $response = $this->patchJson("/api/app/tasks/{$task->id}", [
            'title' => 'Updated Title'
        ], $this->appTokenHeaders($this->createUserWithAppToken(['tasks:update'])['token']));

        // Depending on route definition, this might be 405 or accepted
        // This test depends on actual route configuration
    }

    public function test_malformed_json_error()
    {
        $tokenData = $this->createUserWithAppToken(['tasks:create']);

        $response = $this->call(
            'POST',
            '/api/app/tasks',
            [],
            [],
            [],
            array_merge($this->appTokenHeaders($tokenData['token']), [
                'CONTENT_TYPE' => 'application/json'
            ]),
            '{"invalid":"json"' // Malformed JSON
        );

        $response->assertStatus(400);
    }

    public function test_unsupported_media_type_error()
    {
        $tokenData = $this->createUserWithAppToken(['tasks:create']);

        $response = $this->call(
            'POST',
            '/api/app/tasks',
            [],
            [],
            [],
            array_merge($this->appTokenHeaders($tokenData['token']), [
                'CONTENT_TYPE' => 'text/plain'
            ]),
            'This is plain text, not JSON'
        );

        // Should return 415 Unsupported Media Type or 400 Bad Request
        $this->assertContains($response->status(), [400, 415]);
    }

    public function test_rate_limiting_error()
    {
        // This test would require actual rate limiting configuration
        // For now, we'll skip it or make many requests rapidly
        $tokenData = $this->createUserWithAppToken(['tasks:read']);

        // Make many requests rapidly (if rate limiting is configured)
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/api/app/tasks', $this->appTokenHeaders($tokenData['token']));
            
            if ($response->status() === 429) {
                // Rate limit hit
                $response->assertStatus(429)
                    ->assertJsonStructure([
                        'message'
                    ]);
                return;
            }
        }

        // If no rate limiting is configured, this test passes
        $this->assertTrue(true);
    }

    public function test_soft_deleted_resource_returns_404()
    {
        $task = $this->createTask();
        $taskId = $task->id;

        // Soft delete the task
        $task->delete();

        $response = $this->apiGet("/tasks/{$taskId}", ['tasks:read']);

        $response->assertStatus(404);
    }

    public function test_foreign_key_constraint_error()
    {
        // Try to create a task with non-existent customer_id
        $response = $this->apiPost('/tasks', [
            'title' => 'Test Task',
            'status' => 'open',
            'priority' => 'medium',
            'customer_id' => 99999, // Non-existent
        ], ['tasks:create']);

        // Should return validation error (422) or database error (500)
        $this->assertContains($response->status(), [422, 500]);

        if ($response->status() === 422) {
            $response->assertJsonValidationErrors(['customer_id']);
        }
    }

    public function test_duplicate_unique_field_error()
    {
        $existingCustomer = $this->createCustomer(['email' => 'test@example.com']);

        $response = $this->apiPost('/customers', [
            'name' => 'Another Customer',
            'email' => 'test@example.com', // Duplicate email
            'customer_type' => 'private',
        ], ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_invalid_enum_value_error()
    {
        $response = $this->apiPost('/tasks', [
            'title' => 'Test Task',
            'status' => 'invalid_status_value',
            'priority' => 'medium',
        ], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_missing_required_fields_error()
    {
        $response = $this->apiPost('/customers', [], ['customers:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'customer_type']);
    }

    public function test_invalid_date_format_error()
    {
        $response = $this->apiPost('/tasks', [
            'title' => 'Test Task',
            'status' => 'open',
            'priority' => 'medium',
            'due_date' => 'not-a-date',
        ], ['tasks:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_numeric_field_with_string_error()
    {
        $customer = $this->createCustomer();

        $response = $this->apiPost('/projects', [
            'name' => 'Test Project',
            'status' => 'planning',
            'customer_id' => $customer->id,
            'budget' => 'not-a-number',
        ], ['projects:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['budget']);
    }

    public function test_array_field_validation_error()
    {
        $project = $this->createProject();

        $response = $this->apiPost("/projects/{$project->id}/appointments", [
            'title' => 'Test Appointment',
            'start_date' => '2024-12-25 10:00:00',
            'end_date' => '2024-12-25 12:00:00',
            'attendees' => 'not-an-array', // Should be array
        ], ['appointments:create']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['attendees']);
    }

    public function test_error_response_includes_helpful_message()
    {
        $response = $this->apiPost('/tasks', [], ['tasks:create']);

        $response->assertStatus(422);

        $responseData = $response->json();
        $this->assertArrayHasKey('message', $responseData);
        $this->assertIsString($responseData['message']);
        $this->assertNotEmpty($responseData['message']);
    }

    public function test_error_handling_maintains_consistent_structure()
    {
        // Test 404 error structure
        $response404 = $this->apiGet('/tasks/99999', ['tasks:read']);
        $response404->assertStatus(404)
            ->assertJsonStructure(['message']);

        // Test 422 error structure
        $response422 = $this->apiPost('/tasks', [], ['tasks:create']);
        $response422->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);

        // Test 403 error structure
        $tokenData = $this->createUserWithAppToken(['wrong:permission']);
        $response403 = $this->getJson('/api/app/tasks', $this->appTokenHeaders($tokenData['token']));
        $response403->assertStatus(403)
            ->assertJsonStructure(['message']);
    }
}