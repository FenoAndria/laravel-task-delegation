<?php

namespace App\Policies;

use App\Models\Mission;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * The team lead breaks the mission down; organization managers may step in.
     */
    public function manage(User $user, Mission $mission): bool
    {
        return $mission->team->isLedBy($user) || (bool) $user->roleIn($mission->organization)?->canManage();
    }

    public function update(User $user, Task $task): bool
    {
        return $this->manage($user, $task->mission);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->manage($user, $task->mission);
    }

    /**
     * Assignees move their own tasks forward.
     */
    public function changeStatus(User $user, Task $task): bool
    {
        return $task->assignee_id === $user->id || $this->manage($user, $task->mission);
    }
}
