<?php

namespace App\Domain\Clients;

use App\Enums\DomainRole;
use App\Enums\Origin;
use App\Models\Client;
use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Imports client master data only (client, contacts, domains). It never creates or links projects.
 * A client already present with the same VAT number (or, without one, the same name) is skipped.
 */
class ClientImporter
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{created: list<string>, skipped: list<string>}
     *
     * @throws ValidationException when a row is malformed (nothing is imported)
     */
    public function import(array $rows, Origin $origin = Origin::Asana): array
    {
        Validator::make(['rows' => $rows], [
            'rows' => ['required', 'array'],
            'rows.*.name' => ['required', 'string', 'max:255'],
            'rows.*.legal_name' => ['nullable', 'string', 'max:255'],
            'rows.*.vat_number' => ['nullable', 'string', 'max:255'],
            'rows.*.address' => ['nullable', 'string', 'max:255'],
            'rows.*.contacts' => ['nullable', 'array'],
            'rows.*.contacts.*.name' => ['required', 'string', 'max:255'],
            'rows.*.contacts.*.role' => ['nullable', 'string', 'max:255'],
            'rows.*.contacts.*.phones' => ['nullable', 'array'],
            'rows.*.contacts.*.phones.*' => ['string', 'max:255'],
            'rows.*.contacts.*.emails' => ['nullable', 'array'],
            'rows.*.contacts.*.emails.*' => ['email', 'max:255'],
            'rows.*.domains' => ['nullable', 'array'],
            // A domain is a plain name (the first one becomes principale) or {name, role}.
            'rows.*.domains.*' => [function (string $attribute, mixed $value, \Closure $fail): void {
                $name = is_array($value) ? ($value['name'] ?? null) : $value;
                $role = is_array($value) ? ($value['role'] ?? null) : null;

                if (! is_string($name) || $name === '' || mb_strlen($name) > 255) {
                    $fail('Dominio non valido.');
                } elseif ($role !== null && DomainRole::tryFrom($role) === null) {
                    $fail('Ruolo del dominio non valido.');
                }
            }],
        ])->validate();

        $result = ['created' => [], 'skipped' => []];

        DB::transaction(function () use ($rows, $origin, &$result) {
            foreach ($rows as $row) {
                if ($this->exists($row)) {
                    $result['skipped'][] = $row['name'];

                    continue;
                }

                $client = Client::create([
                    'name' => $row['name'],
                    'legal_name' => $row['legal_name'] ?? null,
                    'vat_number' => $row['vat_number'] ?? null,
                    'address' => $row['address'] ?? null,
                    'origin' => $origin,
                ]);

                foreach (array_values($row['contacts'] ?? []) as $i => $contact) {
                    $client->contacts()->create([
                        'name' => $contact['name'],
                        'role' => $contact['role'] ?? null,
                        'phones' => array_values($contact['phones'] ?? []),
                        'emails' => array_values($contact['emails'] ?? []),
                        'is_primary' => $i === 0,
                    ]);
                }

                foreach (array_values($row['domains'] ?? []) as $i => $domain) {
                    $name = is_array($domain) ? $domain['name'] : $domain;
                    $role = is_array($domain) && isset($domain['role'])
                        ? DomainRole::from($domain['role'])
                        : ($i === 0 ? DomainRole::Principale : DomainRole::Secondario);

                    // A domain already registered elsewhere is left alone rather than failing the import.
                    if (Domain::where('name', Domain::normalizeName($name))->exists()) {
                        continue;
                    }

                    $client->domains()->create(['name' => $name, 'role' => $role]);
                }

                $result['created'][] = $row['name'];
            }
        });

        return $result;
    }

    /** @param  array<string, mixed>  $row */
    private function exists(array $row): bool
    {
        $vat = trim((string) ($row['vat_number'] ?? ''));

        return $vat !== ''
            ? Client::where('vat_number', $vat)->exists()
            : Client::where('name', $row['name'])->exists();
    }
}
