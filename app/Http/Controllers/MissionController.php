<?php

namespace App\Http\Controllers;

use App\Actions\Missions\CompleteMission;
use App\Enums\MissionStatus;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Http\Presenters\TaskPresenter;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MissionController extends Controller
{
    private const FILTERS = ['active', 'completed', 'cancelled', 'all'];

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        $filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'active';
        $teamId = $request->integer('team') ?: null;

        $missions = $organization->missions()
            ->with('team:id,name')
            ->withCount([
                'tasks as tasks_total' => fn ($query) => $query->where('status', '!=', TaskStatus::Cancelled),
                'tasks as tasks_done' => fn ($query) => $query->where('status', TaskStatus::Done),
            ])
            ->when($teamId, fn ($query) => $query->where('team_id', $teamId))
            ->when($filter === 'active', fn ($query) => $query->whereIn('status', [MissionStatus::Open, MissionStatus::InProgress]))
            ->when($filter === 'completed', fn ($query) => $query->where('status', MissionStatus::Completed))
            ->when($filter === 'cancelled', fn ($query) => $query->where('status', MissionStatus::Cancelled))
            ->get()
            ->sortBy([
                fn (Mission $a, Mission $b) => $b->priority->weight() <=> $a->priority->weight(),
                fn (Mission $a, Mission $b) => ($a->due_date->timestamp ?? PHP_INT_MAX) <=> ($b->due_date->timestamp ?? PHP_INT_MAX),
            ])
            ->values()
            ->map(fn (Mission $mission) => $this->summary($mission));

        return Inertia::render('missions/Index', [
            'missions' => $missions,
            'filters' => ['status' => $filter, 'team' => $teamId],
            'teams' => $organization->teams()->orderBy('name')->get(['id', 'name']),
            'priorities' => $this->options(Priority::cases()),
            'canCreate' => $this->user($request)->can('create', [Mission::class, $organization]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $this->organization($request);
        $this->authorize('create', [Mission::class, $organization]);

        $mission = $organization->missions()->create([
            ...$request->validate($this->rules($organization)),
            'created_by' => $this->user($request)->id,
        ]);

        return to_route('missions.show', $mission);
    }

    public function show(Request $request, Mission $mission): Response
    {
        $this->ensureInOrganization($request, $mission);
        $mission->load('team:id,name,lead_id', 'team.lead:id,name', 'team.members:id,name', 'creator:id,name', 'tasks.assignee:id,name');
        $mission->tasks->each->setRelation('mission', $mission);
        $mission->setAttribute('tasks_total', $mission->tasks->where('status', '!=', TaskStatus::Cancelled)->count());
        $mission->setAttribute('tasks_done', $mission->tasks->where('status', TaskStatus::Done)->count());
        $user = $this->user($request);
        $team = $mission->team;

        return Inertia::render('missions/Show', [
            'mission' => [
                ...$this->summary($mission),
                'description' => $mission->description,
                'team_id' => $mission->team_id,
                'lead' => $mission->team->lead?->only('id', 'name'),
                'creator' => $mission->creator?->only('id', 'name'),
                'created_at' => $mission->created_at?->toDateString(),
                'completed_at' => $mission->completed_at?->toDateString(),
            ],
            'tasks' => $mission->tasks->map(fn (Task $task) => TaskPresenter::present($task, $user)),
            'assignees' => collect([$team->lead])->filter()->merge($team->members)->unique('id')
                ->map(fn (User $person) => $person->only('id', 'name'))->values(),
            'teams' => $mission->organization->teams()->orderBy('name')->get(['id', 'name']),
            'priorities' => $this->options(Priority::cases()),
            'can' => [
                'update' => $user->can('update', $mission),
                'delete' => $user->can('delete', $mission),
                'complete' => $user->can('complete', $mission) && CompleteMission::canComplete($mission),
                'manageTasks' => $user->can('manage', [Task::class, $mission]),
            ],
        ]);
    }

    public function update(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureInOrganization($request, $mission);
        $this->authorize('update', $mission);

        $mission->update($request->validate($this->rules($mission->organization)));

        return to_route('missions.show', $mission);
    }

    public function destroy(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureInOrganization($request, $mission);
        $this->authorize('delete', $mission);

        $mission->delete();

        return to_route('missions.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Organization $organization): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'team_id' => ['required', 'integer', Rule::exists(Team::class, 'id')->where('organization_id', $organization->id)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Mission $mission): array
    {
        return [
            'id' => $mission->id,
            'title' => $mission->title,
            'team' => $mission->team->only('id', 'name'),
            'status' => $mission->status->value,
            'status_label' => $mission->status->label(),
            'priority' => $mission->priority->value,
            'due_date' => $mission->due_date?->toDateString(),
            'overdue' => $mission->isOverdue(),
            'progress' => [
                'done' => (int) $mission->getAttribute('tasks_done'),
                'total' => (int) $mission->getAttribute('tasks_total'),
            ],
        ];
    }

    /**
     * @param  list<Priority>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function options(array $cases): array
    {
        return array_map(fn (Priority $case) => ['value' => $case->value, 'label' => $case->label()], $cases);
    }
}
