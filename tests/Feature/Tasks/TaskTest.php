<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

it('keeps manual to-dos with no template item', function () {
    $project = Project::factory()->create();
    $user = User::factory()->create();

    $task = Task::factory()->create([
        'project_id' => $project->id, 'assignee_id' => $user->id, 'title' => 'Chiamare il cliente',
    ]);

    expect($task->fresh()->template_item_id)->toBeNull()
        ->and(Task::assignedTo($user)->count())->toBe(1);
});

it('completes and reopens a task', function () {
    $task = Task::factory()->create();

    $task->complete();
    expect($task->fresh()->completed_at)->not->toBeNull()
        ->and(Task::completed()->count())->toBe(1)
        ->and(Task::open()->count())->toBe(0);

    $task->reopen();
    expect($task->fresh()->completed_at)->toBeNull()
        ->and(Task::open()->count())->toBe(1);
});
