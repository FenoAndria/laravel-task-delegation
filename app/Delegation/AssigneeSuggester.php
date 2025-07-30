<?php

namespace App\Delegation;

use App\Enums\TaskStatus;
use App\Models\Mission;
use Illuminate\Support\Collection;

/**
 * Suggests who should take the next task of a mission, with a reason anyone can check.
 */
class AssigneeSuggester
{
    public function __construct(private WorkloadCalculator $workloads) {}

    /**
     * @return Collection<int, array{user: array{id: int, name: string}, level: string, reason: string}>
     */
    public function suggest(Mission $mission, int $limit = 3): Collection
    {
        $workloads = $this->workloads->forTeam($mission->team);

        if ($workloads->isEmpty()) {
            return collect();
        }

        $onMission = $mission->tasks()
            ->whereNotNull('assignee_id')
            ->where('status', '!=', TaskStatus::Cancelled)
            ->pluck('assignee_id')
            ->countBy();

        $doneInTeam = $mission->team->missions()
            ->join('tasks', 'tasks.mission_id', '=', 'missions.id')
            ->where('tasks.status', TaskStatus::Done)
            ->where('tasks.completed_at', '>=', now()->subDays(90))
            ->pluck('tasks.assignee_id')
            ->countBy();

        return $workloads
            ->map(fn (Workload $workload) => [
                'workload' => $workload,
                // Lower is better: load first, overdue work weighs heavily, context on the mission helps a little.
                'score' => $workload->ratio()
                    + 0.25 * $workload->overdue
                    - 0.05 * min($onMission->get($workload->user->id, 0), 3),
            ])
            ->sortBy('score')
            ->take($limit)
            ->map(fn (array $candidate) => [
                'user' => $candidate['workload']->user->only('id', 'name'),
                'level' => $candidate['workload']->level(),
                'reason' => $this->reason(
                    $candidate['workload'],
                    $onMission->get($candidate['workload']->user->id, 0),
                    $doneInTeam->get($candidate['workload']->user->id, 0),
                ),
            ])
            ->values();
    }

    private function reason(Workload $workload, int $onMission, int $doneInTeam): string
    {
        $parts = [$workload->openTasks === 0
            ? 'No open work'
            : "{$workload->hours}h of open work ({$workload->toArray()['percent']}% of capacity)"];

        if ($workload->overdue > 0) {
            $parts[] = "{$workload->overdue} overdue";
        }

        if ($onMission > 0) {
            $parts[] = 'already on this mission';
        } elseif ($doneInTeam > 0) {
            $parts[] = "{$doneInTeam} tasks delivered in this team lately";
        }

        return implode(' · ', $parts);
    }
}
