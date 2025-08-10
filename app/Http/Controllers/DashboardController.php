<?php

namespace App\Http\Controllers;

use App\Delegation\RiskDetector;
use App\Delegation\Workload;
use App\Delegation\WorkloadCalculator;
use App\Enums\MissionStatus;
use App\Enums\TaskStatus;
use App\Models\Mission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The delegation overview: what is at risk, what is moving, who has room.
     */
    public function __invoke(Request $request, RiskDetector $risks, WorkloadCalculator $workloads): Response
    {
        $organization = $this->organization($request);
        $user = $this->user($request);

        $missions = $organization->missions()
            ->whereIn('status', [MissionStatus::Open, MissionStatus::InProgress])
            ->with('team:id,name')
            ->withCount([
                'tasks as tasks_total' => fn ($query) => $query->where('status', '!=', TaskStatus::Cancelled),
                'tasks as tasks_done' => fn ($query) => $query->where('status', TaskStatus::Done),
            ])
            ->orderByRaw('due_date is null, due_date')
            ->limit(8)
            ->get()
            ->map(fn (Mission $mission) => [
                'id' => $mission->id,
                'title' => $mission->title,
                'team' => $mission->team->name,
                'status' => $mission->status->value,
                'status_label' => $mission->status->label(),
                'due_date' => $mission->due_date?->toDateString(),
                'overdue' => $mission->isOverdue(),
                'progress' => ['done' => (int) $mission->getAttribute('tasks_done'), 'total' => (int) $mission->getAttribute('tasks_total')],
            ]);

        $myTasks = $organization->tasks()->where('assignee_id', $user->id);

        return Inertia::render('Dashboard', [
            'stats' => [
                'active_missions' => $organization->missions()->whereIn('status', [MissionStatus::Open, MissionStatus::InProgress])->count(),
                'my_open_tasks' => (clone $myTasks)->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Blocked])->count(),
                'blocked_tasks' => $organization->tasks()->where('status', TaskStatus::Blocked)->count(),
                'completed_this_month' => $organization->missions()->where('status', MissionStatus::Completed)->where('completed_at', '>=', now()->startOfMonth())->count(),
            ],
            'risks' => $risks->detect($organization)->take(8),
            'missions' => $missions,
            'workload' => $workloads->forPeople($organization, $organization->members()->orderBy('name')->get())
                ->sortByDesc(fn (Workload $workload) => $workload->ratio())
                ->values()
                ->map(fn (Workload $workload) => $workload->toArray()),
        ]);
    }
}
