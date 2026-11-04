<?php

use App\Domain\Projects\ProjectCreator;
use App\Domain\Projects\RequirementSync;
use App\Domain\Workflow\ProjectStatusService;
use App\Enums\MaterialStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\RequirementTemplate;
use App\Models\User;
use Database\Seeders\TemplateSeeder;

beforeEach(fn () => $this->seed(TemplateSeeder::class));

function newProject(string $type = 'nuovo'): Project
{
    return app(ProjectCreator::class)->create([
        'client_id' => Client::factory()->create()->id,
        'name' => 'Progetto',
        'type' => $type,
    ], null);
}

function completeAll(Project $project, ?int $exceptId = null): void
{
    foreach ($project->requirements()->get() as $r) {
        if ($r->id !== $exceptId) {
            $r->update(['is_done' => true]);
        }
    }
}

it('moves to Pronto when the last mandatory requirement is done', function () {
    $project = newProject();
    $last = $project->requirements->last();

    completeAll($project, $last->id);
    expect($project->fresh()->status)->toBe('in_preparazione');

    $last->update(['is_done' => true]);

    $project = $project->fresh();
    expect($project->status)->toBe('pronto_da_sviluppare')
        ->and($project->statusHistory->last()->changed_by)->toBeNull()
        ->and($project->statusHistory->last()->to_status)->toBe('pronto_da_sviluppare');
});

it('ticks the requirement when material is received', function () {
    $material = newProject()->materials->first();

    $material->update(['status' => MaterialStatus::Ricevuto]);

    $req = $material->requirement->fresh();
    expect($req->is_done)->toBeTrue()
        ->and($req->done_at)->not->toBeNull()
        ->and($material->fresh()->received_at)->not->toBeNull();
});

it('ticks the requirement when material is not needed', function () {
    $material = newProject()->materials->first();

    $material->update(['status' => MaterialStatus::NonNecessario]);

    expect($material->requirement->fresh()->is_done)->toBeTrue()
        ->and($material->fresh()->received_at)->toBeNull();
});

it('goes back to In preparazione when a material returns to atteso while Pronto', function () {
    $project = newProject('modifica');
    completeAll($project, $project->materials()->first()->requirement_id);
    $project->materials()->first()->update(['status' => MaterialStatus::Ricevuto]);
    expect($project->fresh()->status)->toBe('pronto_da_modificare');

    $project->materials()->first()->update(['status' => MaterialStatus::Atteso]);

    $project = $project->fresh();
    expect($project->status)->toBe('in_preparazione')
        ->and($project->statusHistory->last()->changed_by)->toBeNull();
});

it('ignores non-mandatory requirements', function () {
    $project = newProject('modifica');
    $optional = $project->requirements->last();
    $optional->update(['is_mandatory' => false]);

    completeAll($project, $optional->id);

    expect($project->fresh()->status)->toBe('pronto_da_modificare');
});

it('does nothing outside the initial and ready states', function () {
    $project = newProject('modifica');
    completeAll($project);
    $project->fresh()->forceFill(['status' => 'in_sviluppo'])->save();

    $project->requirements()->first()->update(['is_done' => false]);

    expect($project->fresh()->status)->toBe('in_sviluppo');
});

it('goes back to In preparazione when an open mandatory requirement is added while ready', function () {
    $project = newProject('modifica');
    completeAll($project);
    expect($project->fresh()->status)->toBe('pronto_da_modificare');

    $project->requirements()->create(['label' => 'Nuovo', 'is_mandatory' => true, 'sort' => 99]);

    expect($project->fresh()->status)->toBe('in_preparazione');
});

it('moves to ready when the last open mandatory requirement is deleted', function () {
    $project = newProject('modifica');
    $last = $project->requirements->last();
    completeAll($project, $last->id);
    expect($project->fresh()->status)->toBe('in_preparazione');

    $last->delete();

    expect($project->fresh()->status)->toBe('pronto_da_modificare');
});

it('re-evaluates when is_mandatory flips on the only open requirement', function () {
    $project = newProject('modifica');
    $last = $project->requirements->last();
    completeAll($project, $last->id);

    $last->update(['is_mandatory' => false]);
    expect($project->fresh()->status)->toBe('pronto_da_modificare');

    $last->update(['is_mandatory' => true]);
    expect($project->fresh()->status)->toBe('in_preparazione');
});

it('is ready immediately when the flow has no mandatory templates', function () {
    RequirementTemplate::query()->update(['is_mandatory' => false]);

    $project = newProject('modifica');

    expect($project->fresh()->status)->toBe('pronto_da_modificare')
        ->and($project->statusHistory()->count())->toBe(2);
});

it('evaluates once while ProjectCreator copies the templates', function () {
    $evaluations = 0;
    app()->bind(RequirementSync::class, function ($app) use (&$evaluations) {
        return new class($app->make(ProjectStatusService::class), function () use (&$evaluations) {
            $evaluations++;
        }) extends RequirementSync
        {
            public function __construct(ProjectStatusService $status, private Closure $onEvaluate)
            {
                parent::__construct($status);
            }

            public function evaluate(Project $project): void
            {
                ($this->onEvaluate)();
                parent::evaluate($project);
            }
        };
    });

    newProject();

    expect($evaluations)->toBe(1);
});

it('returns to ready after a manual move back to the initial state with all requirements done', function () {
    $project = newProject('modifica');
    completeAll($project);
    $project->fresh()->forceFill(['status' => 'in_sviluppo'])->save();
    $project = $project->fresh();

    $state = $project->workflow()->initial();
    app(ProjectStatusService::class)->change($project, $state, User::factory()->create());

    $project = $project->fresh();
    $history = $project->statusHistory->sortBy('id')->values();
    expect($project->status)->toBe('pronto_da_modificare')
        ->and($history->count())->toBe(4)
        ->and($history[2]->to_status)->toBe($state)
        ->and($history[2]->changed_by)->not->toBeNull()
        ->and($history[3]->to_status)->toBe('pronto_da_modificare')
        ->and($history[3]->changed_by)->toBeNull();
});
