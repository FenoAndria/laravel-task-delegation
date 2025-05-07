<?php

namespace App\Http\Controllers;

use App\Enums\MissionStatus;
use App\Models\Mission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MissionStatusController extends Controller
{
    /**
     * Cancel an active mission.
     */
    public function cancel(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureInOrganization($request, $mission);
        $this->authorize('update', $mission);

        if (! $mission->status->isActive()) {
            throw ValidationException::withMessages(['status' => 'Only an active mission can be cancelled.']);
        }

        $mission->update(['status' => MissionStatus::Cancelled]);

        return to_route('missions.show', $mission);
    }

    /**
     * Bring a completed or cancelled mission back to work.
     */
    public function reopen(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureInOrganization($request, $mission);
        $this->authorize('update', $mission);

        if ($mission->status->isActive()) {
            throw ValidationException::withMessages(['status' => 'This mission is already active.']);
        }

        $mission->update(['status' => MissionStatus::Open, 'completed_at' => null]);

        return to_route('missions.show', $mission);
    }
}
