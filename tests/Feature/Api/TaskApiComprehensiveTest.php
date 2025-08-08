<?php

use Tests\Traits\InteractsWithApi;
use Tests\Traits\CreatesTestData;
use App\Models\Task;
use App\Models\User;
use App\Models\Customer;
use App\Models\SolarPlant;
use App\Models\Supplier;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, InteractsWithApi::class, CreatesTestData::class);

// Admin Tests (Task Creation)
describe('Admin Task Management', function () {
    
    test('admin can create task', function () {
        $adminToken = $this->createAdminToken();
        $customer = $this->createCustomer();
        $solarPlant = $this->createSolarPlant(['customer_id' => $customer->id]);
        $assignee = User::find(57) ?: User::factory()->create(['id' => 57]);

        $taskData = [
            'title' => 'Admin Created Task',
            'description' => 'Task created by admin for testing',
            'status' => 'open',
            'priority' => 'high',
            'assigned_to' => $assignee->id,
            'customer_id' => $customer->id,
            'solar_plant_id' => $solarPlant->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'category' => 'installation',
            'estimated_hours' => 5.5,
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
                    'assigned_to',
                    'customer_id',
                    'solar_plant_id',
                    'due_date',
                    'category',
                    'estimated_hours',
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
            'assigned_to' => $assignee->id,
            'customer_id' => $customer->id,
            'solar_plant_id' => $solarPlant->id,
            'category' => 'installation',
            'estimated_hours' => 5.5,
            'created_by' => $adminToken['user']->id,
        ]);
    });

    test('admin can create task with project', function () {
        $adminToken = $this->createAdminToken();
        $customer = $this->createCustomer();
        $project = $this->createProject(['customer_id' => $customer->id]);

        $taskData = [
            'title' => 'Project Task',
            'description' => 'Task linked to project',
            'status' => 'open',
            'priority' => 'medium',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'due_date' => now()->addDays(14)->toDateString(),
        ];

        $response = $this->postJson(
            '/api/app/tasks',
            $taskData,
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Project Task',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
        ]);
    });

    test('admin can create recurring task', function () {
        $adminToken = $this->createAdminToken();
        $customer = $this->createCustomer();

        $taskData = [
            'title' => 'Recurring Maintenance Task',
            'description' => 'Monthly maintenance check',
            'status' => 'open',
            'priority' => 'low',
            'customer_id' => $customer->id,
            'is_recurring' => true,
            'recurrence_pattern' => 'monthly',
            'recurrence_interval' => 1,
            'due_date' => now()->addMonth()->toDateString(),
        ];

        $response = $this->postJson(
            '/api/app/tasks',
            $taskData,
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Recurring Maintenance Task',
            'is_recurring' => true,
            'recurrence_pattern' => 'monthly',
            'recurrence_interval' => 1,
        ]);
    });

    test('admin can bulk create tasks', function () {
        $adminToken = $this->createAdminToken();
        $customer = $this->createCustomer();
        
        $bulkTasks = [
            [
                'title' => 'Task 1',
                'description' => 'First task',
                'status' => 'open',
                'priority' => 'high',
                'customer_id' => $customer->id,
                'due_date' => now()->addDays(3)->toDateString(),
            ],
            [
                'title' => 'Task 2',
                'description' => 'Second task',
                'status' => 'open',
                'priority' => 'medium',
                'customer_id' => $customer->id,
                'due_date' => now()->addDays(5)->toDateString(),
            ],
            [
                'title' => 'Task 3',
                'description' => 'Third task',
                'status' => 'open',
                'priority' => 'low',
                'customer_id' => $customer->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ]
        ];

        $response = $this->postJson(
            '/api/app/tasks/bulk',
            ['tasks' => $bulkTasks],
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'created' => [
                        '*' => ['id', 'title', 'status']
                    ],
                    'summary' => [
                        'total_created',
                        'failed'
                    ]
                ]
            ]);

        foreach ($bulkTasks as $taskData) {
            $this->assertDatabaseHas('tasks', [
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'customer_id' => $customer->id,
            ]);
        }
    });

});

// Editor Tests (User ID 57)
describe('Editor Task Management (User ID 57)', function () {
    
    test('editor can read tasks', function () {
        $editorToken = $this->createEditorToken(['tasks:read']);
        $this->createMultiple('task', 5);

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
                        'assigned_to',
                        'due_date',
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
    });

    test('editor can update assigned task', function () {
        $editorToken = $this->createEditorToken(['tasks:update']);
        $task = $this->createTask(['assigned_to' => 57]); // Assigned to editor
        
        $updateData = [
            'title' => 'Updated by Editor',
            'description' => 'Task updated by editor user',
            'status' => 'in_progress',
            'priority' => 'high',
            'progress' => 50,
            'notes' => 'Progress update from editor',
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
                    'progress' => 50,
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated by Editor',
            'status' => 'in_progress',
            'progress' => 50,
        ]);
    });

    test('editor can update task status', function () {
        $editorToken = $this->createEditorToken(['tasks:status']);
        $task = $this->createTask(['assigned_to' => 57, 'status' => 'open']);

        $response = $this->patchJson(
            "/api/app/tasks/{$task->id}/status",
            ['status' => 'completed'],
            $this->appTokenHeaders($editorToken['token'])
        );

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'status' => 'completed',
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    });

    test('editor can add task notes', function () {
        $editorToken = $this->createEditorToken(['tasks:notes']);
        $task = $this->createTask(['assigned_to' => 57]);

        $noteData = [
            'note' => 'Progress update: 50% complete. Waiting for customer confirmation.',
            'type' => 'progress',
            'visibility' => 'internal',
        ];

        $response = $this->postJson(
            "/api/app/tasks/{$task->id}/notes",
            $noteData,
            $this->appTokenHeaders($editorToken['token'])
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id',
                    'note',
                    'type',
                    'visibility',
                    'created_by',
                    'created_at'
                ]
            ]);
    });

    test('editor cannot create tasks', function () {
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

    test('editor cannot delete tasks', function () {
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

// Task Filtering and Search Tests
describe('Task Filtering and Search', function () {
    
    test('can filter tasks by status', function () {
        $adminToken = $this->createAdminToken();
        
        $this->createTask(['status' => 'open']);
        $this->createTask(['status' => 'open']);
        $this->createTask(['status' => 'in_progress']);
        $this->createTask(['status' => 'completed']);

        $response = $this->getJson(
            '/api/app/tasks?status=open',
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        expect($tasks)->toHaveCount(2);
        
        foreach ($tasks as $task) {
            expect($task['status'])->toBe('open');
        }
    });

    test('can filter tasks by priority', function () {
        $adminToken = $this->createAdminToken();
        
        $this->createTask(['priority' => 'high']);
        $this->createTask(['priority' => 'high']);
        $this->createTask(['priority' => 'medium']);
        $this->createTask(['priority' => 'low']);

        $response = $this->getJson(
            '/api/app/tasks?priority=high',
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        expect($tasks)->toHaveCount(2);
        
        foreach ($tasks as $task) {
            expect($task['priority'])->toBe('high');
        }
    });

    test('can filter tasks by assigned user', function () {
        $adminToken = $this->createAdminToken();
        $user57 = User::find(57) ?: User::factory()->create(['id' => 57]);
        
        $this->createTask(['assigned_to' => $user57->id]);
        $this->createTask(['assigned_to' => $user57->id]);
        $this->createTask(['assigned_to' => $adminToken['user']->id]);

        $response = $this->getJson(
            "/api/app/tasks?assigned_to={$user57->id}",
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        expect($tasks)->toHaveCount(2);
        
        foreach ($tasks as $task) {
            expect($task['assigned_to'])->toBe($user57->id);
        }
    });

    test('can search tasks by title', function () {
        $adminToken = $this->createAdminToken();
        
        $this->createTask(['title' => 'Install solar panels']);
        $this->createTask(['title' => 'Maintenance check']);
        $this->createTask(['title' => 'Solar inverter replacement']);

        $response = $this->getJson(
            '/api/app/tasks?search=solar',
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        expect($tasks)->toHaveCount(2);
    });

    test('can filter tasks by due date range', function () {
        $adminToken = $this->createAdminToken();
        
        $this->createTask(['due_date' => now()->addDays(1)]);
        $this->createTask(['due_date' => now()->addDays(5)]);
        $this->createTask(['due_date' => now()->addDays(10)]);

        $response = $this->getJson(
            '/api/app/tasks?due_from=' . now()->addDays(1)->toDateString() . '&due_to=' . now()->addDays(7)->toDateString(),
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(200);
        
        $tasks = $response->json('data');
        expect($tasks)->toHaveCount(2);
    });

});

// Task Dependencies Tests
describe('Task Dependencies', function () {
    
    test('admin can create task with dependencies', function () {
        $adminToken = $this->createAdminToken();
        $customer = $this->createCustomer();
        
        $parentTask = $this->createTask(['customer_id' => $customer->id]);
        $dependsOnTask = $this->createTask(['customer_id' => $customer->id]);

        $taskData = [
            'title' => 'Dependent Task',
            'description' => 'Task with dependencies',
            'status' => 'open',
            'priority' => 'medium',
            'customer_id' => $customer->id,
            'parent_id' => $parentTask->id,
            'depends_on' => [$dependsOnTask->id],
        ];

        $response = $this->postJson(
            '/api/app/tasks',
            $taskData,
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Dependent Task',
            'parent_id' => $parentTask->id,
        ]);
    });

    test('can get task hierarchy', function () {
        $adminToken = $this->createAdminToken();
        $parentTask = $this->createTask();
        $subtask1 = $this->createTask(['parent_id' => $parentTask->id]);
        $subtask2 = $this->createTask(['parent_id' => $parentTask->id]);

        $response = $this->getJson(
            "/api/app/tasks/{$parentTask->id}/hierarchy",
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'parent' => ['id', 'title'],
                    'subtasks' => [
                        '*' => ['id', 'title', 'status']
                    ]
                ]
            ]);

        $data = $response->json('data');
        expect($data['subtasks'])->toHaveCount(2);
    });

});

// Task Time Tracking Tests
describe('Task Time Tracking', function () {
    
    test('editor can start time tracking', function () {
        $editorToken = $this->createEditorToken(['tasks:time']);
        $task = $this->createTask(['assigned_to' => 57]);

        $response = $this->postJson(
            "/api/app/tasks/{$task->id}/time/start",
            ['description' => 'Starting work on task'],
            $this->appTokenHeaders($editorToken['token'])
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id',
                    'user_id',
                    'started_at',
                    'description',
                    'is_active'
                ]
            ]);
    });

    test('editor can stop time tracking', function () {
        $editorToken = $this->createEditorToken(['tasks:time']);
        $task = $this->createTask(['assigned_to' => 57]);

        // Start time tracking first
        $startResponse = $this->postJson(
            "/api/app/tasks/{$task->id}/time/start",
            ['description' => 'Starting work'],
            $this->appTokenHeaders($editorToken['token'])
        );

        $timeEntryId = $startResponse->json('data.id');

        // Stop time tracking
        $response = $this->postJson(
            "/api/app/tasks/{$task->id}/time/stop",
            ['time_entry_id' => $timeEntryId],
            $this->appTokenHeaders($editorToken['token'])
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'task_id',
                    'user_id',
                    'started_at',
                    'ended_at',
                    'duration',
                    'is_active'
                ]
            ]);

        expect($response->json('data.is_active'))->toBeFalse();
    });

});

// Task Validation Tests
describe('Task Validation', function () {
    
    test('validation fails with missing required fields', function () {
        $adminToken = $this->createAdminToken();

        $response = $this->postJson(
            '/api/app/tasks',
            [],
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'status', 'priority']);
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

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });

    test('validation fails with invalid priority', function () {
        $adminToken = $this->createAdminToken();
        $customer = $this->createCustomer();

        $taskData = [
            'title' => 'Test Task',
            'status' => 'open',
            'priority' => 'invalid_priority',
            'customer_id' => $customer->id,
        ];

        $response = $this->postJson(
            '/api/app/tasks',
            $taskData,
            $this->appTokenHeaders($adminToken['token'])
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
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

    test('task not found returns 404', function () {
        $adminToken = $this->createAdminToken();
        
        $response = $this->getJson(
            '/api/app/tasks/99999',
            $this->appTokenHeaders($adminToken['token'])
        );
        
        $response->assertStatus(404);
    });

});
