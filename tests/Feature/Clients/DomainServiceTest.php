<?php

use App\Domain\Clients\DomainService;
use App\Enums\DomainRole;
use App\Models\Domain;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

it('sets the primary domain and demotes the others', function () {
    $project = Project::factory()->create();
    $a = Domain::factory()->for($project->client)->create(['project_id' => $project->id, 'role' => DomainRole::Principale]);
    $b = Domain::factory()->for($project->client)->create(['project_id' => $project->id]);
    $project->update(['primary_domain_id' => $a->id]);

    app(DomainService::class)->setPrimary($project, $b);

    expect($project->fresh()->primary_domain_id)->toBe($b->id)
        ->and($b->fresh()->role)->toBe(DomainRole::Principale)
        ->and($a->fresh()->role)->toBe(DomainRole::Secondario);
});

it('rejects a domain of another client', function () {
    $project = Project::factory()->create();
    $foreign = Domain::factory()->create();

    try {
        app(DomainService::class)->setPrimary($project, $foreign);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['domain'][0])->toBe('Il dominio non appartiene al cliente di questo progetto');
    }

    expect($project->fresh()->primary_domain_id)->toBeNull();
});

it('attaches an unassigned domain of the same client when set as primary', function () {
    $project = Project::factory()->create();
    $domain = Domain::factory()->for($project->client)->create();

    app(DomainService::class)->setPrimary($project, $domain);

    expect($domain->fresh()->project_id)->toBe($project->id)
        ->and($domain->fresh()->role)->toBe(DomainRole::Principale);
});

it('rejects a domain assigned to another project', function () {
    $project = Project::factory()->create();
    $other = Project::factory()->create(['client_id' => $project->client_id]);
    $domain = Domain::factory()->for($project->client)->create(['project_id' => $other->id]);

    expect(fn () => app(DomainService::class)->setPrimary($project, $domain))
        ->toThrow(ValidationException::class);
});
