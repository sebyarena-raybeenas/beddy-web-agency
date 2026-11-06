<?php

namespace Database\Factories;

use App\Enums\HostingStatus;
use App\Enums\Origin;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'vat_number' => fake()->numerify('###########'),
            'address' => fake()->address(),
            'has_assistance' => false,
            'assistance_type' => null,
            'hosting_status' => fake()->randomElement(HostingStatus::cases()),
            'origin' => Origin::Manuale,
            'created_by' => null,
        ];
    }
}
