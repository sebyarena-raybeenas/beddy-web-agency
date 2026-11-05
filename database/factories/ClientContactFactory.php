<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientContact>
 */
class ClientContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => fake()->name(),
            'role' => fake()->jobTitle(),
            'phones' => [fake()->phoneNumber()],
            'emails' => [fake()->unique()->safeEmail()],
            'is_primary' => false,
        ];
    }
}
