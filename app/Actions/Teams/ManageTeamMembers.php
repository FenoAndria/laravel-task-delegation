<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageTeamMembers
{
    public function add(Team $team, User $user): void
    {
        $this->ensureInOrganization($team, $user, 'user_id');

        if ($team->isLedBy($user)) {
            throw ValidationException::withMessages(['user_id' => 'The lead already belongs to the team.']);
        }

        $team->members()->syncWithoutDetaching([$user->id]);
    }

    public function remove(Team $team, User $user): void
    {
        $team->members()->detach($user->id);
    }

    /**
     * Appoint a new lead (or none). A member promoted to lead leaves the member list.
     */
    public function appointLead(Team $team, ?User $lead): void
    {
        if ($lead !== null) {
            $this->ensureInOrganization($team, $lead, 'lead_id');
        }

        DB::transaction(function () use ($team, $lead) {
            if ($lead !== null) {
                $team->members()->detach($lead->id);
            }

            $team->update(['lead_id' => $lead?->id]);
        });
    }

    private function ensureInOrganization(Team $team, User $user, string $field): void
    {
        if (! $user->belongsToOrganization($team->organization)) {
            throw ValidationException::withMessages([$field => 'This person is not a member of the organization.']);
        }
    }
}
