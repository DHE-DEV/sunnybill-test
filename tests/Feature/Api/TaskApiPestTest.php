<?php

use Tests\Traits\InteractsWithApi;
use Tests\Traits\CreatesTestData;
use App\Models\Task;
use App\Models\User;

uses(InteractsWithApi::class, CreatesTestData::class);

test('can list tasks', function () {
    $this->createMultiple('task', 5);

    $response = $this->apiGet('/tasks', ['tasks:read']);

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

test('can create task', function () {
    $customer = $this->createCustomer();
    $assignee = User::factory()->create();

    $taskData = [
        'title' => 'Test Task',
        'description' => 'Test task description',
        'status' => 'open',
        'priority' => 'medium',
        'assigned_to' => $assignee->id,
        'customer_id' => $customer->id,
        'due_date' => now()->addDays(7)->toDateString(),
    ];

    $response = $this->apiPost('/tasks', $taskData, ['tasks:create']);

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
                'due_date'
            ]
        ]);

    $this->assertDatabaseHas('tasks', [
        'title' => 'Test Task',
        'description' => 'Test task description',
        'status' => 'open',
        'priority' => 'medium',
        'assigned_to' => $assignee->id,
        'customer_id' => $customer->id,
    ]);
});

test('can show task', function () {
    $task = $this->createTask();

    $response = $this->apiGet("/tasks/{$task->id}", ['tasks:read']);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
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
        ])
        ->assertJson([
            'data' => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
            ]
        ]);
});

test('can update task', function () {
    $task = $this->createTask();
    
    $updateData = [
        'title' => 'Updated Task Title',
        'status' => 'in_progress',
        'priority' => 'high',
    ];

    $response = $this->apiPut("/tasks/{$task->id}", $updateData, ['tasks:update']);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $task->id,
                'title' => 'Updated Task Title',
                'status' => 'in_progress',
                'priority' => 'high',
            ]
        ]);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Updated Task Title',
        'status' => 'in_progress',
        'priority' => 'high',
    ]);
});

test('can delete task', function () {
    $task = $this->createTask();

    $response = $this->apiDelete("/tasks/{$task->id}", ['tasks:delete']);

    $response->assertStatus(204);

    $this->assertSoftDeleted('tasks', [
        'id' => $task->id,
    ]);
});

test('can update task status', function () {
    $task = $this->createTask(['status' => 'open']);

    $response = $this->apiPatch("/tasks/{$task->id}/status", [
        'status' => 'completed'
    ], ['tasks:status']);

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

test('can assign task', function () {
    $task = $this->createTask();
    $user = User::factory()->create();

    $response = $this->apiPatch("/tasks/{$task->id}/assign", [
        'assigned_to' => $user->id
    ], ['tasks:assign']);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $task->id,
                'assigned_to' => $user->id,
            ]
        ]);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'assigned_to' => $user->id,
    ]);
});

test('unauthorized access to tasks', function () {
    $this->assertUnauthorized('GET', '/tasks');
    $this->assertUnauthorized('POST', '/tasks', ['title' => 'Test']);
});

test('forbidden access with wrong permissions', function () {
    $task = $this->createTask();
    
    $this->assertForbidden('GET', '/tasks', ['tasks:read']);
    $this->assertForbidden('POST', '/tasks', ['tasks:create'], ['title' => 'Test']);
    $this->assertForbidden('PUT', "/tasks/{$task->id}", ['tasks:update'], ['title' => 'Updated']);
    $this->assertForbidden('DELETE', "/tasks/{$task->id}", ['tasks:delete']);
});

test('validation errors when creating task', function () {
    $response = $this->apiPost('/tasks', [], ['tasks:create']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'status', 'priority']);
});

test('can get task subtasks', function () {
    $parentTask = $this->createTask();
    $subtask1 = $this->createTask(['parent_id' => $parentTask->id]);
    $subtask2 = $this->createTask(['parent_id' => $parentTask->id]);

    $response = $this->apiGet("/tasks/{$parentTask->id}/subtasks", ['tasks:read']);

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'parent_id'
                ]
            ]
        ]);
});

test('task not found', function () {
    $response = $this->apiGet('/tasks/999', ['tasks:read']);
    
    $response->assertStatus(404);
});