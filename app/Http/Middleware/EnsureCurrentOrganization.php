<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentOrganization
{
    /**
     * Make sure the user works inside an organization they belong to,
     * falling back to another membership or to the onboarding screen.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $current = $user?->currentOrganization;

        if ($user && ($current === null || ! $user->belongsToOrganization($current))) {
            $fallback = $user->organizations()->first();

            if ($fallback === null) {
                return redirect()->route('organizations.create');
            }

            $user->switchOrganization($fallback);
        }

        return $next($request);
    }
}
