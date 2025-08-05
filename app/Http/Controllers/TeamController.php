<?php

namespace App\Http\Controllers;

use App\Actions\Teams\ManageTeamMembers;
use App\Delegation\Workload;
use App\Delegation\WorkloadCalculator;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $this->organization($request);

        $teams = $organization->teams()
            ->with('lead:id,name')
            ->withCount('members')
            ->orderBy('name')
            ->get()
            ->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'lead' => $team->lead?->only('id', 'name'),
                'members_count' => $team->members_count,
            ]);

        return Inertia::render('teams/Index', [
            'teams' => $teams,
            'people' => $this->people($organization),
            'canCreate' => $this->user($request)->can('create', [Team::class, $organization]),
        ]);
    }

    public function store(Request $request, ManageTeamMembers $manageMembers): RedirectResponse
    {
        $organization = $this->organization($request);
        $this->authorize('create', [Team::class, $organization]);

        $validated = $request->validate([
            ...$this->rules($organization),
            'lead_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $team = $organization->teams()->create(Arr::only($validated, ['name', 'description']));

        if ($validated['lead_id'] ?? null) {
            $manageMembers->appointLead($team, User::findOrFail($validated['lead_id']));
        }

        return to_route('teams.show', $team);
    }

    public function show(Request $request, Team $team, WorkloadCalculator $workloads): Response
    {
        $this->ensureInOrganization($request, $team);
        $user = $this->user($request);
        $team->load('lead:id,name,email', 'members:id,name,email');

        return Inertia::render('teams/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'lead' => $team->lead?->only('id', 'name', 'email'),
                'members' => $team->members->sortBy('name')->values()->map->only('id', 'name', 'email'),
            ],
            'people' => $this->people($team->organization),
            'workload' => $workloads->forTeam($team)->map(fn (Workload $workload) => $workload->toArray()),
            'can' => [
                'update' => $user->can('update', $team),
                'delete' => $user->can('delete', $team),
                'manageMembers' => $user->can('manageMembers', $team),
            ],
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $this->ensureInOrganization($request, $team);
        $this->authorize('update', $team);

        $team->update($request->validate($this->rules($team->organization, $team)));

        return to_route('teams.show', $team);
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        $this->ensureInOrganization($request, $team);
        $this->authorize('delete', $team);

        $team->delete();

        return to_route('teams.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Organization $organization, ?Team $team = null): array
    {
        return [
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('teams', 'name')->where('organization_id', $organization->id)->ignore($team),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Organization members who can be appointed or added to a team.
     *
     * @return list<array{id: int, name: string}>
     */
    private function people(Organization $organization): array
    {
        return $organization->members()->orderBy('name')->get(['users.id', 'name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->all();
    }
}
