<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function edit(Request $request): Response
    {
        $organization = $this->organization($request);

        return Inertia::render('settings/Organization', [
            'organization' => $organization->only('id', 'name', 'slug'),
            'canUpdate' => $this->user($request)->can('update', $organization),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $organization = $this->organization($request);
        $this->authorize('update', $organization);

        $organization->update($request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]));

        return to_route('organization.edit');
    }
}
