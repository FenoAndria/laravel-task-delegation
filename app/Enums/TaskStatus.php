<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'To do',
            self::InProgress => 'In progress',
            self::Blocked => 'Blocked',
            self::Done => 'Done',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * The workflow: which statuses a task may move to from this one.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Todo => [self::InProgress, self::Blocked, self::Cancelled],
            self::InProgress => [self::Todo, self::Blocked, self::Done, self::Cancelled],
            self::Blocked => [self::Todo, self::InProgress, self::Cancelled],
            self::Done => [self::InProgress],
            self::Cancelled => [self::Todo],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->transitions(), true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Todo, self::InProgress, self::Blocked], true);
    }
}
