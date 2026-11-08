<?php

use App\Enums\DomainOwnership;
use App\Enums\DomainRole;
use App\Enums\HostingStatus;
use App\Enums\Origin;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Domain;
use Illuminate\Validation\ValidationException;

it('normalizes domain names', function () {
    $domain = Domain::factory()->create(['name' => 'https://www.Esempio.it/']);

    expect($domain->name)->toBe('esempio.it')
        ->and($domain->fresh()->name)->toBe('esempio.it');
});

it('rejects a duplicate domain', function () {
    Domain::factory()->create(['name' => 'esempio.it']);

    try {
        Domain::factory()->create(['name' => 'HTTP://WWW.esempio.it']);
        $this->fail('Expected ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors()['name'][0])->toBe('Questo dominio è già registrato');
    }

    expect(Domain::count())->toBe(1);
});

it('allows updating a domain without tripping the duplicate rule', function () {
    $domain = Domain::factory()->create(['name' => 'esempio.it']);
    $domain->update(['role' => DomainRole::Principale]);

    expect($domain->fresh()->role)->toBe(DomainRole::Principale);
});

it('keeps one primary contact per client', function () {
    $client = Client::factory()->create();
    $other = Client::factory()->create();
    $first = ClientContact::factory()->for($client)->create(['is_primary' => true]);
    $otherPrimary = ClientContact::factory()->for($other)->create(['is_primary' => true]);
    $second = ClientContact::factory()->for($client)->create(['is_primary' => true]);

    expect($first->fresh()->is_primary)->toBeFalse()
        ->and($second->fresh()->is_primary)->toBeTrue()
        ->and($otherPrimary->fresh()->is_primary)->toBeTrue();
});

it('exposes contacts and domains relations and defaults', function () {
    $client = Client::factory()->create();
    ClientContact::factory()->for($client)->create();
    Domain::factory()->for($client)->create();

    expect($client->contacts)->toHaveCount(1)
        ->and($client->domains)->toHaveCount(1)
        ->and($client->origin)->toBe(Origin::Manuale)
        ->and($client->domains->first()->role)->toBe(DomainRole::Secondario)
        ->and(HostingStatus::DaAttivare->label())->toBe('Da attivare')
        ->and(DomainOwnership::DaAcquistare->label())->toBe('Da acquistare');
});
