<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrentOrganizationController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'integer'],
        ]);

        $organization = Organization::findOrFail($validated['organization_id']);
        $this->authorize('view', $organization);

        $this->user($request)->switchOrganization($organization);

        return to_route('dashboard');
    }
}
