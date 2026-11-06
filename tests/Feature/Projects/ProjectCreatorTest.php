<?php

use App\Domain\Projects\ProjectCreator;
use App\Enums\MaterialKind;
use App\Models\Client;
use App\Models\RequirementTemplate;
use Database\Seeders\TemplateSeeder;

beforeEach(fn () => $this->seed(TemplateSeeder::class));

function createProject(string $type)
{
    return app(ProjectCreator::class)->create([
        'client_id' => Client::factory()->create()->id,
        'name' => 'Progetto',
        'type' => $type,
    ], null);
}

it('copies the sito requirements and creates the three materials', function () {
    $project = createProject('nuovo');

    expect($project->status)->toBe('in_preparazione')
        ->and($project->requirements)->toHaveCount(6)
        ->and($project->materials)->toHaveCount(3)
        ->and($project->materials->pluck('kind')->all())
        ->toEqual([MaterialKind::Foto, MaterialKind::Testi, MaterialKind::Logo])
        ->and($project->materials->every(fn ($m) => $m->status->value === 'atteso' && $m->requested_at && $m->requirement_id))->toBeTrue();
});

it('uses the modifica list for modifica projects', function () {
    $project = createProject('modifica');

    expect($project->requirements)->toHaveCount(3)
        ->and($project->materials)->toHaveCount(1)
        ->and($project->materials->first()->kind)->toBe(MaterialKind::Altro);
});

it('does not change existing projects when a template changes', function () {
    $project = createProject('nuovo');

    RequirementTemplate::where('flow', 'sito')->orderBy('sort')->first()->update(['label' => 'Cambiato', 'is_mandatory' => false]);

    expect($project->requirements()->where('label', 'Cambiato')->count())->toBe(0)
        ->and($project->requirements()->where('is_mandatory', true)->count())->toBe(6);
});
