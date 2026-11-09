<?php

namespace Database\Factories;

use App\Models\SalesRep;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalesRep> */
class SalesRepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
            'email' => fake()->safeEmail(),
            'phone' => null,
        ];
    }
}
