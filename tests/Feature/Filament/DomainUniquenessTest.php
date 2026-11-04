<?php

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\RelationManagers\DomainsRelationManager as ClientDomains;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\DomainsRelationManager as ProjectDomains;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Domain::factory()->create(['name' => 'esempio.it']);
});

it('rejects a duplicate domain in the client form, however it is spelled', function () {
    Livewire::test(CreateClient::class)
        ->fillForm([
            'name' => 'Rossi Srl',
            'domains' => [['name' => 'https://WWW.Esempio.it/']],
        ])
        ->call('create')
        ->assertHasFormErrors(['domains.0.name' => 'Questo dominio è già registrato']);

    expect(Domain::count())->toBe(1)->and(Client::where('name', 'Rossi Srl')->exists())->toBeFalse();
});

it('rejects a duplicate domain in the client domains relation', function () {
    $client = Client::factory()->create();

    Livewire::test(ClientDomains::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
        ->callAction(TestAction::make('create')->table(), ['name' => 'https://WWW.Esempio.it/'])
        ->assertHasActionErrors(['name' => 'Questo dominio è già registrato']);

    expect(Domain::count())->toBe(1);
});

it('allows editing a client domain without tripping over itself', function () {
    $client = Client::factory()->create();
    $domain = Domain::factory()->create(['client_id' => $client->id, 'name' => 'mio.it']);

    Livewire::test(ClientDomains::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
        ->callAction(TestAction::make('edit')->table($domain), ['name' => 'https://www.Mio.it/'])
        ->assertHasNoActionErrors();

    expect($domain->fresh()->name)->toBe('mio.it');
});

it('rejects a duplicate domain in the project domains relation', function () {
    $project = Project::factory()->create();

    Livewire::test(ProjectDomains::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['name' => 'https://WWW.Esempio.it/'])
        ->assertHasActionErrors(['name' => 'Questo dominio è già registrato']);

    expect(Domain::count())->toBe(1);
});
