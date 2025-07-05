<?php

namespace App\Actions\Missions;

use App\Enums\ActivityType;
use App\Enums\MissionStatus;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Mission;
use App\Models\User;
use App\Notifications\MissionCompleted;
use App\Notifications\Notify;
use Illuminate\Validation\ValidationException;

class CompleteMission
{
    /**
     * A mission is complete once at least one task is done and every other task is done or cancelled.
     */
    public function handle(Mission $mission, ?User $actor = null): void
    {
        if (! $mission->status->isActive()) {
            throw ValidationException::withMessages(['status' => 'Only an active mission can be completed.']);
        }

        if (! self::canComplete($mission)) {
            throw ValidationException::withMessages(['status' => 'Finish or cancel every task before completing the mission.']);
        }

        $mission->update(['status' => MissionStatus::Completed, 'completed_at' => now()]);
        Activity::record($mission, $actor, ActivityType::MissionCompleted);
        Notify::send([$mission->creator], new MissionCompleted($mission), $actor);
    }

    public static function canComplete(Mission $mission): bool
    {
        $statuses = $mission->tasks()->pluck('status');

        return $statuses->contains(TaskStatus::Done)
            && $statuses->every(fn (TaskStatus $status) => ! $status->isOpen());
    }
}
