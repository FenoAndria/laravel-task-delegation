<?php

namespace App\Broadcasting;

use App\Models\Mission;
use App\Models\User;

class MissionChannel
{
    /**
     * Members of the mission's organization may follow it; who is present is shared.
     *
     * @return array{id: int, name: string}|false
     */
    public function join(User $user, Mission $mission): array|false
    {
        if (! $user->belongsToOrganization($mission->organization)) {
            return false;
        }

        return ['id' => $user->id, 'name' => $user->name];
    }
}
