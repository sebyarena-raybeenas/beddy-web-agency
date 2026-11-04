<?php

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

it('saves a comment on a project, newest first', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $first = $project->comments()->create(['user_id' => $user->id, 'body' => 'Primo']);
    $second = $project->comments()->create(['user_id' => $user->id, 'body' => 'Secondo']);

    expect($project->comments)->toHaveCount(2)
        ->and($project->comments->first()->is($second))->toBeTrue()
        ->and($first->commentable->is($project))->toBeTrue()
        ->and($first->user->is($user))->toBeTrue();
});

it('saves a comment on a task', function () {
    $task = Task::factory()->create();
    $comment = Comment::factory()->for($task, 'commentable')->create(['body' => 'Nota']);

    expect($task->comments)->toHaveCount(1)
        ->and($task->comments->first()->body)->toBe('Nota')
        ->and($comment->commentable)->toBeInstanceOf(Task::class);
});

it('keeps the comment when its author is deleted', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create(['user_id' => $user->id]);

    $user->delete();

    expect($comment->fresh()->user_id)->toBeNull();
});
