<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->belongsToOrganization($team->organization);
    }

    public function create(User $user, Organization $organization): bool
    {
        return (bool) $user->roleIn($organization)?->canManage();
    }

    public function update(User $user, Team $team): bool
    {
        return $this->create($user, $team->organization);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->create($user, $team->organization);
    }

    /**
     * Organization managers and the team lead decide who works in the team.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        return $team->isLedBy($user) || $this->create($user, $team->organization);
    }
}
