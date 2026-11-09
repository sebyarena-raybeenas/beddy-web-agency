<?php

use App\Domain\Projects\ProjectCreator;
use App\Domain\Workflow\InvalidTransition;
use App\Domain\Workflow\ProjectStatusService;
use App\Enums\MaterialStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ProjectDetail;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\RelationManagers\DomainsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\MaterialsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\TasksRelationManager;
use App\Filament\Resources\SalesReps\Pages\CreateSalesRep;
use App\Filament\Resources\SalesReps\SalesRepResource;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Project;
use App\Models\SalesRep;
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

    Livewire::test(ProjectDetail::class, ['record' => $project->getRouteKey()])
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

    Livewire::test(ProjectDetail::class, ['record' => $project->getRouteKey()])
        ->callAction('changeStatus', ['status' => 'in_sviluppo'])
        ->assertNotified(Notification::make()->title('Il progetto è già in questo stato')->danger());

    expect($project->fresh()->status)->toBe('in_preparazione');
});

it('rejects the automatic ready state in the form', function () {
    $project = Project::factory()->create();

    Livewire::test(ProjectDetail::class, ['record' => $project->getRouteKey()])
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

    Livewire::test(MaterialsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
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

    Livewire::test(MaterialsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
        ->callAction(TestAction::make('edit')->table($material), ['status' => 'atteso', 'files' => [$file]])
        ->assertHasNoActionErrors();

    $attachment = $material->attachments()->firstOrFail();
    expect($attachment->filename)->toBe('foto.jpg')
        ->and($attachment->uploaded_by)->toBe($this->user->id);
    Storage::disk(Attachment::DISK)->assertExists($attachment->path);
});

it('creates a to-do and completes it', function () {
    $project = Project::factory()->create();

    $livewire = Livewire::test(TasksRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
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

    Livewire::test(DomainsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
        ->callAction(TestAction::make('makePrimary')->table($domain));

    expect($project->fresh()->primary_domain_id)->toBe($domain->id);
});

it('handles a stale page: the state changed elsewhere is no longer selectable', function () {
    $project = Project::factory()->create();

    $page = Livewire::test(ProjectDetail::class, ['record' => $project->getRouteKey()])
        ->mountAction('changeStatus');

    app(ProjectStatusService::class)->change($project, 'in_sviluppo', $this->user);

    // Real service, no mocks: the now-current state is rejected by the form, nothing changes.
    $page->setActionData(['status' => 'in_sviluppo'])
        ->callMountedAction()
        ->assertHasActionErrors(['status']);

    expect($project->fresh()->status)->toBe('in_sviluppo')
        ->and($project->statusHistory()->count())->toBe(2);
});

it('lets a stale page move back to the previous state through the real service', function () {
    $project = Project::factory()->create();
    // An open mandatory requirement keeps the project out of the automatic ready state.
    $project->requirements()->create(['label' => 'Logo', 'is_mandatory' => true, 'sort' => 1]);

    $page = Livewire::test(ProjectDetail::class, ['record' => $project->getRouteKey()])
        ->mountAction('changeStatus');

    app(ProjectStatusService::class)->change($project, 'in_sviluppo', $this->user);

    $page->setActionData(['status' => 'in_preparazione'])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($project->fresh()->status)->toBe('in_preparazione')
        ->and($project->statusHistory()->count())->toBe(3);
});

it('rejects a disallowed file type on a material upload', function () {
    Storage::fake(Attachment::DISK);
    $project = seededProject($this->user);
    $material = $project->materials()->where('kind', 'foto')->firstOrFail();
    $file = UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload');

    Livewire::test(MaterialsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
        ->callAction(TestAction::make('edit')->table($material), ['status' => 'atteso', 'files' => [$file]])
        ->assertHasActionErrors(['files']);

    expect($material->attachments()->count())->toBe(0);
});

it('accepts office and odf documents on a material upload', function (string $name, string $mime) {
    Storage::fake(Attachment::DISK);
    $project = seededProject($this->user);
    $material = $project->materials()->where('kind', 'testi')->firstOrFail();
    $file = UploadedFile::fake()->create($name, 10, $mime);

    Livewire::test(MaterialsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => EditProject::class])
        ->callAction(TestAction::make('edit')->table($material), ['status' => 'atteso', 'files' => [$file]])
        ->assertHasNoActionErrors();

    expect($material->attachments()->value('filename'))->toBe($name);
})->with([
    'docx' => ['testi.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'xlsx' => ['listino.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    'pptx' => ['presentazione.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
    'odt' => ['testi.odt', 'application/vnd.oasis.opendocument.text'],
    'ods' => ['listino.ods', 'application/vnd.oasis.opendocument.spreadsheet'],
    'odp' => ['presentazione.odp', 'application/vnd.oasis.opendocument.presentation'],
]);

it('allows material uploads up to the 20 MB limit at the Livewire layer too', function () {
    expect(config('livewire.temporary_file_upload.rules'))->toContain('max:20480');
});

it('saves payment, hosting and assistance from the project form', function () {
    $project = Project::factory()->create();

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->fillForm(['payment_status' => 'acconto', 'hosting_status' => 'suo', 'has_assistance' => true, 'assistance_type' => 'Base'])
        ->call('save')
        ->assertHasNoFormErrors();

    $project->refresh();
    expect($project->payment_status->value)->toBe('acconto')
        ->and($project->hosting_status->value)->toBe('suo')
        ->and($project->has_assistance)->toBeTrue()
        ->and($project->assistance_type)->toBe('Base');
});

it('saves priority and sales rep from the project form', function () {
    $project = Project::factory()->create();
    $rep = SalesRep::factory()->create();

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->fillForm(['priority' => 'urgente', 'sales_rep_id' => $rep->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($project->fresh()->priority->value)->toBe('urgente')
        ->and($project->fresh()->salesRep->is($rep))->toBeTrue();
});

it('manages sales reps and keeps projects when one is deleted', function () {
    $this->get(SalesRepResource::getUrl('index'))->assertOk();

    Livewire::test(CreateSalesRep::class)
        ->fillForm(['name' => 'Marco Venditore', 'email' => 'marco@esempio.test'])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateSalesRep::class)
        ->fillForm(['name' => 'Marco Venditore'])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);

    $rep = SalesRep::where('name', 'Marco Venditore')->firstOrFail();
    $project = Project::factory()->create(['sales_rep_id' => $rep->id]);
    $rep->delete();

    expect($project->fresh())->not->toBeNull()
        ->and($project->fresh()->sales_rep_id)->toBeNull();
});
