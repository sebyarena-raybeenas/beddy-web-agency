<?php

use App\Domain\Workflow\InvalidTransition;
use App\Enums\ProjectType;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;

it('locks the project type after creation', function () {
    $project = Project::factory()->create();

    try {
        $project->update(['type' => ProjectType::Modifica]);
        $this->fail('Expected InvalidTransition');
    } catch (InvalidTransition $e) {
        expect($e->getMessage())->toBe('Il tipo di progetto non si può cambiare dopo la creazione');
    }

    expect($project->fresh()->type)->toBe(ProjectType::Nuovo);
});

it('exposes workflow, label and relations', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $project = Project::factory()->create(['owner_id' => $owner->id]);
    $project->members()->attach($member);
    Domain::factory()->for($project->client)->create(['project_id' => $project->id]);

    expect($project->workflow()->key)->toBe('sito')
        ->and($project->statusLabel())->toBe('In preparazione')
        ->and($project->owner->is($owner))->toBeTrue()
        ->and($project->members)->toHaveCount(1)
        ->and($project->domains)->toHaveCount(1)
        ->and($project->client->projects)->toHaveCount(1)
        ->and($project->primaryDomain)->toBeNull();
});

it('records the authenticated user in the first history row', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $project = Project::factory()->create();

    expect($project->statusHistory->first()->changed_by)->toBe($user->id);
});

it('records the creator passed to ProjectCreator in the first history row, over the authenticated user', function () {
    $this->seed(Database\Seeders\TemplateSeeder::class);
    $creator = User::factory()->create();
    $this->actingAs(User::factory()->create());

    $project = app(App\Domain\Projects\ProjectCreator::class)->create([
        'client_id' => App\Models\Client::factory()->create()->id,
        'name' => 'Progetto',
        'type' => 'nuovo',
    ], $creator);

    expect($project->statusHistory()->orderBy('id')->first()->changed_by)->toBe($creator->id);
});
