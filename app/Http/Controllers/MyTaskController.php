<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Presenters\TaskPresenter;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyTaskController extends Controller
{
    /**
     * Everything assigned to the user in the current organization.
     */
    public function index(Request $request): Response
    {
        $user = $this->user($request);

        $tasks = $this->organization($request)->tasks()
            ->where('assignee_id', $user->id)
            ->where(fn ($query) => $query
                ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Blocked])
                ->orWhere('completed_at', '>=', now()->subDays(14)))
            ->with('mission:id,organization_id,title,team_id,status', 'mission.team:id,organization_id,name,lead_id', 'assignee:id,name')
            ->orderByRaw('due_date is null, due_date')
            ->get();

        $groups = collect([TaskStatus::InProgress, TaskStatus::Todo, TaskStatus::Blocked, TaskStatus::Done])
            ->map(fn (TaskStatus $status) => [
                'status' => $status->value,
                'label' => $status === TaskStatus::Done ? 'Done recently' : $status->label(),
                'tasks' => $tasks->where('status', $status)->values()->map(fn (Task $task) => [
                    ...TaskPresenter::present($task, $user),
                    'mission' => $task->mission->only('id', 'title'),
                    'team' => $task->mission->team->name,
                ]),
            ]);

        return Inertia::render('tasks/Mine', ['groups' => $groups]);
    }
}
