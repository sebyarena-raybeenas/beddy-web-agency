<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'commentable_type' => (new Project)->getMorphClass(),
            'commentable_id' => Project::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }
}
