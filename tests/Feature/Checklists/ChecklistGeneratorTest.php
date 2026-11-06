<?php

use App\Domain\Checklists\ChecklistGenerator;
use App\Domain\Workflow\ProjectStatusService;
use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\TemplateSeeder;

beforeEach(fn () => $this->seed(TemplateSeeder::class));

it('generates the 17 go-live tasks when entering in_pubblicazione', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id, 'status' => 'in_sviluppo']);

    app(ProjectStatusService::class)->change($project, 'in_pubblicazione', $owner);

    $tasks = Task::where('project_id', $project->id)->orderBy('sort')->get();
    expect($tasks)->toHaveCount(17)
        ->and($tasks->first()->title)->toBe('Responsive')
        ->and($tasks->last()->title)->toBe('Redirect vecchio sito')
        ->and($tasks->every(fn ($t) => $t->assignee_id === $owner->id && $t->template_item_id !== null))->toBeTrue();
});

it('does not duplicate the checklist when re-entering the state', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['status' => 'in_sviluppo']);
    $service = app(ProjectStatusService::class);

    $service->change($project, 'in_pubblicazione', $user);
    $service->change($project, 'feedback_modifiche', $user);
    $service->change($project, 'in_pubblicazione', $user);

    expect(Task::where('project_id', $project->id)->count())->toBe(17);
});

it('generates nothing for modifica projects', function () {
    $project = Project::factory()->create(['type' => ProjectType::Modifica]);

    expect(app(ChecklistGenerator::class)->generate($project, 'in_pubblicazione'))->toBe(0)
        ->and(Task::count())->toBe(0);
});
