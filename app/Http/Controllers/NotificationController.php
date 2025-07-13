<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $this->scoped($request)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? '',
                'body' => $notification->data['body'] ?? null,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);

        return Inertia::render('notifications/Index', ['notifications' => $notifications]);
    }

    /**
     * Mark the notification as read and follow it.
     */
    public function show(Request $request, string $id): RedirectResponse
    {
        $notification = $this->scoped($request)->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('dashboard'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->scoped($request)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    /**
     * Notifications of the user in the current organization.
     *
     * @return MorphMany<DatabaseNotification, User>
     */
    private function scoped(Request $request): MorphMany
    {
        return $this->user($request)->notifications()
            ->where('data->organization_id', $this->organization($request)->id);
    }
}
