<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

test('the organization settings page can be rendered', function () {
    $user = User::factory()->create();
    Organization::factory()->withMember($user)->create();

    $this->actingAs($user)->get(route('organization.edit'))->assertOk();
});

test('owners and admins can rename the organization', function (OrganizationRole $role) {
    $user = User::factory()->create();
    $organization = Organization::factory()->withMember($user, $role)->create();

    $this->actingAs($user)
        ->patch(route('organization.update'), ['name' => 'Renamed'])
        ->assertRedirect(route('organization.edit'));

    expect($organization->fresh()->name)->toBe('Renamed');
})->with([OrganizationRole::Owner, OrganizationRole::Admin]);

test('members cannot rename the organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->withMember($user)->create(['name' => 'Original']);

    $this->actingAs($user)
        ->patch(route('organization.update'), ['name' => 'Renamed'])
        ->assertForbidden();

    expect($organization->fresh()->name)->toBe('Original');
});

test('settings only ever touch the current organization', function () {
    $user = User::factory()->create();
    $foreign = Organization::factory()->create(['name' => 'Foreign']);
    Organization::factory()->ownedBy($user)->create();

    $this->actingAs($user)->patch(route('organization.update'), ['name' => 'Renamed']);

    expect($foreign->fresh()->name)->toBe('Foreign');
});
