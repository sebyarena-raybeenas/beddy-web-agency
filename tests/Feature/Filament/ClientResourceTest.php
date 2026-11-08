<?php

use App\Enums\DomainRole;
use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\Clients\RelationManagers\DomainsRelationManager;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists clients', function () {
    $clients = Client::factory()->count(2)->create();

    Livewire::test(ListClients::class)->assertCanSeeTableRecords($clients);
});

it('creates a client with a contact and a domain', function () {
    Livewire::test(CreateClient::class)
        ->fillForm([
            'name' => 'Rossi Srl',
            'contacts' => [[
                'name' => 'Mario Rossi',
                'phones' => [['value' => '333 1'], ['value' => '06 2'], ['value' => '']],
                'emails' => [['value' => 'mario@rossi.test'], ['value' => 'info@rossi.test']],
                'is_primary' => true,
            ]],
            'domains' => [['name' => 'https://www.Rossi.it/', 'ownership' => 'suo', 'role' => 'principale']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $client = Client::where('name', 'Rossi Srl')->firstOrFail();
    $contact = $client->contacts->first();

    expect($client->contacts)->toHaveCount(1)
        ->and($contact->name)->toBe('Mario Rossi')
        ->and($contact->phones)->toBe(['333 1', '06 2'])
        ->and($contact->emails)->toBe(['mario@rossi.test', 'info@rossi.test'])
        ->and($client->domains->pluck('name')->all())->toBe(['rossi.it'])
        ->and($client->domains->first()->role)->toBe(DomainRole::Principale)
        ->and($client->created_by)->toBe(auth()->id());
});

it('requires a name', function () {
    Livewire::test(CreateClient::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('refuses two principale domains on client creation', function () {
    Livewire::test(CreateClient::class)
        ->fillForm([
            'name' => 'Rossi Srl',
            'contacts' => [],
            'domains' => [
                ['name' => 'rossi.it', 'role' => 'principale'],
                ['name' => 'rossi.com', 'role' => 'principale'],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['domains']);

    expect(Client::where('name', 'Rossi Srl')->exists())->toBeFalse();
});

function clientDomains(Client $client)
{
    return Livewire::test(DomainsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class]);
}

it('adds a principale domain without confirmation when the client has none', function () {
    $client = Client::factory()->create();

    clientDomains($client)
        ->callAction(TestAction::make('create')->table(), ['name' => 'rossi.it', 'role' => 'principale'])
        ->assertHasNoActionErrors();

    expect($client->domains()->first()->role)->toBe(DomainRole::Principale);
});

it('asks for confirmation before replacing the principale domain', function () {
    $client = Client::factory()->create();
    $old = Domain::factory()->for($client)->create(['name' => 'vecchio.it', 'role' => DomainRole::Principale]);

    clientDomains($client)
        ->callAction(TestAction::make('create')->table(), ['name' => 'nuovo.it', 'role' => 'principale'])
        ->assertHasActionErrors(['confirm_primary' => 'accepted']);

    expect(Domain::where('name', 'nuovo.it')->exists())->toBeFalse()
        ->and($old->fresh()->role)->toBe(DomainRole::Principale);
});

it('makes the new domain principale and the others secondario once confirmed', function () {
    $client = Client::factory()->create();
    $project = Project::factory()->for($client)->create();
    $old = Domain::factory()->for($client)->create(['name' => 'vecchio.it', 'role' => DomainRole::Principale, 'project_id' => $project->id]);
    $other = Domain::factory()->for($client)->create(['name' => 'altro.it']);
    $project->update(['primary_domain_id' => $old->id]);

    clientDomains($client)
        ->callAction(TestAction::make('create')->table(), ['name' => 'nuovo.it', 'role' => 'principale', 'confirm_primary' => true])
        ->assertHasNoActionErrors();

    expect(Domain::where('name', 'nuovo.it')->firstOrFail()->role)->toBe(DomainRole::Principale)
        ->and($old->fresh()->role)->toBe(DomainRole::Secondario)
        ->and($other->fresh()->role)->toBe(DomainRole::Secondario)
        ->and($project->fresh()->primary_domain_id)->toBeNull()
        ->and($client->domains()->where('role', 'principale')->count())->toBe(1);
});

it('promotes an existing domain on edit after confirmation and demotes on request', function () {
    $client = Client::factory()->create();
    $old = Domain::factory()->for($client)->create(['name' => 'vecchio.it', 'role' => DomainRole::Principale]);
    $b = Domain::factory()->for($client)->create(['name' => 'b.it']);

    $rm = clientDomains($client);

    $rm->callAction(TestAction::make('edit')->table($b), ['name' => 'b.it', 'role' => 'principale', 'confirm_primary' => true])
        ->assertHasNoActionErrors();

    expect($b->fresh()->role)->toBe(DomainRole::Principale)
        ->and($old->fresh()->role)->toBe(DomainRole::Secondario);

    // Editing the principale itself needs no confirmation; turning it secondario is allowed.
    $rm->callAction(TestAction::make('edit')->table($b), ['name' => 'b.it', 'role' => 'secondario'])
        ->assertHasNoActionErrors();

    expect($b->fresh()->role)->toBe(DomainRole::Secondario);
});

it('saves several phones and emails on a contact', function () {
    $client = Client::factory()->create();

    Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
        ->callAction(TestAction::make('create')->table(), [
            'name' => 'Giulia Ferri',
            'phones' => [['value' => '333 111'], ['value' => '06 222']],
            'emails' => [['value' => 'g@rossi.it'], ['value' => 'amministrazione@rossi.it']],
        ])
        ->assertHasNoActionErrors();

    $contact = $client->contacts()->firstOrFail();

    expect($contact->phones)->toBe(['333 111', '06 222'])
        ->and($contact->emails)->toBe(['g@rossi.it', 'amministrazione@rossi.it']);
});

it('rejects an invalid email among the contact emails', function () {
    $client = Client::factory()->create();

    Livewire::test(ContactsRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
        ->callAction(TestAction::make('create')->table(), ['name' => 'Giulia', 'phones' => [], 'emails' => [['value' => 'ok@rossi.it'], ['value' => 'non-valida']]])
        ->assertHasActionErrors();

    expect($client->contacts()->count())->toBe(0);
});
