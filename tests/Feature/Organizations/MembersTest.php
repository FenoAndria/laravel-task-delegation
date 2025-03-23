<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function organizationWith(User $actor, OrganizationRole $role): Organization
{
    return Organization::factory()->withMember($actor, $role)->create();
}

function addMember(Organization $organization, OrganizationRole $role = OrganizationRole::Member): User
{
    $user = User::factory()->create();
    $organization->members()->attach($user, ['role' => $role]);

    return $user;
}

test('the members page lists the members of the current organization only', function () {
    $owner = User::factory()->create();
    $organization = organizationWith($owner, OrganizationRole::Owner);
    addMember($organization);
    addMember(Organization::factory()->create());

    $this->actingAs($owner)
        ->get(route('members.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Members')
            ->has('members', 2)
            ->where('canManage', true)
        );
});

test('admins can promote a member to admin', function () {
    $admin = User::factory()->create();
    $organization = organizationWith($admin, OrganizationRole::Admin);
    $member = addMember($organization);

    $this->actingAs($admin)
        ->patch(route('members.update', $member), ['role' => 'admin'])
        ->assertRedirect(route('members.index'));

    expect($member->roleIn($organization))->toBe(OrganizationRole::Admin);
});

test('members cannot change roles', function () {
    $actor = User::factory()->create();
    $organization = organizationWith($actor, OrganizationRole::Member);
    $other = addMember($organization);

    $this->actingAs($actor)
        ->patch(route('members.update', $other), ['role' => 'admin'])
        ->assertSessionHasErrors('role');

    expect($other->roleIn($organization))->toBe(OrganizationRole::Member);
});

test('only owners can grant ownership', function () {
    $admin = User::factory()->create();
    $organization = organizationWith($admin, OrganizationRole::Admin);
    $member = addMember($organization);

    $this->actingAs($admin)
        ->patch(route('members.update', $member), ['role' => 'owner'])
        ->assertSessionHasErrors('role');

    expect($member->roleIn($organization))->toBe(OrganizationRole::Member);
});

test('the last owner cannot be demoted', function () {
    $owner = User::factory()->create();
    $organization = organizationWith($owner, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->patch(route('members.update', $owner), ['role' => 'admin'])
        ->assertSessionHasErrors('role');

    expect($owner->roleIn($organization))->toBe(OrganizationRole::Owner);
});

test('an owner can step down once another owner exists', function () {
    $owner = User::factory()->create();
    $organization = organizationWith($owner, OrganizationRole::Owner);
    addMember($organization, OrganizationRole::Owner);

    $this->actingAs($owner)->patch(route('members.update', $owner), ['role' => 'admin']);

    expect($owner->roleIn($organization))->toBe(OrganizationRole::Admin);
});

test('admins can remove members', function () {
    $admin = User::factory()->create();
    $organization = organizationWith($admin, OrganizationRole::Admin);
    $member = addMember($organization);
    $member->switchOrganization($organization);

    $this->actingAs($admin)
        ->delete(route('members.destroy', $member))
        ->assertRedirect(route('members.index'));

    expect($member->belongsToOrganization($organization))->toBeFalse()
        ->and($member->fresh()->current_organization_id)->toBeNull();
});

test('admins cannot remove owners', function () {
    $admin = User::factory()->create();
    $organization = organizationWith($admin, OrganizationRole::Admin);
    $owner = addMember($organization, OrganizationRole::Owner);

    $this->actingAs($admin)
        ->delete(route('members.destroy', $owner))
        ->assertSessionHasErrors('member');

    expect($owner->belongsToOrganization($organization))->toBeTrue();
});

test('members can leave an organization', function () {
    $member = User::factory()->create();
    $organization = organizationWith($member, OrganizationRole::Member);
    $other = Organization::factory()->create();
    $other->members()->attach($member, ['role' => OrganizationRole::Member]);

    $this->actingAs($member)
        ->delete(route('members.destroy', $member))
        ->assertRedirect(route('dashboard'));

    expect($member->belongsToOrganization($organization))->toBeFalse()
        ->and($member->fresh()->current_organization_id)->toBe($other->id);
});

test('the last owner cannot leave', function () {
    $owner = User::factory()->create();
    $organization = organizationWith($owner, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->delete(route('members.destroy', $owner))
        ->assertSessionHasErrors('member');

    expect($owner->belongsToOrganization($organization))->toBeTrue();
});

test('users of another organization are not found', function () {
    $owner = User::factory()->create();
    organizationWith($owner, OrganizationRole::Owner);
    $outsider = addMember(Organization::factory()->create());

    $this->actingAs($owner)
        ->patch(route('members.update', $outsider), ['role' => 'admin'])
        ->assertNotFound();

    $this->actingAs($owner)
        ->delete(route('members.destroy', $outsider))
        ->assertNotFound();
});
