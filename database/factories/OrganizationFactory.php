<?php

namespace Database\Factories;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }

    /**
     * Attach a user with the given role and make it their current organization.
     */
    public function withMember(User $user, OrganizationRole $role = OrganizationRole::Member): static
    {
        return $this->afterCreating(function (Organization $organization) use ($user, $role) {
            $organization->members()->attach($user, ['role' => $role]);
            $user->switchOrganization($organization);
        });
    }

    public function ownedBy(User $user): static
    {
        return $this->withMember($user, OrganizationRole::Owner);
    }
}
