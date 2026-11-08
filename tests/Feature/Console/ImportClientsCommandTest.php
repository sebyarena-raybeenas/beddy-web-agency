<?php

use App\Enums\DomainRole;
use App\Enums\Origin;
use App\Models\Client;
use App\Models\Project;

function importFile(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'clients').'.json';
    file_put_contents($path, json_encode($rows));

    return $path;
}

$row = [
    'name' => 'Hotel Esempio',
    'legal_name' => 'Esempio Srl',
    'vat_number' => '01234567890',
    'address' => 'Via Roma 1, Catania',
    'contacts' => [[
        'name' => 'Mario Esempio',
        'role' => 'Referente contratto',
        'phones' => ['333 000', '095 000'],
        'emails' => ['mario@esempio.test', 'info@esempio.test'],
    ]],
    'domains' => ['https://www.esempio.test/'],
];

it('imports client master data with contacts and domains, without projects', function () use ($row) {
    $this->artisan('beddy:import-clients', ['file' => importFile([$row])])
        ->expectsOutputToContain('Clienti importati: 1')
        ->assertSuccessful();

    $client = Client::firstOrFail();
    $contact = $client->contacts()->firstOrFail();

    expect($client->name)->toBe('Hotel Esempio')
        ->and($client->origin)->toBe(Origin::Asana)
        ->and($client->legal_name)->toBe('Esempio Srl')
        ->and($client->vat_number)->toBe('01234567890')
        ->and($contact->is_primary)->toBeTrue()
        ->and($contact->phones)->toBe(['333 000', '095 000'])
        ->and($contact->emails)->toBe(['mario@esempio.test', 'info@esempio.test'])
        ->and($client->domains()->first()->name)->toBe('esempio.test')
        ->and($client->domains()->first()->role)->toBe(DomainRole::Principale)
        ->and($client->domains()->first()->project_id)->toBeNull()
        ->and(Project::count())->toBe(0);
});

it('skips a client already present with the same VAT number', function () use ($row) {
    $file = importFile([$row]);

    $this->artisan('beddy:import-clients', ['file' => $file])->assertSuccessful();
    $this->artisan('beddy:import-clients', ['file' => $file])
        ->expectsOutputToContain('Già presenti, saltati: 1')
        ->assertSuccessful();

    expect(Client::count())->toBe(1);
});

it('imports nothing when a row is invalid', function () use ($row) {
    $bad = array_merge($row, ['name' => 'Altro', 'vat_number' => '999', 'contacts' => [['name' => 'X', 'emails' => ['non-valida']]]]);

    $this->artisan('beddy:import-clients', ['file' => importFile([$row, $bad])])
        ->expectsOutputToContain('Importazione annullata')
        ->assertFailed();

    expect(Client::count())->toBe(0);
});

it('honours an explicit domain role', function () use ($row) {
    $row['domains'] = [['name' => 'uno.test', 'role' => 'secondario'], ['name' => 'due.test', 'role' => 'secondario']];

    $this->artisan('beddy:import-clients', ['file' => importFile([$row])])->assertSuccessful();

    expect(Client::firstOrFail()->domains()->where('role', 'principale')->count())->toBe(0)
        ->and(Client::firstOrFail()->domains()->count())->toBe(2);
});
