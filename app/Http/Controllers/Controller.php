<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    protected function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    /**
     * The organization the user is working in, guaranteed by the "organization" middleware.
     */
    protected function organization(Request $request): Organization
    {
        $organization = $this->user($request)->currentOrganization;
        abort_if($organization === null, 409);

        return $organization;
    }
}
