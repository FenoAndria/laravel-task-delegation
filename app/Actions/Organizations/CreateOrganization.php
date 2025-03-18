<?php

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOrganization
{
    /**
     * Create an organization owned by the user and make it their current one.
     */
    public function handle(User $owner, string $name): Organization
    {
        return DB::transaction(function () use ($owner, $name) {
            $organization = Organization::create(['name' => $name]);
            $organization->members()->attach($owner, ['role' => OrganizationRole::Owner]);
            $owner->switchOrganization($organization);

            return $organization;
        });
    }
}
