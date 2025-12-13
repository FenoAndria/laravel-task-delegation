<?php

namespace App\Delegation;

use App\Enums\TaskStatus;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class WorkloadCalculator
{
    /**
     * Workload of the given people, counting their open tasks in the whole organization.
     *
     * @param  Collection<int, User>  $people
     * @return Collection<int, Workload>
     */
    public function forPeople(Organization $organization, Collection $people): Collection
    {
        $defaultEstimate = (int) config('delegation.workload.default_estimate_hours');
        $capacity = (int) config('delegation.workload.weekly_capacity_hours');

        $tasks = $organization->tasks()
            ->whereIn('assignee_id', $people->pluck('id'))
            ->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::Blocked])
            ->get(['id', 'assignee_id', 'status', 'estimate_hours', 'due_date'])
            ->groupBy('assignee_id');

        return $people->map(function (User $person) use ($tasks, $defaultEstimate, $capacity) {
            /** @var Collection<int, Task> $open */
            $open = $tasks->get($person->id, collect());

            return new Workload(
                user: $person,
                openTasks: $open->count(),
                hours: (int) $open->sum(fn (Task $task) => $task->estimate_hours ?? $defaultEstimate),
                overdue: $open->filter(fn (Task $task) => $task->isOverdue())->count(),
                capacity: $capacity,
            );
        })->values();
    }

    /**
     * Workload of a team's lead and members.
     *
     * @return Collection<int, Workload>
     */
    public function forTeam(Team $team): Collection
    {
        $people = collect([$team->lead])->filter()->merge($team->members)->unique('id')->values();

        return $this->forPeople($team->organization, $people);
    }
}
