<?php

namespace Database\Factories;

use App\Enums\DomainOwnership;
use App\Enums\DomainRole;
use App\Models\Client;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Domain>
 */
class DomainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'project_id' => null,
            'name' => fake()->unique()->domainName(),
            'role' => DomainRole::Secondario,
            'ownership' => DomainOwnership::Nostro,
        ];
    }
}
