<?php

test('database connection works', function () {
    // Test basic database connection
    expect(\DB::connection()->getPdo())->not->toBeNull();
});

test('basic models can be instantiated', function () {
    $user = new \App\Models\User();
    expect($user)->toBeInstanceOf(\App\Models\User::class);

    $task = new \App\Models\Task();
    expect($task)->toBeInstanceOf(\App\Models\Task::class);

    $customer = new \App\Models\Customer();
    expect($customer)->toBeInstanceOf(\App\Models\Customer::class);
});

test('application configuration is loaded', function () {
    expect(config('app.name'))->not->toBeNull();
    expect(config('database.default'))->not->toBeNull();
});

test('test environment is properly configured', function () {
    expect(app()->environment())->toBe('testing');
});