<?php

use App\Enums\DomainRole;
use App\Filament\Pages\ClientsIndex;
use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('renders the clients page at /clienti', function () {
    $client = Client::factory()->create(['name' => 'Hotel Rossi', 'has_assistance' => true]);
    ClientContact::create(['client_id' => $client->id, 'name' => 'Mario Rossi', 'is_primary' => true]);

    $this->get('/clienti')->assertOk()->assertSee('Hotel Rossi')->assertSee('Aggiungi cliente');

    Livewire::test(ClientsIndex::class)
        ->assertSee('Hotel Rossi')
        ->assertSee('Mario Rossi')
        ->assertSee(ClientResource::getUrl('create'))
        ->assertSee(ClientResource::getUrl('edit', ['record' => $client]));
});

it('searches by client name', function () {
    Client::factory()->create(['name' => 'Hotel Rossi']);
    Client::factory()->create(['name' => 'Villa Bianchi']);

    Livewire::test(ClientsIndex::class)
        ->set('search', 'rossi')
        ->assertSee('Hotel Rossi')
        ->assertDontSee('Villa Bianchi');
});

it('searches by domain, normalizing the input', function () {
    $a = Client::factory()->create(['name' => 'Hotel Rossi']);
    Client::factory()->create(['name' => 'Villa Bianchi']);
    Domain::create(['client_id' => $a->id, 'name' => 'rossihotel.it', 'role' => DomainRole::Principale]);

    Livewire::test(ClientsIndex::class)
        ->set('search', 'https://www.RossiHotel.it/')
        ->assertSee('Hotel Rossi')
        ->assertDontSee('Villa Bianchi');
});

it('shows active projects count, per-status counts and primary domains', function () {
    $client = Client::factory()->create(['name' => 'Hotel Rossi']);
    $p1 = Project::factory()->create(['client_id' => $client->id]);
    Project::factory()->create(['client_id' => $client->id]);
    $done = Project::factory()->create(['client_id' => $client->id]);
    $final = collect(app(\App\Domain\Workflow\WorkflowRegistry::class)->get('sito')->states())
        ->keys()->first(fn ($s) => app(\App\Domain\Workflow\WorkflowRegistry::class)->get('sito')->isFinal($s));
    $done->forceFill(['status' => $final])->save();
    Domain::create(['client_id' => $client->id, 'project_id' => $p1->id, 'name' => 'rossi.it', 'role' => DomainRole::Principale]);
    Domain::create(['client_id' => $client->id, 'project_id' => $p1->id, 'name' => 'secondo.it', 'role' => DomainRole::Secondario]);

    $component = Livewire::test(ClientsIndex::class);
    $component->assertSee('rossi.it')->assertDontSee('secondo.it')->assertSee($p1->statusLabel().' 2');

    $row = $component->instance()->clients()->first();
    expect($row->active_projects_count)->toBe(2);
});
