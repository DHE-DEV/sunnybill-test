<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the amount of code you type.
|
*/

function createUser(array $attributes = []): \App\Models\User
{
    return \App\Models\User::factory()->create(array_merge([
        'role' => 'admin',
        'email_verified_at' => now(),
    ], $attributes));
}

function createCustomer(array $attributes = []): \App\Models\Customer
{
    return \App\Models\Customer::factory()->create($attributes);
}

function createAdminUser(array $attributes = []): \App\Models\User
{
    return \App\Models\User::factory()->create(array_merge([
        'role' => 'admin',
        'email_verified_at' => now(),
    ], $attributes));
}

function actingAsAdmin(): \App\Models\User
{
    $user = createUser(['role' => 'admin']);
    test()->actingAs($user);
    return $user;
}

function actingAsUser(): \App\Models\User
{
    $user = createUser(['role' => 'user']);
    test()->actingAs($user);
    return $user;
}

function createSupplier(array $attributes = []): \App\Models\Supplier
{
    return \App\Models\Supplier::factory()->create($attributes);
}

function createSupplierType(array $attributes = []): \App\Models\SupplierType
{
    return \App\Models\SupplierType::factory()->create($attributes);
}

function createSupplierWithType(array $supplierAttributes = [], array $typeAttributes = []): \App\Models\Supplier
{
    $supplierType = createSupplierType($typeAttributes);
    return createSupplier(array_merge(['supplier_type_id' => $supplierType->id], $supplierAttributes));
}

function createTask(array $attributes = []): \App\Models\Task
{
    return \App\Models\Task::factory()->create($attributes);
}

function createTaskType(array $attributes = []): \App\Models\TaskType
{
    return \App\Models\TaskType::factory()->create($attributes);
}

function createSolarPlant(array $attributes = []): \App\Models\SolarPlant
{
    return \App\Models\SolarPlant::factory()->create($attributes);
}

function createSolarPlantMilestone(array $attributes = []): \App\Models\SolarPlantMilestone
{
    return \App\Models\SolarPlantMilestone::factory()->create($attributes);
}

function createTaskWithCustomer(array $taskAttributes = [], array $customerAttributes = []): \App\Models\Task
{
    $customer = createCustomer($customerAttributes);
    return createTask(array_merge(['customer_id' => $customer->id], $taskAttributes));
}

function createTaskWithSupplier(array $taskAttributes = [], array $supplierAttributes = []): \App\Models\Task
{
    $supplier = createSupplier($supplierAttributes);
    return createTask(array_merge(['supplier_id' => $supplier->id], $taskAttributes));
}

function createTaskWithAssignedUser(array $taskAttributes = [], array $userAttributes = []): \App\Models\Task
{
    $user = createUser($userAttributes);
    return createTask(array_merge(['assigned_to' => $user->id], $taskAttributes));
}

function createMilestoneWithSolarPlant(array $milestoneAttributes = [], array $solarPlantAttributes = []): \App\Models\SolarPlantMilestone
{
    $solarPlant = createSolarPlant($solarPlantAttributes);
    return createSolarPlantMilestone(array_merge(['solar_plant_id' => $solarPlant->id], $milestoneAttributes));
}

function createMilestoneWithResponsibleUser(array $milestoneAttributes = [], array $userAttributes = []): \App\Models\SolarPlantMilestone
{
    $user = createUser($userAttributes);
    return createSolarPlantMilestone(array_merge(['responsible_user_id' => $user->id], $milestoneAttributes));
}

// Helper für Dashboard-Tests
function createTasksForTimeFilter(): array
{
    return [
        'today' => createTask(['due_date' => now()->toDateString(), 'status' => 'open']),
        'next_7_days' => createTask(['due_date' => now()->addDays(5)->toDateString(), 'status' => 'open']),
        'next_30_days' => createTask(['due_date' => now()->addDays(20)->toDateString(), 'status' => 'open']),
        'overdue' => createTask(['due_date' => now()->subDays(2)->toDateString(), 'status' => 'open']),
        'completed' => createTask(['due_date' => now()->toDateString(), 'status' => 'completed', 'completed_at' => now()]),
    ];
}

function createMilestonesForTimeFilter(): array
{
    return [
        'today' => createSolarPlantMilestone(['planned_date' => now()->toDateString(), 'status' => 'planned']),
        'next_7_days' => createSolarPlantMilestone(['planned_date' => now()->addDays(5)->toDateString(), 'status' => 'planned']),
        'next_30_days' => createSolarPlantMilestone(['planned_date' => now()->addDays(20)->toDateString(), 'status' => 'planned']),
        'overdue' => createSolarPlantMilestone(['planned_date' => now()->subDays(2)->toDateString(), 'status' => 'planned']),
        'completed' => createSolarPlantMilestone(['planned_date' => now()->toDateString(), 'status' => 'completed', 'actual_date' => now()]),
    ];
}
