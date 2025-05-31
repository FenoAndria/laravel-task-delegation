<?php

namespace App\Http\Presenters;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class TaskPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function present(Task $task, User $viewer): array
    {
        $canChangeStatus = $viewer->can('changeStatus', $task);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'status_label' => $task->status->label(),
            'blocked_reason' => $task->blocked_reason,
            'assignee' => $task->assignee?->only('id', 'name'),
            'due_date' => $task->due_date?->toDateString(),
            'estimate_hours' => $task->estimate_hours,
            'overdue' => $task->isOverdue(),
            'transitions' => $canChangeStatus
                ? array_map(fn (TaskStatus $status) => ['value' => $status->value, 'label' => $status->label()], $task->status->transitions())
                : [],
        ];
    }
}
