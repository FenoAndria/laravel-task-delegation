<?php

namespace App\Delegation\Ai;

use App\Enums\TaskStatus;
use App\Models\Mission;
use App\Models\Task;
use Illuminate\Support\Str;

/**
 * Proposes how to break a mission down into tasks. Nothing is created:
 * the lead reviews, edits and picks what to keep.
 */
class MissionBreakdown
{
    public const MAX_TASKS = 8;

    public function __construct(private ChatClient $client) {}

    /**
     * @return list<array{title: string, estimate_hours: int|null}>
     *
     * @throws AssistantUnavailable
     */
    public function propose(Mission $mission): array
    {
        $existing = $mission->tasks()
            ->where('status', '!=', TaskStatus::Cancelled)
            ->pluck('title')
            ->map(fn (string $title) => "- {$title}")
            ->implode("\n");

        $answer = $this->client->json([
            ['role' => 'system', 'content' => $this->instructions()],
            ['role' => 'user', 'content' => implode("\n\n", array_filter([
                "Mission: {$mission->title}",
                "Team: {$mission->team->name}",
                $mission->description ? "Context:\n{$mission->description}" : null,
                $mission->due_date ? 'Due: '.$mission->due_date->toFormattedDayDateString() : null,
                $existing !== '' ? "Tasks that already exist (do not repeat them):\n{$existing}" : null,
            ]))],
        ]);

        $tasks = collect(is_array($answer['tasks'] ?? null) ? $answer['tasks'] : [])
            ->filter(fn ($task) => is_array($task) && is_string($task['title'] ?? null) && trim($task['title']) !== '')
            ->map(fn (array $task) => [
                'title' => Str::limit(trim($task['title']), 157),
                'estimate_hours' => is_numeric($task['estimate_hours'] ?? null)
                    ? max(1, min(40, (int) $task['estimate_hours']))
                    : null,
            ])
            ->unique(fn (array $task) => Str::lower($task['title']))
            ->take(self::MAX_TASKS)
            ->values()
            ->all();

        if ($tasks === []) {
            throw new AssistantUnavailable('The AI assistant had no useful suggestion.');
        }

        return $tasks;
    }

    private function instructions(): string
    {
        $max = self::MAX_TASKS;

        return <<<PROMPT
        You help a team lead break a mission down into tasks for their team.
        Propose between 3 and {$max} concrete tasks that together deliver the mission.
        Each task starts with a verb, fits in one line, can be done by one person, and takes 1 to 16 hours.
        Answer with JSON only, in this exact shape:
        {"tasks": [{"title": "Interview five recent users", "estimate_hours": 6}]}
        PROMPT;
    }

    /**
     * Whether the user may ask for a breakdown of this mission.
     */
    public static function availableFor(Mission $mission, bool $canManageTasks): bool
    {
        return $canManageTasks && $mission->status->isActive() && ChatClient::isConfigured();
    }

    /**
     * @param  array{title: string, estimate_hours: int|null}  $proposal
     */
    public static function toTask(Mission $mission, array $proposal, int $position, int $createdBy): Task
    {
        return $mission->tasks()->create([
            'organization_id' => $mission->organization_id,
            'created_by' => $createdBy,
            'title' => $proposal['title'],
            'estimate_hours' => $proposal['estimate_hours'],
            'position' => $position,
        ]);
    }
}
