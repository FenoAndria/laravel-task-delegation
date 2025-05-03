<?php

namespace Database\Factories;

use App\Enums\MissionStatus;
use App\Enums\Priority;
use App\Models\Mission;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mission>
 */
class MissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'organization_id' => fn (array $attributes) => Team::find($attributes['team_id'])?->organization_id,
            'title' => rtrim(fake()->sentence(4), '.'),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(Priority::cases()),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
        ];
    }

    public function status(MissionStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'completed_at' => $status === MissionStatus::Completed ? now() : null,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['due_date' => now()->subDays(3)]);
    }
}
