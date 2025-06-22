<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Mission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Anyone who can see the mission can take part in its discussion.
     */
    public function store(Request $request, Mission $mission): RedirectResponse
    {
        $this->ensureInOrganization($request, $mission);
        $this->authorize('view', $mission);

        $mission->comments()->create([
            ...$request->validate(['body' => ['required', 'string', 'max:5000']]),
            'organization_id' => $mission->organization_id,
            'user_id' => $this->user($request)->id,
        ]);

        return back();
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        $this->ensureInOrganization($request, $comment);
        $this->authorize('delete', $comment);

        $comment->delete();

        return back();
    }
}
