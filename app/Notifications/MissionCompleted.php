<?php

namespace App\Notifications;

use App\Models\Mission;

class MissionCompleted extends AppNotification
{
    public function __construct(public Mission $mission) {}

    protected function message(): array
    {
        return [
            'organization_id' => $this->mission->organization_id,
            'title' => "{$this->mission->team->name} completed \"{$this->mission->title}\"",
            'body' => null,
            'url' => route('missions.show', $this->mission),
        ];
    }
}
