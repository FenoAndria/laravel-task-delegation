<?php

namespace App\Actions\Tasks;

use App\Enums\ActivityType;
use App\Enums\MissionStatus;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use App\Notifications\Notify;
use App\Notifications\TaskBlocked;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeTaskStatus
{
    public function handle(?User $actor, Task $task, TaskStatus $status, ?string $blockedReason = null): void
    {
        $mission = $task->mission;

        if (! $mission->status->isActive()) {
            throw ValidationException::withMessages(['status' => 'The mission is closed: reopen it to change its tasks.']);
        }

        if (! $task->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => "A task cannot go from {$task->status->label()} to {$status->label()}.",
            ]);
        }

        if ($status === TaskStatus::Blocked && blank($blockedReason)) {
            throw ValidationException::withMessages(['blocked_reason' => 'Say what is blocking the task.']);
        }

        DB::transaction(function () use ($actor, $task, $mission, $status, $blockedReason) {
            $from = $task->status;

            $task->update([
                'status' => $status,
                'blocked_reason' => $status === TaskStatus::Blocked ? $blockedReason : null,
                'completed_at' => $status === TaskStatus::Done ? now() : null,
            ]);

            // The mission starts as soon as someone starts working on it.
            if ($mission->status === MissionStatus::Open && in_array($status, [TaskStatus::InProgress, TaskStatus::Done], true)) {
                $mission->update(['status' => MissionStatus::InProgress]);
                Activity::record($mission, $actor, ActivityType::MissionStarted);
            }

            Activity::record($mission, $actor, ActivityType::TaskStatusChanged, array_filter([
                'task' => $task->title,
                'from' => $from->value,
                'to' => $status->value,
                'reason' => $status === TaskStatus::Blocked ? $blockedReason : null,
            ]), $task);

            if ($status === TaskStatus::Blocked && $actor !== null) {
                Notify::send([$mission->team->lead], new TaskBlocked($task, $actor), $actor);
            }
        });
    }
}
