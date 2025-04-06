<?php

namespace App\Actions\Invitations;

use App\Enums\OrganizationRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\InvitationSent;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SendInvitation
{
    public function handle(User $inviter, Organization $organization, string $email, OrganizationRole $role): Invitation
    {
        $email = Str::lower(trim($email));
        $inviterRole = $inviter->roleIn($organization);

        if (! $inviterRole?->canManage()) {
            throw ValidationException::withMessages(['email' => 'You cannot invite people to this organization.']);
        }

        if ($role === OrganizationRole::Owner && $inviterRole !== OrganizationRole::Owner) {
            throw ValidationException::withMessages(['role' => 'Only an owner can invite another owner.']);
        }

        if ($organization->members()->whereRaw('lower(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'This person is already a member.']);
        }

        if ($organization->invitations()->pending()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'This person already has a pending invitation.']);
        }

        $invitation = $organization->invitations()->create([
            'invited_by' => $inviter->id,
            'email' => $email,
            'role' => $role,
        ]);

        self::deliver($invitation);

        return $invitation;
    }

    public static function deliver(Invitation $invitation): void
    {
        Notification::route('mail', $invitation->email)->notify(new InvitationSent($invitation));
    }
}
