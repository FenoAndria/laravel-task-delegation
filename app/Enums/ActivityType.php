<?php

namespace App\Enums;

enum ActivityType: string
{
    case MissionCreated = 'mission_created';
    case MissionStarted = 'mission_started';
    case MissionCompleted = 'mission_completed';
    case MissionCancelled = 'mission_cancelled';
    case MissionReopened = 'mission_reopened';
    case TaskCreated = 'task_created';
    case TaskAssigned = 'task_assigned';
    case TaskStatusChanged = 'task_status_changed';
    case TaskDeleted = 'task_deleted';

    /**
     * A sentence completing "<actor> …", built from the stored properties
     * so it stays readable after the task is renamed or deleted.
     *
     * @param  array<string, mixed>  $properties
     */
    public function describe(array $properties): string
    {
        $task = '"'.($properties['task'] ?? 'a task').'"';

        return match ($this) {
            self::MissionCreated => 'created the mission',
            self::MissionStarted => 'started the mission',
            self::MissionCompleted => 'completed the mission',
            self::MissionCancelled => 'cancelled the mission',
            self::MissionReopened => 'reopened the mission',
            self::TaskCreated => "added the task {$task}",
            self::TaskAssigned => isset($properties['assignee'])
                ? "assigned {$task} to {$properties['assignee']}"
                : "unassigned {$task}",
            self::TaskStatusChanged => isset($properties['reason'])
                ? "marked {$task} as blocked: {$properties['reason']}"
                : "moved {$task} to ".TaskStatus::from($properties['to'])->label(),
            self::TaskDeleted => "deleted the task {$task}",
        };
    }
}
