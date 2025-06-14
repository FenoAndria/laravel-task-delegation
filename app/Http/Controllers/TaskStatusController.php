<?php

namespace App\Http\Controllers;

use App\Actions\Tasks\ChangeTaskStatus;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskStatusController extends Controller
{
    public function update(Request $request, Task $task, ChangeTaskStatus $changeStatus): RedirectResponse
    {
        $this->ensureInOrganization($request, $task);
        $this->authorize('changeStatus', $task);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'blocked_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $changeStatus->handle($this->user($request), $task, TaskStatus::from($validated['status']), $validated['blocked_reason'] ?? null);

        return back();
    }
}
