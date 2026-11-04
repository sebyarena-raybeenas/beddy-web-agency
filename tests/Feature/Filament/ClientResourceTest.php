<?php

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Models\Client;
use App\Models\User;
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
            'hosting_status' => 'nostro',
            'contacts' => [['name' => 'Mario Rossi', 'email' => 'mario@rossi.test', 'is_primary' => true]],
            'domains' => [['name' => 'https://www.Rossi.it/', 'ownership' => 'suo']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $client = Client::where('name', 'Rossi Srl')->firstOrFail();

    expect($client->contacts)->toHaveCount(1)
        ->and($client->contacts->first()->name)->toBe('Mario Rossi')
        ->and($client->domains->pluck('name')->all())->toBe(['rossi.it'])
        ->and($client->created_by)->toBe(auth()->id());
});

it('requires a name', function () {
    Livewire::test(CreateClient::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});
