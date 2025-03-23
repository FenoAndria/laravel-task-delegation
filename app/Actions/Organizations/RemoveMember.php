<?php

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveMember
{
    /**
     * Remove a member from the organization. Members may also remove themselves (leave).
     */
    public function handle(User $actor, Organization $organization, User $member): void
    {
        DB::transaction(function () use ($actor, $organization, $member) {
            $actorRole = $actor->roleIn($organization);
            $memberRole = $member->roleIn($organization);
            $leaving = $actor->is($member);

            if ($memberRole === null || (! $leaving && ! $actorRole?->canManage())) {
                throw ValidationException::withMessages(['member' => 'You cannot remove this member.']);
            }

            if (! $leaving && $memberRole === OrganizationRole::Owner && $actorRole !== OrganizationRole::Owner) {
                throw ValidationException::withMessages(['member' => 'Only an owner can remove another owner.']);
            }

            if ($memberRole === OrganizationRole::Owner && $organization->ownerCount() === 1) {
                throw ValidationException::withMessages(['member' => 'An organization needs at least one owner.']);
            }

            $organization->members()->detach($member->id);

            if ($member->current_organization_id === $organization->id) {
                $member->forceFill(['current_organization_id' => $member->organizations()->value('organizations.id')])->save();
            }
        });
    }
}
