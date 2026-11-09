<?php

use App\Enums\ProjectPriority;
use App\Filament\Pages\ProjectsBoard;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectRequirement;
use App\Models\SalesRep;
use App\Models\Task;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function boardProject(string $client, string $name, array $attrs = []): Project
{
    return Project::factory()->create([
        'client_id' => Client::factory()->create(['name' => $client])->id,
        'name' => $name,
    ] + $attrs);
}

it('renders the board at the root with demo-like data', function () {
    $p = boardProject('Hotel Rossi', 'Sito Hotel Rossi', ['owner_id' => $this->user->id]);
    ProjectRequirement::create(['project_id' => $p->id, 'label' => 'Informazioni', 'is_mandatory' => true, 'is_done' => true, 'sort' => 1]);
    ProjectRequirement::create(['project_id' => $p->id, 'label' => 'Struttura', 'is_mandatory' => true, 'is_done' => false, 'sort' => 2]);

    $this->get('/')->assertOk()->assertSee('Progetti')->assertSee('Hotel Rossi')->assertSee('Aggiungi progetto');

    Livewire::test(ProjectsBoard::class)
        ->assertSee('Sito Hotel Rossi')
        ->assertSee('In preparazione')
        ->assertSee('Assegnati a me')
        ->assertSee('In scadenza')
        ->assertSee('Materiale in ritardo');
});

it('filters by status counter', function () {
    boardProject('A', 'Progetto Dev', ['status' => 'in_sviluppo']);
    boardProject('B', 'Progetto Prep');

    Livewire::test(ProjectsBoard::class)
        ->set('status', 'in_sviluppo')
        ->assertSee('Progetto Dev')
        ->assertDontSee('Progetto Prep');
});

it('groups states with the same label across flows', function () {
    boardProject('A', 'Sito Prep');
    boardProject('B', 'Mod Prep', ['type' => 'modifica']);
    boardProject('C', 'Mod Pronta', ['type' => 'modifica', 'status' => 'pronto_da_modificare']);

    $c = Livewire::test(ProjectsBoard::class)->instance()->counters();
    $prep = collect($c)->firstWhere('label', 'In preparazione');
    expect($prep['count'])->toBe(2);
    expect(collect($c)->where('label', 'In preparazione'))->toHaveCount(1);
    expect(collect($c)->pluck('label'))->toContain('Pronto da sviluppare')->toContain('Pronto da modificare');

    Livewire::test(ProjectsBoard::class)
        ->set('status', 'in_preparazione')
        ->assertSee('Sito Prep')->assertSee('Mod Prep')->assertDontSee('Mod Pronta');
});

it('filters assigned to me', function () {
    boardProject('A', 'Mio', ['owner_id' => $this->user->id]);
    boardProject('B', 'Altrui', ['owner_id' => User::factory()->create()->id]);

    Livewire::test(ProjectsBoard::class)
        ->call('toggleMine')
        ->assertSet('owner', $this->user->id)
        ->assertSee('Mio')->assertDontSee('Altrui')
        ->call('toggleMine')
        ->assertSet('owner', null)
        ->assertSee('Altrui');
});

it('filters due soon and late material', function () {
    boardProject('A', 'Scade presto', ['due_date' => today()->addDays(3)]);
    boardProject('B', 'Scaduto', ['due_date' => today()->subDays(2)]);
    boardProject('C', 'Lontano', ['due_date' => today()->addDays(30)]);
    boardProject('D', 'Finito', ['due_date' => today()->subDays(2), 'status' => 'pubblicato']);
    $late = boardProject('E', 'Foto in ritardo');
    Material::create(['project_id' => $late->id, 'kind' => 'foto', 'status' => 'atteso', 'requested_at' => now()->subDays(9)]);
    $fresh = boardProject('F', 'Foto recente');
    Material::create(['project_id' => $fresh->id, 'kind' => 'foto', 'status' => 'atteso', 'requested_at' => now()->subDays(2)]);

    Livewire::test(ProjectsBoard::class)
        ->set('dueSoon', true)
        ->assertSee('Scade presto')->assertSee('Scaduto')
        ->assertDontSee('Lontano')->assertDontSee('Finito')->assertDontSee('Foto in ritardo');

    Livewire::test(ProjectsBoard::class)
        ->set('lateMaterial', true)
        ->assertSee('Foto in ritardo')->assertDontSee('Foto recente')->assertDontSee('Scaduto');
});

it('renders kanban columns of the sito flow by default', function () {
    boardProject('A', 'Nel kanban');

    $test = Livewire::test(ProjectsBoard::class)
        ->set('viewMode', 'kanban')
        ->assertSet('flow', 'sito')
        ->assertSee('Sito pubblicato')
        ->assertSee('Nel kanban');
    expect(substr_count($test->html(), 'class="pb-col"'))->toBe(7);

    $test->set('flow', 'modifica')->assertDontSee('Nel kanban');
    expect(substr_count($test->html(), 'class="pb-col"'))->toBe(6);
});

it('renders list view', function () {
    boardProject('A', 'Nella lista');

    Livewire::test(ProjectsBoard::class)->set('viewMode', 'list')->assertSee('Nella lista')->assertSee('Responsabile');
});

it('shows Nessun responsabile for a project without owner', function () {
    boardProject('A', 'Orfano');

    Livewire::test(ProjectsBoard::class)->assertSee('Nessun responsabile');
});

it('searches by client name', function () {
    boardProject('Hotel Rossi', 'Sito uno');
    boardProject('Panificio Verdi', 'Sito due');

    Livewire::test(ProjectsBoard::class)
        ->set('search', 'Rossi')
        ->assertSee('Sito uno')->assertDontSee('Sito due');
});

it('does not run N+1 queries on the grid', function () {
    foreach (range(1, 8) as $i) {
        $p = boardProject("Cliente $i", "Prog $i", ['owner_id' => $this->user->id]);
        ProjectRequirement::create(['project_id' => $p->id, 'label' => 'x', 'is_mandatory' => true, 'is_done' => false, 'sort' => 1]);
    }
    $test = Livewire::test(ProjectsBoard::class);
    DB::flushQueryLog();
    DB::enableQueryLog();
    $test->call('$refresh');
    expect(count(DB::getQueryLog()))->toBeLessThan(20);
});

it('shows to-do progress and the primary domain on the card', function () {
    $p = boardProject('Hotel Rossi', 'Sito Rossi');
    $domain = Domain::factory()->create(['client_id' => $p->client_id, 'project_id' => $p->id, 'name' => 'hotelrossi.test']);
    $p->update(['primary_domain_id' => $domain->id]);
    Task::factory()->count(3)->create(['project_id' => $p->id, 'completed_at' => null]);
    Task::factory()->create(['project_id' => $p->id, 'completed_at' => now()]);

    Livewire::test(ProjectsBoard::class)
        ->assertSee('1/4 to-do')
        ->assertSee('hotelrossi.test')
        ->assertSee('Manuale');
});

it('has a late-material helper that agrees with the scope at the boundary', function () {
    $today = CarbonImmutable::parse('2026-11-17 10:00:00');
    $mk = function (string $requestedAt) {
        $p = boardProject('C'.$requestedAt, 'P'.$requestedAt);
        Material::create(['project_id' => $p->id, 'kind' => 'foto', 'status' => 'atteso', 'requested_at' => $requestedAt]);

        return $p;
    };
    $late = $mk('2026-11-10 23:59:59');
    $notLate = $mk('2026-11-11 00:00:00');

    expect($late->load('materials')->hasLateMaterial($today))->toBeTrue();
    expect($notLate->load('materials')->hasLateMaterial($today))->toBeFalse();
    expect(Project::materialOverdue($today)->pluck('id')->all())->toBe([$late->id]);
});

it('sorts projects by name, due date or status', function () {
    boardProject('Cliente A', 'Zeta', ['due_date' => today()->addDays(1), 'status' => 'in_preparazione']);
    boardProject('Cliente B', 'Alfa', ['due_date' => today()->addDays(9), 'status' => 'in_sviluppo']);
    boardProject('Cliente C', 'Emme', ['due_date' => null, 'status' => 'pubblicato']);

    $names = fn ($page) => collect($page->viewData('projects'))->pluck('name')->all();

    expect($names(Livewire::test(ProjectsBoard::class)))->toBe(['Zeta', 'Alfa', 'Emme'])
        ->and($names(Livewire::test(ProjectsBoard::class)->set('sort', 'alfabetico')))->toBe(['Alfa', 'Emme', 'Zeta'])
        ->and($names(Livewire::test(ProjectsBoard::class)->set('sort', 'stato')))->toBe(['Zeta', 'Alfa', 'Emme']);
});

it('shows the payment status on the card', function () {
    boardProject('Hotel Rossi', 'Sito Rossi', ['payment_status' => 'acconto']);

    Livewire::test(ProjectsBoard::class)->assertSee('Acconto pagato')->assertSeeHtml('pay-acc');
});

it('sorts by priority, then by due date', function () {
    boardProject('A', 'Normale vicino', ['priority' => 'normale', 'due_date' => today()->addDay()]);
    boardProject('B', 'Urgente', ['priority' => 'urgente', 'due_date' => today()->addDays(20)]);
    boardProject('C', 'Bassa', ['priority' => 'bassa', 'due_date' => today()]);
    boardProject('D', 'Alta', ['priority' => 'alta']);
    boardProject('E', 'Normale lontano', ['priority' => 'normale', 'due_date' => today()->addDays(5)]);

    $names = collect(Livewire::test(ProjectsBoard::class)->set('sort', 'priorita')->viewData('projects'))->pluck('name')->all();

    expect($names)->toBe(['Urgente', 'Alta', 'Normale vicino', 'Normale lontano', 'Bassa']);
});

it('filters by sales rep and changes priority from the list', function () {
    $rep = SalesRep::factory()->create(['name' => 'Marco Venditore']);
    $mine = boardProject('A', 'Del commerciale', ['sales_rep_id' => $rep->id]);
    boardProject('B', 'Senza commerciale');

    Livewire::test(ProjectsBoard::class)
        ->set('salesRep', $rep->id)
        ->assertSee('Del commerciale')
        ->assertDontSee('Senza commerciale')
        ->set('viewMode', 'list')
        ->call('setPriority', $mine->id, 'urgente')
        ->call('setPriority', $mine->id, 'inventata');

    expect($mine->fresh()->priority)->toBe(ProjectPriority::Urgente);
});
