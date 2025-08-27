<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'demo' => (bool) config('delego.demo'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'unreadNotifications' => fn () => $this->unreadNotifications($request),
            'auth' => [
                'user' => $request->user(),
                'organization' => fn () => $this->currentOrganization($request),
                'organizations' => fn () => $request->user()?->organizations()->orderBy('name')->get(['organizations.id', 'name'])
                    ->map(fn (Organization $organization) => $organization->only('id', 'name')),
            ],
        ]);
    }

    private function unreadNotifications(Request $request): int
    {
        $user = $request->user();

        if ($user === null || $user->current_organization_id === null) {
            return 0;
        }

        return $user->unreadNotifications()->where('data->organization_id', $user->current_organization_id)->count();
    }

    /**
     * @return array{id: int, name: string, slug: string, role: string|null, can_manage: bool}|null
     */
    private function currentOrganization(Request $request): ?array
    {
        $user = $request->user();
        $organization = $user?->currentOrganization;

        if ($user === null || $organization === null) {
            return null;
        }

        $role = $user->roleIn($organization);

        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'role' => $role?->value,
            'can_manage' => (bool) $role?->canManage(),
        ];
    }
}
