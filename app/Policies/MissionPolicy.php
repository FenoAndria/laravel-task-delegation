<?php

namespace App\Policies;

use App\Models\Mission;
use App\Models\Organization;
use App\Models\User;

class MissionPolicy
{
    /**
     * Missions are visible to the whole organization.
     */
    public function view(User $user, Mission $mission): bool
    {
        return $user->belongsToOrganization($mission->organization);
    }

    public function create(User $user, Organization $organization): bool
    {
        return (bool) $user->roleIn($organization)?->canManage();
    }

    public function update(User $user, Mission $mission): bool
    {
        return $this->create($user, $mission->organization);
    }

    public function delete(User $user, Mission $mission): bool
    {
        return $this->create($user, $mission->organization);
    }

    /**
     * The team lead closes the mission they delivered; managers may too.
     */
    public function complete(User $user, Mission $mission): bool
    {
        return $mission->team->isLedBy($user) || $this->create($user, $mission->organization);
    }
}
