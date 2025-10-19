<?php

namespace App\Http\Controllers;

use App\Delegation\Ai\AssistantUnavailable;
use App\Delegation\Ai\MissionBreakdown;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Mission;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class MissionBreakdownController extends Controller
{
    /**
     * Ask the assistant for a breakdown and hand the proposal back to the page for review.
     */
    public function propose(Request $request, Mission $mission, MissionBreakdown $breakdown): RedirectResponse
    {
        $this->ensureCanBreakDown($request, $mission);

        $key = "ai-breakdown:{$mission->organization_id}";

        if (RateLimiter::tooManyAttempts($key, (int) config('delego.ai.max_per_hour'))) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages(['breakdown' => "The assistant needs a break. Try again in {$minutes} minutes."]);
        }

        RateLimiter::hit($key, 3600);

        try {
            $tasks = $breakdown->propose($mission);
        } catch (AssistantUnavailable $exception) {
            report($exception);
            throw ValidationException::withMessages(['breakdown' => $exception->getMessage().' You can still add tasks by hand.']);
        }

        return back()->with('breakdown', ['mission_id' => $mission->id, 'tasks' => $tasks]);
    }

    /**
     * Create the proposed tasks the lead kept, as edited.
     */
    public function accept(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureCanBreakDown($request, $mission);

        $validated = $request->validate([
            'tasks' => ['required', 'array', 'min:1', 'max:'.MissionBreakdown::MAX_TASKS],
            'tasks.*.title' => ['required', 'string', 'max:160'],
            'tasks.*.estimate_hours' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $user = $this->user($request);

        DB::transaction(function () use ($mission, $validated, $user) {
            $position = (int) $mission->tasks()->max('position');

            foreach ($validated['tasks'] as $proposal) {
                $task = MissionBreakdown::toTask($mission, [
                    'title' => $proposal['title'],
                    'estimate_hours' => $proposal['estimate_hours'] ?? null,
                ], ++$position, $user->id);

                Activity::record($mission, $user, ActivityType::TaskCreated, ['task' => $task->title], $task);
            }
        });

        return to_route('missions.show', $mission);
    }

    private function ensureCanBreakDown(Request $request, Mission $mission): void
    {
        $this->ensureInOrganization($request, $mission);
        $this->authorize('manage', [Task::class, $mission]);

        if (! MissionBreakdown::availableFor($mission, true)) {
            throw ValidationException::withMessages(['breakdown' => 'The assistant is not available for this mission.']);
        }
    }
}
