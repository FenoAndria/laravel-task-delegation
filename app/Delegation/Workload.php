<?php

namespace App\Delegation;

use App\Models\User;

/**
 * How much open work a person carries across their organization.
 */
final readonly class Workload
{
    public function __construct(
        public User $user,
        public int $openTasks,
        public int $hours,
        public int $overdue,
        public int $capacity,
    ) {}

    public function ratio(): float
    {
        return $this->capacity > 0 ? $this->hours / $this->capacity : 0.0;
    }

    public function level(): string
    {
        return match (true) {
            $this->ratio() > 1.0 => 'overloaded',
            $this->ratio() >= 0.5 => 'balanced',
            default => 'available',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user' => $this->user->only('id', 'name'),
            'open_tasks' => $this->openTasks,
            'hours' => $this->hours,
            'capacity' => $this->capacity,
            'overdue' => $this->overdue,
            'percent' => (int) round($this->ratio() * 100),
            'level' => $this->level(),
        ];
    }
}
