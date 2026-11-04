<?php

use App\Domain\Projects\ProjectCreator;
use App\Domain\Workflow\InvalidTransition;
use App\Domain\Workflow\ProjectStatusService;
use App\Enums\MaterialStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\RelationManagers\DomainsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\MaterialsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\TasksRelationManager;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\TemplateSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(TemplateSeeder::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function seededProject(User $by): Project
{
    return app(ProjectCreator::class)->create([
        'client_id' => Client::factory()->create()->id, 'name' => 'Sito Rossi', 'type' => 'nuovo',
    ], $by);
}

it('lists projects with the status label', function () {
    $project = seededProject($this->user);

    Livewire::test(ListProjects::class)
        ->assertCanSeeTableRecords([$project])
        ->assertSee('In preparazione');
});

it('creates a nuovo project from the form with its requirements', function () {
    $client = Client::factory()->create();
    $member = User::factory()->create();

    Livewire::test(CreateProject::class)
        ->fillForm([
            'client_id' => $client->id,
            'name' => 'Sito Bianchi',
            'type' => 'nuovo',
            'owner_id' => $this->user->id,
            'members' => [$member->id],
            'due_date' => '2026-12-31',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::where('name', 'Sito Bianchi')->firstOrFail();

    expect($project->requirements)->toHaveCount(6)
        ->and($project->materials)->toHaveCount(3)
        ->and($project->status)->toBe('in_preparazione')
        ->and($project->created_by)->toBe($this->user->id)
        ->and($project->members->pluck('id')->all())->toBe([$member->id])
        ->and($project->due_date->toDateString())->toBe('2026-12-31');
});

it('moves status with the Cambia stato action', function () {
    $project = Project::factory()->create();

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('changeStatus', ['status' => 'in_sviluppo'])
        ->assertHasNoActionErrors();

    expect($project->fresh()->status)->toBe('in_sviluppo');
});

it('does not offer the ready or current state', function () {
    $project = Project::factory()->create();

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->mountAction('changeStatus')
        ->assertSchemaComponentExists('status', 'mountedActionSchema0', function ($component) {
            $options = $component->getOptions();

            return ! array_key_exists('pronto_da_sviluppare', $options)
                && ! array_key_exists('in_preparazione', $options)
                && array_key_exists('in_sviluppo', $options);
        });
});

it('shows a danger notification when the transition is invalid', function () {
    $project = Project::factory()->create();

    $this->mock(ProjectStatusService::class)
        ->shouldReceive('change')
        ->andThrow(new InvalidTransition('Il progetto è già in questo stato'));

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('changeStatus', ['status' => 'in_sviluppo'])
        ->assertNotified(Notification::make()->title('Il progetto è già in questo stato')->danger());

    expect($project->fresh()->status)->toBe('in_preparazione');
});

it('rejects the automatic ready state in the form', function () {
    $project = Project::factory()->create();

    Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->callAction('changeStatus', ['status' => 'pronto_da_sviluppare'])
        ->assertHasActionErrors(['status']);

    expect($project->fresh()->status)->toBe('in_preparazione');
});

it('disables the type field on edit', function () {
    $project = Project::factory()->create();

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->assertFormFieldIsDisabled('type');
});

it('links the detail url to the view page', function () {
    $project = Project::factory()->create();

    expect($project->detailUrl())->toBe(ProjectResource::getUrl('view', ['record' => $project]));
    $this->get($project->detailUrl())->assertOk();
});

it('ticks the requirement when material is marked as received', function () {
    $project = seededProject($this->user);
    $material = $project->materials()->where('kind', 'foto')->firstOrFail();

    Livewire::test(MaterialsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('edit')->table($material), ['status' => MaterialStatus::Ricevuto->value])
        ->assertHasNoActionErrors();

    expect($material->fresh()->status)->toBe(MaterialStatus::Ricevuto)
        ->and($material->requirement->fresh()->is_done)->toBeTrue();
});

it('stores an uploaded file as an attachment of the material', function () {
    Storage::fake(Attachment::DISK);
    $project = seededProject($this->user);
    $material = $project->materials()->where('kind', 'foto')->firstOrFail();
    $file = UploadedFile::fake()->image('foto.jpg');

    Livewire::test(MaterialsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('edit')->table($material), ['status' => 'atteso', 'files' => [$file]])
        ->assertHasNoActionErrors();

    $attachment = $material->attachments()->firstOrFail();
    expect($attachment->filename)->toBe('foto.jpg')
        ->and($attachment->uploaded_by)->toBe($this->user->id);
    Storage::disk(Attachment::DISK)->assertExists($attachment->path);
});

it('creates a to-do and completes it', function () {
    $project = Project::factory()->create();

    $livewire = Livewire::test(TasksRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['title' => 'Chiamare il cliente'])
        ->assertHasNoActionErrors();

    $task = $project->tasks()->firstOrFail();
    expect($task->created_by)->toBe($this->user->id);

    $livewire->callAction(TestAction::make('complete')->table($task));
    expect($task->fresh()->completed_at)->not->toBeNull();
});

it('makes a domain primary from the domains relation', function () {
    $project = Project::factory()->create();
    $domain = Domain::factory()->create(['client_id' => $project->client_id, 'project_id' => $project->id]);

    Livewire::test(DomainsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('makePrimary')->table($domain));

    expect($project->fresh()->primary_domain_id)->toBe($domain->id);
});
