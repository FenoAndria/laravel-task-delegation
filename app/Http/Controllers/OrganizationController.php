<?php

namespace App\Http\Controllers;

use App\Actions\Organizations\CreateOrganization;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function create(Request $request): Response
    {
        $user = $this->user($request);

        return Inertia::render('organizations/Create', [
            'hasOrganizations' => $user->organizations()->exists(),
            'invitations' => Invitation::pending()
                ->where('email', Str::lower($user->email))
                ->with('organization')
                ->get()
                ->map(fn (Invitation $invitation) => [
                    'token' => $invitation->token,
                    'organization' => $invitation->organization->name,
                    'role' => $invitation->role->label(),
                ]),
        ]);
    }

    public function store(Request $request, CreateOrganization $createOrganization): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $createOrganization->handle($this->user($request), $validated['name']);

        return to_route('dashboard');
    }
}
