<?php

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Appoint a lead, adding them to the organization if needed.
     */
    public function ledBy(User $lead): static
    {
        return $this->afterCreating(function (Team $team) use ($lead) {
            if (! $lead->belongsToOrganization($team->organization)) {
                $team->organization->members()->attach($lead, ['role' => OrganizationRole::Member]);
            }

            $team->update(['lead_id' => $lead->id]);
        });
    }
}
