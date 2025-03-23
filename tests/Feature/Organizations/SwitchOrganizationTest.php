<?php

use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('users can switch between their organizations', function () {
    $user = User::factory()->create();
    $first = Organization::factory()->withMember($user)->create();
    $second = Organization::factory()->withMember($user)->create();

    $this->actingAs($user)
        ->put(route('current-organization.update'), ['organization_id' => $first->id])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->current_organization_id)->toBe($first->id);

    $this->actingAs($user)->put(route('current-organization.update'), ['organization_id' => $second->id]);

    expect($user->fresh()->current_organization_id)->toBe($second->id);
});

test('users cannot switch to an organization they do not belong to', function () {
    $user = User::factory()->create();
    $own = Organization::factory()->withMember($user)->create();
    $foreign = Organization::factory()->create();

    $this->actingAs($user)
        ->put(route('current-organization.update'), ['organization_id' => $foreign->id])
        ->assertForbidden();

    expect($user->fresh()->current_organization_id)->toBe($own->id);
});

test('a stale current organization falls back to another membership', function () {
    $user = User::factory()->create();
    $kept = Organization::factory()->withMember($user)->create();
    $left = Organization::factory()->withMember($user)->create();
    $left->members()->detach($user);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh()->current_organization_id)->toBe($kept->id);
});

test('pages share only the organizations of the user', function () {
    $user = User::factory()->create();
    $own = Organization::factory()->withMember($user)->create(['name' => 'Mine']);
    Organization::factory()->create(['name' => 'Not mine']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.organization.id', $own->id)
            ->has('auth.organizations', 1)
            ->where('auth.organizations.0.name', 'Mine')
        );
});
