<?php

namespace App\Http\Controllers;

use App\Actions\Organizations\CreateOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('organizations/Create', [
            'hasOrganizations' => $this->user($request)->organizations()->exists(),
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
