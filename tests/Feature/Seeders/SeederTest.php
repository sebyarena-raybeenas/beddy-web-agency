<?php

use App\Models\ChecklistTemplate;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Project;
use App\Models\RequirementTemplate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\TemplateSeeder;

it('seeds the standard templates idempotently', function () {
    $this->seed(TemplateSeeder::class);
    $this->seed(TemplateSeeder::class);

    expect(RequirementTemplate::where('flow', 'sito')->count())->toBe(6)
        ->and(RequirementTemplate::where('flow', 'modifica')->count())->toBe(3)
        ->and(ChecklistTemplate::count())->toBe(1)
        ->and(ChecklistTemplate::first()->items()->count())->toBe(17);
});

it('seeds Hotel Rossi with 4 of 6 requirements done', function () {
    $this->seed(TemplateSeeder::class);
    $this->seed(DemoSeeder::class);

    $project = Project::whereHas('client', fn ($q) => $q->where('name', 'Hotel Rossi'))->firstOrFail();
    $foto = $project->materials()->where('kind', 'foto')->first();

    expect($project->status)->toBe('in_preparazione')
        ->and($project->requirements)->toHaveCount(6)
        ->and($project->requirements->where('is_done', true))->toHaveCount(4)
        ->and($foto->status->value)->toBe('atteso')
        ->and((int) $foto->requested_at->diffInDays(now(), true))->toBe(9)
        ->and(User::where('email', 'demo@example.test')->exists())->toBeTrue()
        ->and(User::where('name', 'Collega Demo')->exists())->toBeTrue();
});

it('covers the demo scenarios', function () {
    $this->seed(TemplateSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Project::count())->toBe(8)
        ->and(Project::whereNull('owner_id')->exists())->toBeTrue()
        ->and(Project::where('status', 'in_sviluppo')->whereDate('due_date', '<', today())->exists())->toBeTrue()
        ->and(Domain::where('role', 'principale')->whereNotNull('project_id')->exists())->toBeTrue()
        ->and(Domain::where('role', 'secondario')->whereNotNull('project_id')->exists())->toBeTrue();
});

it('does not seed demo data outside the local environment', function () {
    $this->app['env'] = 'testing';
    $this->seed(DatabaseSeeder::class);

    expect(Project::count())->toBe(0)
        ->and(RequirementTemplate::count())->toBe(9);
});

it('seeds demo data in the local environment', function () {
    $this->app['env'] = 'local';
    $this->seed(DatabaseSeeder::class);

    expect(Project::count())->toBe(8)
        ->and(Client::count())->toBe(6);
});
