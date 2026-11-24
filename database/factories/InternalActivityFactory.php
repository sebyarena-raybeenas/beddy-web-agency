<?php

namespace Database\Factories;

use App\Models\InternalActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InternalActivity> */
class InternalActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Manutenzione '.fake()->word(),
            'owner_id' => null,
            'due_date' => null,
            'description' => null,
        ];
    }
}
