<?php

test('pest helper functions work correctly', function () {
    // Test createUser helper
    $user = createUser(['name' => 'Test User']);
    expect($user)->toBeInstanceOf(\App\Models\User::class);
    expect($user->name)->toBe('Test User');

    // Test createCustomer helper
    $customer = createCustomer(['name' => 'Test Customer']);
    expect($customer)->toBeInstanceOf(\App\Models\Customer::class);
    expect($customer->name)->toBe('Test Customer');

    // Test createSupplier helper
    $supplier = createSupplier(['name' => 'Test Supplier']);
    expect($supplier)->toBeInstanceOf(\App\Models\Supplier::class);
    expect($supplier->name)->toBe('Test Supplier');

    // Test createTask helper
    $task = createTask(['title' => 'Test Task']);
    expect($task)->toBeInstanceOf(\App\Models\Task::class);
    expect($task->title)->toBe('Test Task');

    // Test createSolarPlant helper
    $solarPlant = createSolarPlant(['name' => 'Test Plant']);
    expect($solarPlant)->toBeInstanceOf(\App\Models\SolarPlant::class);
    expect($solarPlant->name)->toBe('Test Plant');
});

test('actingAs helpers work correctly', function () {
    // Test actingAsAdmin
    $admin = actingAsAdmin();
    expect($admin)->toBeInstanceOf(\App\Models\User::class);
    expect($admin->role)->toBe('admin');

    // Test actingAsUser
    $user = actingAsUser();
    expect($user)->toBeInstanceOf(\App\Models\User::class);
    expect($user->role)->toBe('user');
});

test('related model creation helpers work', function () {
    // Test createTaskWithCustomer
    $task = createTaskWithCustomer(
        ['title' => 'Customer Task'],
        ['name' => 'Task Customer']
    );
    
    expect($task)->toBeInstanceOf(\App\Models\Task::class);
    expect($task->title)->toBe('Customer Task');
    expect($task->customer_id)->not->toBeNull();
    
    if (method_exists($task, 'customer')) {
        expect($task->customer->name)->toBe('Task Customer');
    }

    // Test createTaskWithSupplier
    $task = createTaskWithSupplier(
        ['title' => 'Supplier Task'],
        ['name' => 'Task Supplier']
    );
    
    expect($task)->toBeInstanceOf(\App\Models\Task::class);
    expect($task->title)->toBe('Supplier Task');
    expect($task->supplier_id)->not->toBeNull();

    // Test createTaskWithAssignedUser
    $task = createTaskWithAssignedUser(
        ['title' => 'Assigned Task'],
        ['name' => 'Assigned User']
    );
    
    expect($task)->toBeInstanceOf(\App\Models\Task::class);
    expect($task->title)->toBe('Assigned Task');
    expect($task->assigned_to)->not->toBeNull();
});

test('time filter helpers work correctly', function () {
    // Test createTasksForTimeFilter
    $tasks = createTasksForTimeFilter();
    
    expect($tasks)->toBeArray();
    expect($tasks)->toHaveKeys(['today', 'next_7_days', 'next_30_days', 'overdue', 'completed']);
    
    foreach ($tasks as $key => $task) {
        expect($task)->toBeInstanceOf(\App\Models\Task::class);
    }
    
    // Check specific conditions
    expect($tasks['today']->due_date)->toBe(now()->toDateString());
    expect($tasks['today']->status)->toBe('open');
    
    expect($tasks['completed']->status)->toBe('completed');
    expect($tasks['completed']->completed_at)->not->toBeNull();
});

test('milestone helpers work correctly', function () {
    // Test createMilestonesForTimeFilter
    $milestones = createMilestonesForTimeFilter();
    
    expect($milestones)->toBeArray();
    expect($milestones)->toHaveKeys(['today', 'next_7_days', 'next_30_days', 'overdue', 'completed']);
    
    foreach ($milestones as $key => $milestone) {
        expect($milestone)->toBeInstanceOf(\App\Models\SolarPlantMilestone::class);
    }
    
    // Check specific conditions
    expect($milestones['today']->planned_date)->toBe(now()->toDateString());
    expect($milestones['today']->status)->toBe('planned');
    
    expect($milestones['completed']->status)->toBe('completed');
    expect($milestones['completed']->actual_date)->not->toBeNull();
});

test('supplier type helpers work correctly', function () {
    // Test createSupplierType
    $supplierType = createSupplierType(['name' => 'Test Type']);
    expect($supplierType)->toBeInstanceOf(\App\Models\SupplierType::class);
    expect($supplierType->name)->toBe('Test Type');

    // Test createSupplierWithType
    $supplier = createSupplierWithType(
        ['name' => 'Typed Supplier'],
        ['name' => 'Supplier Type']
    );
    
    expect($supplier)->toBeInstanceOf(\App\Models\Supplier::class);
    expect($supplier->name)->toBe('Typed Supplier');
    expect($supplier->supplier_type_id)->not->toBeNull();
});

test('task type helpers work correctly', function () {
    // Test createTaskType
    $taskType = createTaskType(['name' => 'Test Task Type']);
    expect($taskType)->toBeInstanceOf(\App\Models\TaskType::class);
    expect($taskType->name)->toBe('Test Task Type');
});