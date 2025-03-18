<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('users without an organization are sent to onboarding', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('organizations.create'));
});

test('the onboarding screen can be rendered', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('organizations.create'))
        ->assertOk();
});

test('users can create an organization and become its owner', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('organizations.store'), ['name' => 'Acme Studio'])
        ->assertRedirect(route('dashboard'));

    $organization = Organization::firstWhere('name', 'Acme Studio');

    expect($organization)->not->toBeNull()
        ->and($organization->slug)->toBe('acme-studio')
        ->and($user->fresh()->current_organization_id)->toBe($organization->id)
        ->and($user->roleIn($organization))->toBe(OrganizationRole::Owner);
});

test('organization slugs stay unique', function () {
    $first = Organization::factory()->create(['name' => 'Acme']);
    $second = Organization::factory()->create(['name' => 'Acme']);

    expect($second->slug)->not->toBe($first->slug)
        ->and($second->slug)->toStartWith('acme-');
});

test('an organization name is required', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('organizations.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('members reach the dashboard of their organization', function () {
    $user = User::factory()->create();
    Organization::factory()->withMember($user)->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});
