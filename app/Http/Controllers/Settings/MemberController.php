<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Organizations\ChangeMemberRole;
use App\Actions\Organizations\RemoveMember;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $this->organization($request);

        $members = Membership::query()
            ->whereBelongsTo($organization)
            ->with('user')
            ->get()
            ->sortBy('user.name')
            ->values()
            ->map(fn (Membership $membership) => [
                'id' => $membership->user->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'role' => $membership->role->value,
                'joined_at' => $membership->created_at?->toDateString(),
            ]);

        return Inertia::render('settings/Members', [
            'members' => $members,
            'roles' => collect(OrganizationRole::cases())->map(fn (OrganizationRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'canManage' => $this->user($request)->can('manageMembers', $organization),
        ]);
    }

    public function update(Request $request, User $member, ChangeMemberRole $changeMemberRole): RedirectResponse
    {
        $organization = $this->organization($request);
        $this->ensureMember($organization, $member);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(OrganizationRole::class)],
        ]);

        $changeMemberRole->handle($this->user($request), $organization, $member, OrganizationRole::from($validated['role']));

        return to_route('members.index');
    }

    public function destroy(Request $request, User $member, RemoveMember $removeMember): RedirectResponse
    {
        $organization = $this->organization($request);
        $this->ensureMember($organization, $member);

        $removeMember->handle($this->user($request), $organization, $member);

        return $this->user($request)->is($member) ? to_route('dashboard') : to_route('members.index');
    }

    private function ensureMember(Organization $organization, User $member): void
    {
        abort_unless($member->belongsToOrganization($organization), 404);
    }
}
