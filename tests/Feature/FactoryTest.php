<?php

use App\Models\Task;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\SolarPlant;

test('task factory works correctly', function () {
    $task = Task::factory()->create();

    expect($task)->toBeInstanceOf(Task::class);
    expect($task->title)->not->toBeEmpty();
    expect($task->status)->toBeIn(['open', 'in_progress', 'waiting_external', 'waiting_internal', 'completed', 'cancelled']);
    expect($task->priority)->toBeIn(['low', 'medium', 'high', 'urgent']);
    
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => $task->title,
    ]);
});

test('customer factory works correctly', function () {
    $customer = Customer::factory()->create();

    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->name)->not->toBeEmpty();
    expect($customer->email)->not->toBeEmpty();
    expect($customer->customer_type)->toBeIn(['business', 'private']);
    
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'email' => $customer->email,
    ]);
});

test('user factory works correctly', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->not->toBeEmpty();
    expect($user->email)->not->toBeEmpty();
    
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'email' => $user->email,
    ]);
});

test('supplier factory works correctly', function () {
    $supplier = Supplier::factory()->create();

    expect($supplier)->toBeInstanceOf(Supplier::class);
    expect($supplier->name)->not->toBeEmpty();
    
    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => $supplier->name,
    ]);
});

test('solar plant factory works correctly', function () {
    $solarPlant = SolarPlant::factory()->create();

    expect($solarPlant)->toBeInstanceOf(SolarPlant::class);
    expect($solarPlant->name)->not->toBeEmpty();
    expect($solarPlant->plant_number)->not->toBeEmpty();
    
    $this->assertDatabaseHas('solar_plants', [
        'id' => $solarPlant->id,
        'plant_number' => $solarPlant->plant_number,
    ]);
});

test('task factory states work correctly', function () {
    $urgentTask = Task::factory()->urgent()->create();
    expect($urgentTask->priority)->toBe('urgent');

    $openTask = Task::factory()->open()->create();
    expect($openTask->status)->toBe('open');

    $completedTask = Task::factory()->completed()->create();
    expect($completedTask->status)->toBe('completed');
    expect($completedTask->completed_at)->not->toBeNull();
});

test('customer factory states work correctly', function () {
    $businessCustomer = Customer::factory()->business()->create();
    expect($businessCustomer->customer_type)->toBe('business');
    expect($businessCustomer->company_name)->not->toBeNull();

    $privateCustomer = Customer::factory()->private()->create();
    expect($privateCustomer->customer_type)->toBe('private');
    expect($privateCustomer->company_name)->toBeNull();
});

test('multiple factories can be created', function () {
    $tasks = Task::factory()->count(5)->create();
    expect($tasks)->toHaveCount(5);
    
    $customers = Customer::factory()->count(3)->create();
    expect($customers)->toHaveCount(3);
});

test('factories can create related models', function () {
    $customer = Customer::factory()->create();
    $task = Task::factory()->create([
        'customer_id' => $customer->id
    ]);

    expect($task->customer_id)->toBe($customer->id);
    
    // Test relationship (if it exists)
    if (method_exists($task, 'customer')) {
        expect($task->customer)->toBeInstanceOf(Customer::class);
        expect($task->customer->id)->toBe($customer->id);
    }
});