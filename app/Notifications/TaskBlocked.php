<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;

class TaskBlocked extends AppNotification
{
    public function __construct(public Task $task, public User $blockedBy) {}

    protected function message(): array
    {
        return [
            'organization_id' => $this->task->organization_id,
            'title' => "{$this->blockedBy->name} is blocked on \"{$this->task->title}\"",
            'body' => $this->task->blocked_reason,
            'url' => route('missions.show', $this->task->mission_id),
        ];
    }
}
