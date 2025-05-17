<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Mission;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
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
            'title' => rtrim(fake()->sentence(3), '.'),
            'estimate_hours' => fake()->optional()->numberBetween(1, 16),
        ];
    }

    public function status(TaskStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'blocked_reason' => $status === TaskStatus::Blocked ? 'Waiting for feedback' : null,
            'completed_at' => $status === TaskStatus::Done ? now() : null,
        ]);
    }
}
