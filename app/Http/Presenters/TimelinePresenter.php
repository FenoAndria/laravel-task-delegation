<?php

namespace App\Http\Presenters;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Mission;
use App\Models\User;

class TimelinePresenter
{
    private const LIMIT = 100;

    /**
     * Comments and activity of a mission, oldest first, as one thread.
     *
     * @return list<array<string, mixed>>
     */
    public static function present(Mission $mission, User $viewer): array
    {
        $comments = $mission->comments()->with('author:id,name')->latest()->limit(self::LIMIT)->get()
            ->each->setRelation('mission', $mission)
            ->map(fn (Comment $comment) => [
                'key' => "comment-{$comment->id}",
                'kind' => 'comment',
                'id' => $comment->id,
                'actor' => $comment->author->name ?? 'Former member',
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toIso8601String(),
                'can_delete' => $viewer->can('delete', $comment),
            ]);

        $activities = $mission->activities()->with('actor:id,name')->latest()->limit(self::LIMIT)->get()
            ->map(fn (Activity $activity) => [
                'key' => "activity-{$activity->id}",
                'kind' => 'activity',
                'type' => $activity->type->value,
                'actor' => $activity->actor->name ?? 'Someone',
                'body' => $activity->description(),
                'created_at' => $activity->created_at->toIso8601String(),
            ]);

        return $comments->toBase()->merge($activities)
            ->sortBy('created_at')
            ->take(-self::LIMIT)
            ->values()
            ->all();
    }
}
