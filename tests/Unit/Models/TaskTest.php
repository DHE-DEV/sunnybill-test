<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Project;
use App\Models\SolarPlant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_can_be_created()
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $task = Task::create([
            'title' => 'Test Task',
            'description' => 'Test task description',
            'status' => 'open',
            'priority' => 'medium',
            'assigned_to' => $user->id,
            'customer_id' => $customer->id,
            'due_date' => Carbon::now()->addDays(7),
        ]);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('Test task description', $task->description);
        $this->assertEquals('open', $task->status);
        $this->assertEquals('medium', $task->priority);
        $this->assertEquals($user->id, $task->assigned_to);
        $this->assertEquals($customer->id, $task->customer_id);
    }

    public function test_task_has_fillable_attributes()
    {
        $fillable = [
            'title',
            'description',
            'status',
            'priority',
            'assigned_to',
            'customer_id',
            'supplier_id',
            'project_id',
            'solar_plant_id',
            'parent_id',
            'due_date',
            'estimated_hours',
            'actual_hours',
            'tags',
            'notes',
        ];

        $task = new Task();
        
        $this->assertEquals($fillable, $task->getFillable());
    }

    public function test_task_belongs_to_user()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['assigned_to' => $user->id]);

        $this->assertInstanceOf(User::class, $task->assignedUser);
        $this->assertEquals($user->id, $task->assignedUser->id);
    }

    public function test_task_belongs_to_customer()
    {
        $customer = Customer::factory()->create();
        $task = Task::factory()->create(['customer_id' => $customer->id]);

        $this->assertInstanceOf(Customer::class, $task->customer);
        $this->assertEquals($customer->id, $task->customer->id);
    }

    public function test_task_belongs_to_supplier()
    {
        $supplier = Supplier::factory()->create();
        $task = Task::factory()->create(['supplier_id' => $supplier->id]);

        $this->assertInstanceOf(Supplier::class, $task->supplier);
        $this->assertEquals($supplier->id, $task->supplier->id);
    }

    public function test_task_belongs_to_project()
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->assertInstanceOf(Project::class, $task->project);
        $this->assertEquals($project->id, $task->project->id);
    }

    public function test_task_belongs_to_solar_plant()
    {
        $solarPlant = SolarPlant::factory()->create();
        $task = Task::factory()->create(['solar_plant_id' => $solarPlant->id]);

        $this->assertInstanceOf(SolarPlant::class, $task->solarPlant);
        $this->assertEquals($solarPlant->id, $task->solarPlant->id);
    }

    public function test_task_has_parent_relationship()
    {
        $parentTask = Task::factory()->create();
        $childTask = Task::factory()->create(['parent_id' => $parentTask->id]);

        $this->assertInstanceOf(Task::class, $childTask->parent);
        $this->assertEquals($parentTask->id, $childTask->parent->id);
    }

    public function test_task_has_subtasks_relationship()
    {
        $parentTask = Task::factory()->create();
        $subtask1 = Task::factory()->create(['parent_id' => $parentTask->id]);
        $subtask2 = Task::factory()->create(['parent_id' => $parentTask->id]);

        $this->assertCount(2, $parentTask->subtasks);
        $this->assertTrue($parentTask->subtasks->contains($subtask1));
        $this->assertTrue($parentTask->subtasks->contains($subtask2));
    }

    public function test_task_status_scopes()
    {
        Task::factory()->create(['status' => 'open']);
        Task::factory()->create(['status' => 'in_progress']);
        Task::factory()->create(['status' => 'completed']);
        Task::factory()->create(['status' => 'cancelled']);

        $this->assertCount(1, Task::open()->get());
        $this->assertCount(1, Task::inProgress()->get());
        $this->assertCount(1, Task::completed()->get());
        $this->assertCount(1, Task::cancelled()->get());
    }

    public function test_task_priority_scopes()
    {
        Task::factory()->create(['priority' => 'low']);
        Task::factory()->create(['priority' => 'medium']);
        Task::factory()->create(['priority' => 'high']);
        Task::factory()->create(['priority' => 'urgent']);

        $this->assertCount(1, Task::lowPriority()->get());
        $this->assertCount(1, Task::mediumPriority()->get());
        $this->assertCount(1, Task::highPriority()->get());
        $this->assertCount(1, Task::urgentPriority()->get());
    }

    public function test_task_assigned_to_scope()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Task::factory()->create(['assigned_to' => $user1->id]);
        Task::factory()->create(['assigned_to' => $user1->id]);
        Task::factory()->create(['assigned_to' => $user2->id]);

        $this->assertCount(2, Task::assignedTo($user1->id)->get());
        $this->assertCount(1, Task::assignedTo($user2->id)->get());
    }

    public function test_task_due_today_scope()
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $yesterday = Carbon::yesterday();

        Task::factory()->create(['due_date' => $today]);
        Task::factory()->create(['due_date' => $tomorrow]);
        Task::factory()->create(['due_date' => $yesterday]);

        $this->assertCount(1, Task::dueToday()->get());
    }

    public function test_task_overdue_scope()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $tomorrow = Carbon::tomorrow();

        Task::factory()->create(['due_date' => $yesterday, 'status' => 'open']);
        Task::factory()->create(['due_date' => $tomorrow, 'status' => 'open']);
        Task::factory()->create(['due_date' => $yesterday, 'status' => 'completed']);

        $this->assertCount(1, Task::overdue()->get());
    }

    public function test_task_is_overdue_attribute()
    {
        $overdueTask = Task::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => 'open',
        ]);

        $futureTask = Task::factory()->create([
            'due_date' => Carbon::tomorrow(),
            'status' => 'open',
        ]);

        $completedTask = Task::factory()->create([
            'due_date' => Carbon::yesterday(),
            'status' => 'completed',
        ]);

        $this->assertTrue($overdueTask->is_overdue);
        $this->assertFalse($futureTask->is_overdue);
        $this->assertFalse($completedTask->is_overdue);
    }

    public function test_task_is_completed_attribute()
    {
        $completedTask = Task::factory()->create(['status' => 'completed']);
        $openTask = Task::factory()->create(['status' => 'open']);

        $this->assertTrue($completedTask->is_completed);
        $this->assertFalse($openTask->is_completed);
    }

    public function test_task_progress_percentage_attribute()
    {
        $task = Task::factory()->create([
            'estimated_hours' => 10,
            'actual_hours' => 7,
        ]);

        $this->assertEquals(70, $task->progress_percentage);
    }

    public function test_task_progress_percentage_with_no_estimated_hours()
    {
        $task = Task::factory()->create([
            'estimated_hours' => null,
            'actual_hours' => 5,
        ]);

        $this->assertEquals(0, $task->progress_percentage);
    }

    public function test_task_can_be_soft_deleted()
    {
        $task = Task::factory()->create();
        $taskId = $task->id;

        $task->delete();

        $this->assertSoftDeleted('tasks', ['id' => $taskId]);
        
        // Should not be found in regular queries
        $this->assertNull(Task::find($taskId));
        
        // Should be found when including trashed
        $this->assertNotNull(Task::withTrashed()->find($taskId));
    }

    public function test_task_search_scope()
    {
        Task::factory()->create(['title' => 'Fix bug in authentication']);
        Task::factory()->create(['title' => 'Implement new feature']);
        Task::factory()->create(['description' => 'This task involves fixing authentication issues']);

        $searchResults = Task::search('authentication')->get();

        $this->assertCount(2, $searchResults);
    }

    public function test_task_casts_attributes_correctly()
    {
        $task = Task::factory()->create([
            'due_date' => '2024-12-25',
            'estimated_hours' => 8.5,
            'actual_hours' => 6.25,
            'tags' => ['bug', 'urgent', 'authentication'],
        ]);

        $this->assertInstanceOf(Carbon::class, $task->due_date);
        $this->assertIsFloat($task->estimated_hours);
        $this->assertIsFloat($task->actual_hours);
        $this->assertIsArray($task->tags);
        $this->assertEquals(['bug', 'urgent', 'authentication'], $task->tags);
    }

    public function test_task_has_proper_date_mutators()
    {
        $task = new Task();
        $task->due_date = '2024-12-25 14:30:00';

        $this->assertInstanceOf(Carbon::class, $task->due_date);
        $this->assertEquals('2024-12-25', $task->due_date->toDateString());
    }
}