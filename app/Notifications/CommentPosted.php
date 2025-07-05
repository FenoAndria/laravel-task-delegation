<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Support\Str;

class CommentPosted extends AppNotification
{
    public function __construct(public Comment $comment) {}

    protected function message(): array
    {
        $author = $this->comment->author->name ?? 'Someone';

        return [
            'organization_id' => $this->comment->organization_id,
            'title' => "{$author} commented on \"{$this->comment->mission->title}\"",
            'body' => Str::limit($this->comment->body, 140),
            'url' => route('missions.show', $this->comment->mission_id),
        ];
    }
}
