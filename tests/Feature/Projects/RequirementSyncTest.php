<?php

use App\Domain\Projects\ProjectCreator;
use App\Enums\MaterialStatus;
use App\Models\Client;
use App\Models\Project;
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
