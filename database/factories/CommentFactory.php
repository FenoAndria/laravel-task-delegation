<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(),
            'organization_id' => fn (array $attributes) => Mission::find($attributes['mission_id'])?->organization_id,
            'body' => fake()->sentence(12),
        ];
    }
}
