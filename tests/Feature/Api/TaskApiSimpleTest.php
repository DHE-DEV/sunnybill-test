<?php

use Tests\Traits\InteractsWithApi;
use Tests\Traits\CreatesTestData;
use App\Models\Task;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, InteractsWithApi::class, CreatesTestData::class);

// Basic Token Tests
describe('Token Authentication Tests', function () {
    
    test('can create admin token for task creation', function () {
        $adminToken = $this->createAdminToken();
        
        expect($adminToken)->toHaveKeys(['user', 'token', 'app_token']);
        expect($adminToken['app_token']->abilities)->toContain('tasks:create');
        expect($adminToken['app_token']->abilities)->toContain('tasks:read');
        expect($adminToken['app_token']->abilities)->toContain('tasks:update');
        expect($adminToken['app_token']->abilities)->toContain('tasks:delete');
    });

    test('can create editor token with limited permissions', function () {
        // Sicherstellen, dass User 57 existiert
        User::factory()->create(['id' => 57]);
        
        $editorToken = $this->createEditorToken(['tasks:read', 'tasks:update']);
        
        expect($editorToken)->toHaveKeys(['user', 'token', 'app_token']);
        expect($editorToken['user']->id)->toBe(57);
        expect($editorToken['app_token']->abilities)->toContain('tasks:read');
        expect($editorToken['app_token']->abilities)->toContain('tasks:update');
        expect($editorToken['app_token']->abilities)->not->toContain('tasks:create');
        expect($editorToken['app_token']->abilities)->not->toContain('tasks:delete');
    });

});

// Admin Task Creation Tests
describe('Admin Task Creation', function () {
    
    test('admin can create basic task', function () {
        $adminToken = $this->createAdminToken();
        $customer = $this->createCustomer();

        $taskData = [
            'title' => 'Admin Created Task',
            'description' => 'Task created by admin for testing',
            'status' => 'open',
            'priority' => 'high',
            'customer_id' => $customer->id,
            'due_date' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->postJson(
            '/api/app/tasks',
            $taskData,
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'customer_id',
                    'due_date',
                    'created_by',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Admin Created Task',
            'description' => 'Task created by admin for testing',
            'status' => 'open',
            'priority' => 'high',
            'customer_id' => $customer->id,
            'created_by' => $adminToken['user']->id,
        ]);
    });

    test('admin can create task and assign to user 57', function () {
        $adminToken = $this->createAdminToken();
        $customer = $this->createCustomer();
        $assignee = User::factory()->create(['id' => 57]);

        $taskData = [
            'title' => 'Task for Editor',
            'description' => 'Task assigned to user 57',
            'status' => 'open',
            'priority' => 'medium',
            'assigned_to' => $assignee->id,
            'customer_id' => $customer->id,
            'due_date' => now()->addDays(5)->toDateString(),
        ];

        $response = $this->postJson(
            '/api/app/tasks',
            $taskData,
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Task for Editor',
            'assigned_to' => 57,
            'customer_id' => $customer->id,
            'created_by' => $adminToken['user']->id,
        ]);
    });

});

// Editor Task Management Tests (User ID 57)
describe('Editor Task Management (User ID 57)', function () {
    
    test('editor can read tasks', function () {
        User::factory()->create(['id' => 57]);
        $editorToken = $this->createEditorToken(['tasks:read']);
        
        // Create some test tasks
        $this->createMultiple('task', 3);

        $response = $this->getJson(
            '/api/app/tasks',
            $this->appTokenHeaders($editorToken['token'])
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'priority',
                        'due_date'
                    ]
                ]
            ]);
    });

    test('editor can update assigned task', function () {
        User::factory()->create(['id' => 57]);
        $editorToken = $this->createEditorToken(['tasks:update']);
        $task = $this->createTask(['assigned_to' => 57]);
        
        $updateData = [
            'title' => 'Updated by Editor',
            'description' => 'Task updated by editor user',
            'status' => 'in_progress',
            'priority' => 'high',
        ];

        $response = $this->putJson(
            "/api/app/tasks/{$task->id}",
            $updateData,
            $this->appTokenHeaders($editorToken['token'])
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => 'Updated by Editor',
                    'status' => 'in_progress',
                    'priority' => 'high',
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated by Editor',
            'status' => 'in_progress',
        ]);
    });

    test('editor cannot create tasks without permission', function () {
        User::factory()->create(['id' => 57]);
        $editorToken = $this->createEditorToken(['tasks:read', 'tasks:update']); // No create permission
        $customer = $this->createCustomer();

        $taskData = [
            'title' => 'Unauthorized Task Creation',
            'description' => 'Editor trying to create task',
            'status' => 'open',
            'priority' => 'medium',
            'customer_id' => $customer->id,
        ];

        $response = $this->postJson(
            '/api/app/tasks',
            $taskData,
            $this->appTokenHeaders($editorToken['token'])
        );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('tasks', [
            'title' => 'Unauthorized Task Creation',
        ]);
    });

    test('editor cannot delete tasks without permission', function () {
        User::factory()->create(['id' => 57]);
        $editorToken = $this->createEditorToken(['tasks:read', 'tasks:update']); // No delete permission
        $task = $this->createTask(['assigned_to' => 57]);

        $response = $this->deleteJson(
            "/api/app/tasks/{$task->id}",
            [],
            $this->appTokenHeaders($editorToken['token'])
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);
    });

});

// Task Filtering Tests
describe('Task Filtering', function () {
    
    test('can filter tasks by status', function () {
        $adminToken = $this->createAdminToken();
        
        $this->createTask(['status' => 'open']);
        $this->createTask(['status' => 'open']);
        $this->createTask(['status' => 'completed']);

        $response = $this->getJson(
            '/api/app/tasks?status=open',
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        expect(count($tasks))->toBeGreaterThanOrEqual(2);
        
        foreach ($tasks as $task) {
            expect($task['status'])->toBe('open');
        }
    });

    test('can filter tasks by priority', function () {
        $adminToken = $this->createAdminToken();
        
        $this->createTask(['priority' => 'high']);
        $this->createTask(['priority' => 'high']);
        $this->createTask(['priority' => 'low']);

        $response = $this->getJson(
            '/api/app/tasks?priority=high',
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        expect(count($tasks))->toBeGreaterThanOrEqual(2);
        
        foreach ($tasks as $task) {
            expect($task['priority'])->toBe('high');
        }
    });

});

// Validation Tests
describe('Task Validation', function () {
    
    test('validation fails with missing required fields', function () {
        $adminToken = $this->createAdminToken();

        $response = $this->postJson(
            '/api/app/tasks',
            [],
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    test('validation fails with invalid status', function () {
        $adminToken = $this->createAdminToken();
        $customer = $this->createCustomer();

        $taskData = [
            'title' => 'Test Task',
            'status' => 'invalid_status',
            'priority' => 'medium',
            'customer_id' => $customer->id,
        ];

        $response = $this->postJson(
            '/api/app/tasks',
            $taskData,
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(422);
    });

});

// Authorization Tests
describe('Task Authorization', function () {
    
    test('unauthorized access returns 401', function () {
        $response = $this->getJson('/api/app/tasks');
        $response->assertStatus(401);
        
        $response = $this->postJson('/api/app/tasks', ['title' => 'Test']);
        $response->assertStatus(401);
    });

    test('wrong permissions return 403', function () {
        $tokenWithoutPermissions = $this->createUserWithAppToken(['wrong:permission']);

        $response = $this->getJson(
            '/api/app/tasks',
            $this->appTokenHeaders($tokenWithoutPermissions['token'])
        );
        
        $response->assertStatus(403);
    });

});
