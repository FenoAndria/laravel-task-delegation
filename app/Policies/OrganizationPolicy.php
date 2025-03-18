<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $user->belongsToOrganization($organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return (bool) $user->roleIn($organization)?->canManage();
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->update($user, $organization);
    }
}
