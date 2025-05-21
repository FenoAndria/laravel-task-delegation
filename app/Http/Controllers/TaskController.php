<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\Task;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskController extends Controller
{
    public function store(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureInOrganization($request, $mission);
        $this->authorize('manage', [Task::class, $mission]);

        if (! $mission->status->isActive()) {
            throw ValidationException::withMessages(['title' => 'The mission is closed: reopen it to add tasks.']);
        }

        $mission->tasks()->create([
            ...$request->validate($this->rules($mission)),
            'organization_id' => $mission->organization_id,
            'created_by' => $this->user($request)->id,
            'position' => (int) $mission->tasks()->max('position') + 1,
        ]);

        return to_route('missions.show', $mission);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->ensureInOrganization($request, $task);
        $this->authorize('update', $task);

        $task->update($request->validate($this->rules($task->mission)));

        return back();
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->ensureInOrganization($request, $task);
        $this->authorize('delete', $task);

        $task->delete();

        return to_route('missions.show', $task->mission_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Mission $mission): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assignee_id' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail) use ($mission) {
                $user = User::find($value);

                if ($user === null || ! $mission->team->includes($user)) {
                    $fail('Tasks can only be assigned to the team lead or its members.');
                }
            }],
            'due_date' => ['nullable', 'date'],
            'estimate_hours' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
