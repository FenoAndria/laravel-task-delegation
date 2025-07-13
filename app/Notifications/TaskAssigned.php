<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;

class TaskAssigned extends AppNotification
{
    protected bool $mail = true;

    public function __construct(public Task $task, public User $assignedBy) {}

    protected function message(): array
    {
        return [
            'organization_id' => $this->task->organization_id,
            'title' => "{$this->assignedBy->name} assigned you \"{$this->task->title}\"",
            'body' => $this->task->mission->title,
            'url' => route('missions.show', $this->task->mission_id),
        ];
    }
}
