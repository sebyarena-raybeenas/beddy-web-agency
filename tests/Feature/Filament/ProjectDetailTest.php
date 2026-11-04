<?php

use App\Enums\MaterialKind;
use App\Enums\MaterialStatus;
use App\Enums\ProjectType;
use App\Filament\Resources\Projects\Pages\ProjectDetail;
use App\Models\Attachment;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\Client;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectRequirement;
use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Sara Admin']);
    $this->actingAs($this->user);
});

function detail(Project $project)
{
    return Livewire::test(ProjectDetail::class, ['record' => $project->getRouteKey()]);
}

function reqFor(Project $project, string $label, bool $done = false, int $sort = 1): ProjectRequirement
{
    return ProjectRequirement::create(['project_id' => $project->id, 'label' => $label, 'is_mandatory' => true, 'is_done' => $done, 'sort' => $sort]);
}

it('renders at the project route with hero, client box and discussion', function () {
    $client = Client::factory()->create(['name' => 'Hotel Rossi']);
    $project = Project::factory()->create(['client_id' => $client->id, 'name' => 'Sito Rossi', 'owner_id' => $this->user->id]);
    $client->contacts()->create(['name' => 'Giulia Ferri', 'phone' => '333 111', 'email' => 'g@rossi.it', 'is_primary' => true]);

    $this->get($project->detailUrl())->assertOk()->assertSee('Sito Rossi')->assertSee('Hotel Rossi');

    detail($project)
        ->assertSee('Sito Rossi')
        ->assertSee('Hotel Rossi')
        ->assertSee('Giulia Ferri')
        ->assertSee('Discussione del team')
        ->assertSee('Attività recente')
        ->assertSee('Modifica');
});

it('renders the stepper with the 6 modifica states', function () {
    $project = Project::factory()->create(['type' => ProjectType::Modifica]);

    $page = detail($project);

    foreach (['In preparazione', 'Pronto da modificare', 'In modifica', 'In attesa di feedback', 'Feedback cliente: altre modifiche', 'Modifiche pubblicate'] as $label) {
        $page->assertSee($label);
    }

    expect(substr_count($page->html(), 'class="pd-step'))->toBe(6);
});

it('changes status from the stepper', function () {
    $project = Project::factory()->create();

    detail($project)->call('changeStatus', 'in_sviluppo');

    expect($project->fresh()->status)->toBe('in_sviluppo');
});

it('shows the Italian error and keeps the state when the transition is invalid', function () {
    $project = Project::factory()->create();

    detail($project)
        ->call('changeStatus', 'pronto_da_sviluppare')
        ->assertNotified(Notification::make()->title('Lo stato "Pronto da sviluppare" viene impostato solo in automatico')->danger());

    expect($project->fresh()->status)->toBe('in_preparazione');
});

it('does not make the ready state clickable', function () {
    $project = Project::factory()->create();

    $html = detail($project)->html();

    expect($html)->not->toContain("changeStatus('pronto_da_sviluppare')")
        ->and($html)->toContain("changeStatus('in_sviluppo')");
});

it('ticking the last requirement moves the project to ready', function () {
    $project = Project::factory()->create();
    reqFor($project, 'Informazioni', true, 1);
    $last = reqFor($project, 'Struttura', false, 2);

    detail($project)->call('toggleRequirement', $last->id);

    expect($project->fresh()->status)->toBe('pronto_da_sviluppare')
        ->and($last->fresh()->is_done)->toBeTrue()
        ->and($last->fresh()->done_by)->toBe($this->user->id);
});

it('shows the ready state after ticking the last requirement', function () {
    $project = Project::factory()->create();
    $last = reqFor($project, 'Struttura');

    detail($project)
        ->call('toggleRequirement', $last->id)
        ->assertSeeHtml('pd-step cur')
        ->assertNotified(Notification::make()->title('Stato aggiornato: Pronto da sviluppare')->success());
});

it('unticks a requirement', function () {
    $project = Project::factory()->create();
    $req = reqFor($project, 'Informazioni', true);

    detail($project)->call('toggleRequirement', $req->id);

    expect($req->fresh()->is_done)->toBeFalse();
});

it('refuses a requirement of another project', function () {
    $project = Project::factory()->create();
    $other = Project::factory()->create();
    $req = reqFor($other, 'Altro');

    detail($project)->call('toggleRequirement', $req->id)->assertStatus(404);
    expect($req->fresh()->is_done)->toBeFalse();
});

it('sets a material status and ticks its requirement', function () {
    $project = Project::factory()->create();
    $req = reqFor($project, 'Materiale: foto');
    $material = Material::create(['project_id' => $project->id, 'requirement_id' => $req->id, 'kind' => MaterialKind::Foto, 'status' => 'atteso']);

    detail($project)->call('setMaterialStatus', $material->id, 'ricevuto');

    expect($material->fresh()->status)->toBe(MaterialStatus::Ricevuto)
        ->and($req->fresh()->is_done)->toBeTrue();
});

it('rejects an unknown material status and foreign materials', function () {
    $project = Project::factory()->create();
    $material = Material::create(['project_id' => $project->id, 'kind' => MaterialKind::Foto, 'status' => 'atteso']);
    $foreign = Material::create(['project_id' => Project::factory()->create()->id, 'kind' => MaterialKind::Foto, 'status' => 'atteso']);

    detail($project)->call('setMaterialStatus', $material->id, 'boh')->assertNotified();
    expect($material->fresh()->status)->toBe(MaterialStatus::Atteso);

    detail($project)->call('setMaterialStatus', $foreign->id, 'ricevuto')->assertStatus(404);
});

it('shows material files with a download link', function () {
    $project = Project::factory()->create();
    $material = Material::create(['project_id' => $project->id, 'kind' => MaterialKind::Testi, 'status' => 'ricevuto']);
    $att = $material->attachments()->create(['path' => "attachments/{$project->id}/x.docx", 'filename' => 'Testi_sito.docx', 'uploaded_by' => $this->user->id]);

    detail($project)
        ->assertSee('Testi_sito.docx')
        ->assertSee('1 file')
        ->assertSeeHtml(route('attachments.download', $att));
});

it('streams attachments to authenticated users only', function () {
    Storage::fake(Attachment::DISK);
    $project = Project::factory()->create();
    $material = Material::create(['project_id' => $project->id, 'kind' => MaterialKind::Testi, 'status' => 'ricevuto']);
    Storage::disk(Attachment::DISK)->put("attachments/{$project->id}/x.txt", 'ciao');
    $att = $material->attachments()->create(['path' => "attachments/{$project->id}/x.txt", 'filename' => 'Testi.txt', 'uploaded_by' => $this->user->id]);

    $this->get(route('attachments.download', $att))->assertOk()->assertDownload('Testi.txt');

    auth()->logout();
    $this->get(route('attachments.download', $att))->assertRedirect();
});

it('adds a comment shown in the discussion', function () {
    $project = Project::factory()->create();

    detail($project)->call('addComment', 'Ho caricato le traduzioni')->assertSee('Ho caricato le traduzioni')->assertSee('Sara Admin');

    expect($project->comments()->first())->user_id->toBe($this->user->id);
});

it('ignores a blank comment', function () {
    $project = Project::factory()->create();

    detail($project)->call('addComment', '   ');

    expect($project->comments()->count())->toBe(0);
});

it('adds a task assigned to a user', function () {
    $project = Project::factory()->create();
    $luca = User::factory()->create(['name' => 'Luca Rossi']);

    detail($project)->call('addTask', 'Pagina camere', $luca->id, '2026-11-20')->assertSee('Pagina camere');

    $task = $project->tasks()->first();
    expect($task->assignee_id)->toBe($luca->id)
        ->and($task->due_date->toDateString())->toBe('2026-11-20')
        ->and($task->created_by)->toBe($this->user->id);
});

it('rejects a task without a title or with an invalid assignee', function () {
    $project = Project::factory()->create();

    detail($project)->call('addTask', '  ', null, null);
    detail($project)->call('addTask', 'Titolo', 9999, null);

    expect($project->tasks()->count())->toBe(0);
});

it('toggles a task and splits open and completed tabs', function () {
    $project = Project::factory()->create();
    $task = Task::factory()->create(['project_id' => $project->id, 'title' => 'Home page']);

    $page = detail($project)->assertSeeHtml("task-{$task->id}")->call('toggleTask', $task->id);
    expect($task->fresh()->completed_at)->not->toBeNull();

    $page->assertDontSeeHtml("task-{$task->id}")->set('tab', 'completati')->assertSeeHtml("task-{$task->id}");

    $page->call('toggleTask', $task->id);
    expect($task->fresh()->completed_at)->toBeNull();
});

it('refuses to toggle a task of another project', function () {
    $project = Project::factory()->create();
    $foreign = Task::factory()->create();

    detail($project)->call('toggleTask', $foreign->id)->assertStatus(404);
    expect($foreign->fresh()->completed_at)->toBeNull();
});

it('groups checklist tasks under Messa online', function () {
    $project = Project::factory()->create(['status' => 'in_pubblicazione']);
    $template = ChecklistTemplate::factory()->create(['trigger_status' => 'in_pubblicazione']);
    $item = ChecklistTemplateItem::factory()->create(['checklist_template_id' => $template->id, 'title' => 'Configura DNS']);
    Task::factory()->create(['project_id' => $project->id, 'title' => 'Configura DNS', 'template_item_id' => $item->id]);
    Task::factory()->create(['project_id' => $project->id, 'title' => 'Libero']);

    detail($project)->assertSee('Messa online')->assertSee('Configura DNS')->assertSee('Libero');

    $plain = Project::factory()->create();
    Task::factory()->create(['project_id' => $plain->id, 'title' => 'Solo']);
    detail($plain)->assertDontSee('Messa online');
});

it('lists the status change in the activity timeline', function () {
    $project = Project::factory()->create();

    detail($project)->call('changeStatus', 'in_sviluppo')->assertSee('Sara Admin')->assertSee('ha cambiato lo stato in');
});
