<?php

namespace Database\Factories;

use App\Enums\ProjectType;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'name' => 'Sito '.fake()->unique()->company(),
            'type' => ProjectType::Nuovo,
        ];
    }
}
