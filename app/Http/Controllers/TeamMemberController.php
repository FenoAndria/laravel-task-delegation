<?php

namespace App\Http\Controllers;

use App\Actions\Teams\ManageTeamMembers;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function store(Request $request, Team $team, ManageTeamMembers $manageMembers): RedirectResponse
    {
        $this->ensureInOrganization($request, $team);
        $this->authorize('manageMembers', $team);

        $validated = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);
        $manageMembers->add($team, User::findOrFail($validated['user_id']));

        return to_route('teams.show', $team);
    }

    public function destroy(Request $request, Team $team, User $member, ManageTeamMembers $manageMembers): RedirectResponse
    {
        $this->ensureInOrganization($request, $team);
        $this->authorize('manageMembers', $team);

        $manageMembers->remove($team, $member);

        return to_route('teams.show', $team);
    }

    public function updateLead(Request $request, Team $team, ManageTeamMembers $manageMembers): RedirectResponse
    {
        $this->ensureInOrganization($request, $team);
        $this->authorize('update', $team);

        $validated = $request->validate(['lead_id' => ['nullable', 'integer', 'exists:users,id']]);
        $lead = isset($validated['lead_id']) ? User::findOrFail($validated['lead_id']) : null;
        $manageMembers->appointLead($team, $lead);

        return to_route('teams.show', $team);
    }
}
