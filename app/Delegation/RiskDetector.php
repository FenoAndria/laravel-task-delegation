<?php

namespace App\Delegation;

use App\Enums\MissionStatus;
use App\Enums\TaskStatus;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Rule-based early warnings: what is likely to go wrong if nobody acts.
 */
class RiskDetector
{
    private const WEIGHT = ['high' => 3, 'medium' => 2, 'low' => 1];

    public function __construct(private WorkloadCalculator $workloads) {}

    /**
     * Risks of the whole organization, or of a single mission.
     *
     * @return Collection<int, array{level: string, kind: string, title: string, detail: string|null, url: string}>
     */
    public function detect(Organization $organization, ?Mission $only = null): Collection
    {
        $missions = $organization->missions()
            ->whereIn('status', [MissionStatus::Open, MissionStatus::InProgress])
            ->when($only, fn ($query) => $query->whereKey($only->id))
            ->with('team:id,name,lead_id', 'tasks.assignee:id,name')
            ->get();

        $risks = $missions->flatMap(fn (Mission $mission) => $this->missionRisks($mission));

        if ($only === null) {
            $risks = $risks->merge($this->overloadRisks($organization));
        }

        return $risks
            ->sortByDesc(fn (array $risk) => self::WEIGHT[$risk['level']])
            ->values();
    }

    /**
     * @return list<array{level: string, kind: string, title: string, detail: string|null, url: string}>
     */
    private function missionRisks(Mission $mission): array
    {
        $url = route('missions.show', $mission);
        $tasks = $mission->tasks->where('status', '!=', TaskStatus::Cancelled);
        $done = $tasks->where('status', TaskStatus::Done)->count();
        $progress = "{$done} of {$tasks->count()} tasks done";
        $risks = [];

        if ($mission->isOverdue()) {
            $risks[] = $this->risk('high', 'mission_overdue', "\"{$mission->title}\" is overdue", "Was due {$mission->due_date?->toFormattedDayDateString()} · {$progress}", $url);
        } elseif ($mission->due_date !== null && $mission->due_date->lte(today()->addDays((int) config('delegation.risks.due_soon_days')))) {
            $total = $tasks->count();

            if ($total === 0 || $done / $total < 0.75) {
                $days = (int) today()->diffInDays($mission->due_date);
                $when = $days === 0 ? 'today' : ($days === 1 ? 'tomorrow' : "in {$days} days");
                $risks[] = $this->risk('medium', 'mission_due_soon', "\"{$mission->title}\" is due {$when}", "Only {$progress}", $url);
            }
        }

        if ($mission->team->lead_id === null) {
            $risks[] = $this->risk('medium', 'mission_without_lead', "\"{$mission->title}\" has no lead", "Appoint a lead for {$mission->team->name} to break it down", $url);
        }

        $blockedSince = now()->subDays((int) config('delegation.risks.blocked_for_days'));

        foreach ($tasks as $task) {
            /** @var Task $task */
            if ($task->status === TaskStatus::Blocked && $task->updated_at?->lt($blockedSince)) {
                $days = (int) $task->updated_at->diffInDays(now());
                $risks[] = $this->risk('medium', 'task_blocked', "\"{$task->title}\" has been blocked for {$days} days", $task->blocked_reason, $url);
            } elseif ($task->isOverdue()) {
                $who = $task->assignee->name ?? 'nobody';
                $risks[] = $this->risk('medium', 'task_overdue', "\"{$task->title}\" is overdue", "Assigned to {$who} · was due {$task->due_date?->toFormattedDayDateString()}", $url);
            }
        }

        $unassigned = $tasks->filter(fn (Task $task) => $task->status->isOpen() && $task->assignee_id === null)->count();
        $dueWithin = today()->addDays((int) config('delegation.risks.unassigned_due_within_days'));

        if ($unassigned > 0 && $mission->due_date !== null && $mission->due_date->lte($dueWithin)) {
            $label = $unassigned === 1 ? '1 task has' : "{$unassigned} tasks have";
            $risks[] = $this->risk('low', 'tasks_unassigned', "{$label} no assignee in \"{$mission->title}\"", 'The mission is due soon', $url);
        }

        return $risks;
    }

    /**
     * @return Collection<int, array{level: string, kind: string, title: string, detail: string|null, url: string}>
     */
    private function overloadRisks(Organization $organization): Collection
    {
        return $this->workloads->forPeople($organization, $organization->members()->get())
            ->filter(fn (Workload $workload) => $workload->level() === 'overloaded')
            ->map(fn (Workload $workload) => $this->risk(
                'medium',
                'member_overloaded',
                "{$workload->user->name} is overloaded",
                "{$workload->hours}h of open work for a {$workload->capacity}h week",
                route('teams.index'),
            ))
            ->values();
    }

    /**
     * @return array{level: string, kind: string, title: string, detail: string|null, url: string}
     */
    private function risk(string $level, string $kind, string $title, ?string $detail, string $url): array
    {
        return compact('level', 'kind', 'title', 'detail', 'url');
    }
}
