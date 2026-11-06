<?php

use App\Domain\Workflow\InvalidTransition;
use App\Domain\Workflow\ProjectStatusService;
use App\Enums\ProjectType;
use App\Events\ProjectStatusChanged;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function statusService(): ProjectStatusService
{
    return app(ProjectStatusService::class);
}

it('starts new projects in the initial state', function () {
    $project = Project::factory()->create();

    expect($project->status)->toBe('in_preparazione')
        ->and($project->status_changed_at)->not->toBeNull()
        ->and($project->statusHistory)->toHaveCount(1)
        ->and($project->statusHistory->first()->from_status)->toBeNull()
        ->and($project->statusHistory->first()->to_status)->toBe('in_preparazione');
});

it('allows any manual move inside the flow, also backwards', function () {
    Event::fake([ProjectStatusChanged::class]);
    $user = User::factory()->create();
    $project = Project::factory()->create(['status' => 'in_sviluppo']);

    statusService()->change($project, 'in_preparazione', $user);

    $row = $project->statusHistory()->reorder('id', 'desc')->first();
    expect($project->fresh()->status)->toBe('in_preparazione')
        ->and($row->from_status)->toBe('in_sviluppo')
        ->and($row->to_status)->toBe('in_preparazione')
        ->and($row->changed_by)->toBe($user->id);
    Event::assertDispatched(ProjectStatusChanged::class,
        fn ($e) => $e->from === 'in_sviluppo' && $e->to === 'in_preparazione');
});

it('refuses a manual move to the ready state', function () {
    $project = Project::factory()->create();

    statusService()->change($project, 'pronto_da_sviluppare', null);
})->throws(InvalidTransition::class, 'viene impostato solo in automatico');

it('refuses a state of the other flow', function () {
    $project = Project::factory()->create(['type' => ProjectType::Modifica]);

    statusService()->change($project, 'in_sviluppo', null);
})->throws(InvalidTransition::class, 'non esiste nel flusso');

it('refuses moving to the current state', function () {
    $project = Project::factory()->create();

    statusService()->change($project, 'in_preparazione', null);
})->throws(InvalidTransition::class, 'già in questo stato');

it('lets the system move to the ready state without a user', function () {
    $project = Project::factory()->create();

    statusService()->applySystem($project, 'pronto_da_sviluppare');

    $row = $project->statusHistory()->reorder('id', 'desc')->first();
    expect($project->fresh()->status)->toBe('pronto_da_sviluppare')
        ->and($row->changed_by)->toBeNull();
});

it('sets a 7-day deadline when entering in_sviluppo', function () {
    $this->travelTo('2026-11-10 10:00:00');
    $project = Project::factory()->create();

    statusService()->change($project, 'in_sviluppo', null);

    expect($project->fresh()->due_date->toDateString())->toBe('2026-11-17');
});

it('restarts the deadline on each feedback round', function () {
    $this->travelTo('2026-11-10 10:00:00');
    $project = Project::factory()->create();

    statusService()->change($project, 'feedback_modifiche', null);
    expect($project->fresh()->due_date->toDateString())->toBe('2026-11-17');

    $this->travelTo('2026-11-12 10:00:00');
    statusService()->change($project, 'in_attesa_feedback', null);
    expect($project->fresh()->due_date)->toBeNull();

    $this->travelTo('2026-11-20 10:00:00');
    statusService()->change($project, 'feedback_modifiche', null);
    expect($project->fresh()->due_date->toDateString())->toBe('2026-11-27');
});

it('clears the deadline in states without one', function () {
    $project = Project::factory()->create();
    statusService()->change($project, 'in_sviluppo', null);
    expect($project->fresh()->due_date)->not->toBeNull();

    statusService()->change($project, 'in_attesa_feedback', null);

    expect($project->fresh()->due_date)->toBeNull();
});
