<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something changed on a mission: whoever is looking at it reloads.
 * Queued on purpose, so a stopped WebSocket server never breaks a user's action.
 */
class MissionChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public bool $afterCommit = true;

    public function __construct(public int $missionId) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel("missions.{$this->missionId}")];
    }

    public function broadcastAs(): string
    {
        return 'mission.changed';
    }

    /**
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return ['mission_id' => $this->missionId];
    }
}
