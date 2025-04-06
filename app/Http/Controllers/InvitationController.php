<?php

namespace App\Http\Controllers;

use App\Actions\Invitations\RespondToInvitation;
use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $invitation = $this->find($token);
        $user = $request->user();

        if ($user === null) {
            // Bring the invitee back here after they log in or register.
            redirect()->setIntendedUrl(route('invitations.show', $token));
        }

        return Inertia::render('invitations/Show', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'role' => $invitation->role->label(),
                'organization' => $invitation->organization->name,
                'inviter' => $invitation->inviter?->name,
                'state' => $this->state($invitation),
                'expires_at' => $invitation->expires_at->toDateString(),
            ],
            'isGuest' => $user === null,
            'isRecipient' => $user !== null && $invitation->isFor($user),
            'hasAccount' => User::whereRaw('lower(email) = ?', [$invitation->email])->exists(),
        ]);
    }

    public function accept(Request $request, string $token, RespondToInvitation $respond): RedirectResponse
    {
        $respond->accept($this->user($request), $this->find($token));

        return to_route('dashboard');
    }

    public function decline(Request $request, string $token, RespondToInvitation $respond): RedirectResponse
    {
        $respond->decline($this->user($request), $this->find($token));

        return to_route('dashboard');
    }

    private function find(string $token): Invitation
    {
        return Invitation::with('organization', 'inviter')->where('token', $token)->firstOrFail();
    }

    private function state(Invitation $invitation): string
    {
        if ($invitation->status === InvitationStatus::Pending && $invitation->isExpired()) {
            return 'expired';
        }

        return $invitation->status->value;
    }
}
