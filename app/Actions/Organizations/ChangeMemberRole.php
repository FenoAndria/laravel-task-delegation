<?php

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeMemberRole
{
    public function handle(User $actor, Organization $organization, User $member, OrganizationRole $role): void
    {
        DB::transaction(function () use ($actor, $organization, $member, $role) {
            $actorRole = $actor->roleIn($organization);
            $currentRole = $member->roleIn($organization);

            if (! $actorRole?->canManage() || $currentRole === null) {
                throw ValidationException::withMessages(['role' => 'You cannot change this member\'s role.']);
            }

            $touchesOwnership = $role === OrganizationRole::Owner || $currentRole === OrganizationRole::Owner;

            if ($touchesOwnership && $actorRole !== OrganizationRole::Owner) {
                throw ValidationException::withMessages(['role' => 'Only an owner can grant or revoke ownership.']);
            }

            if ($currentRole === OrganizationRole::Owner && $role !== OrganizationRole::Owner && $organization->ownerCount() === 1) {
                throw ValidationException::withMessages(['role' => 'An organization needs at least one owner.']);
            }

            $organization->members()->updateExistingPivot($member->id, ['role' => $role]);
        });
    }
}
