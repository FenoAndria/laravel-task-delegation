<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Invitations\SendInvitation;
use App\Enums\InvitationStatus;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function store(Request $request, SendInvitation $sendInvitation): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::enum(OrganizationRole::class)],
        ]);

        $sendInvitation->handle(
            $this->user($request),
            $this->organization($request),
            $validated['email'],
            OrganizationRole::from($validated['role']),
        );

        return to_route('members.index');
    }

    /**
     * Send the invitation again with a fresh expiry date.
     */
    public function resend(Request $request, Invitation $invitation): RedirectResponse
    {
        $this->authorizeInvitation($request, $invitation);

        $invitation->update(['expires_at' => now()->addDays(Invitation::VALID_FOR_DAYS)]);
        SendInvitation::deliver($invitation);

        return to_route('members.index');
    }

    public function destroy(Request $request, Invitation $invitation): RedirectResponse
    {
        $this->authorizeInvitation($request, $invitation);

        $invitation->update(['status' => InvitationStatus::Cancelled, 'responded_at' => now()]);

        return to_route('members.index');
    }

    private function authorizeInvitation(Request $request, Invitation $invitation): void
    {
        $organization = $this->organization($request);

        abort_unless($invitation->organization_id === $organization->id, 404);
        abort_unless($invitation->status === InvitationStatus::Pending, 409);
        $this->authorize('manageMembers', $organization);
    }
}
