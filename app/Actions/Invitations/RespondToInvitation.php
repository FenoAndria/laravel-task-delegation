<?php

namespace App\Actions\Invitations;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RespondToInvitation
{
    public function accept(User $user, Invitation $invitation): void
    {
        $this->ensureCanRespond($user, $invitation);

        DB::transaction(function () use ($user, $invitation) {
            $organization = $invitation->organization;

            if (! $user->belongsToOrganization($organization)) {
                $organization->members()->attach($user, ['role' => $invitation->role]);
            }

            $invitation->update(['status' => InvitationStatus::Accepted, 'responded_at' => now()]);
            $user->switchOrganization($organization);
        });
    }

    public function decline(User $user, Invitation $invitation): void
    {
        $this->ensureCanRespond($user, $invitation);

        $invitation->update(['status' => InvitationStatus::Declined, 'responded_at' => now()]);
    }

    private function ensureCanRespond(User $user, Invitation $invitation): void
    {
        if (! $invitation->isOpen()) {
            throw ValidationException::withMessages(['invitation' => 'This invitation is no longer valid.']);
        }

        if (! $invitation->isFor($user)) {
            throw ValidationException::withMessages(['invitation' => "This invitation was sent to {$invitation->email}. Log in with that address to answer it."]);
        }
    }
}
