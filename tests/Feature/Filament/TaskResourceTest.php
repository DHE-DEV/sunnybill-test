<?php

use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Livewire\Livewire;

beforeEach(function () {
    // Authenticate as a user with access permissions
    $this->actingAs(User::factory()->create(['role' => 'admin']));
});

it('kann die Task-Liste rendern', function () {
    Livewire::test(TaskResource\Pages\ListTasks::class)
        ->assertSuccessful();
});

it('kann Tasks auflisten', function () {
    $tasks = Task::factory()->count(5)->create();

    Livewire::test(TaskResource\Pages\ListTasks::class)
        ->assertCanSeeTableRecords($tasks)
        ->assertCountTableRecords(5);
});

it('kann eine Task erstellen', function () {
    $taskType = TaskType::factory()->create();
    $newData = Task::factory()->make();

    Livewire::test(Pages\CreateTask::class)
        ->fillForm([
            'title' => $newData->title,
            'description' => $newData->description,
            'due_date' => $newData->due_date->format('Y-m-d'),
            'priority' => $newData->priority,
            'status' => $newData->status,
            'user_id' => $newData->user_id,
            'task_type_id' => $taskType->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('tasks', [
        'title' => $newData->title,
        'description' => $newData->description,
        'task_type_id' => $taskType->id,
    ]);
});

it('kann eine Task bearbeiten', function () {
    $task = Task::factory()->create();
    $newData = Task::factory()->make();

    Livewire::test(Pages\EditTask::class, [
        'record' => $task->getRouteKey(),
    ])
        ->fillForm([
            'title' => $newData->title,
            'description' => $newData->description,
            'due_date' => $newData->due_date->format('Y-m-d'),
            'priority' => $newData->priority,
            'status' => $newData->status,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => $newData->title,
    ]);
});

//it('kann eine Task löschen', function () {
//    $task = Task::factory()->create();
//
//    Livewire::test(Pages\ListTasks::class)
//        ->mountTableAction(DeleteAction::class, $task)
//        ->callMountedTableAction();
//
//    $this->assertModelMissing($task);
//});

it('kann nach Tasks suchen', function () {
    $taskToFind = Task::factory()->create(['title' => 'Find Me']);
    $otherTask = Task::factory()->create(['title' => 'Ignore Me']);

    Livewire::test(Pages\ListTasks::class)
        ->searchTable($taskToFind->title)
        ->assertCanSeeTableRecords([$taskToFind])
        ->assertSee($taskToFind->title)
        ->assertDontSee($otherTask->title);
});
