<?php

namespace App\Notifications;

use App\Models\Mission;

class MissionEntrusted extends AppNotification
{
    protected bool $mail = true;

    public function __construct(public Mission $mission) {}

    protected function message(): array
    {
        $due = $this->mission->due_date ? ' · due '.$this->mission->due_date->toFormattedDayDateString() : '';

        return [
            'organization_id' => $this->mission->organization_id,
            'title' => "New mission for {$this->mission->team->name}: \"{$this->mission->title}\"",
            'body' => "Priority {$this->mission->priority->label()}{$due}",
            'url' => route('missions.show', $this->mission),
        ];
    }
}
