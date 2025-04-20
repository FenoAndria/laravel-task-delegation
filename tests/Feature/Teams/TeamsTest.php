<?php

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function personIn(Organization $organization, OrganizationRole $role = OrganizationRole::Member): User
{
    $user = User::factory()->create();
    $organization->members()->attach($user, ['role' => $role]);
    $user->switchOrganization($organization);

    return $user;
}

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = personIn($this->organization, OrganizationRole::Admin);
});

test('members see the teams of their organization only', function () {
    Team::factory()->for($this->organization)->create(['name' => 'Design']);
    Team::factory()->create(['name' => 'Elsewhere']);
    $member = personIn($this->organization);

    $this->actingAs($member)
        ->get(route('teams.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('teams/Index')
            ->has('teams', 1)
            ->where('teams.0.name', 'Design')
            ->where('canCreate', false)
        );
});

test('admins can create a team with a lead', function () {
    $lead = personIn($this->organization);

    $this->actingAs($this->admin)
        ->post(route('teams.store'), ['name' => 'Design', 'description' => 'Brand and product', 'lead_id' => $lead->id])
        ->assertRedirect();

    $team = $this->organization->teams()->sole();

    expect($team->name)->toBe('Design')
        ->and($team->lead_id)->toBe($lead->id);
});

test('team names are unique within an organization', function () {
    Team::factory()->for($this->organization)->create(['name' => 'Design']);
    Team::factory()->create(['name' => 'Ops']);

    $this->actingAs($this->admin)
        ->post(route('teams.store'), ['name' => 'Design'])
        ->assertSessionHasErrors('name');

    $this->actingAs($this->admin)
        ->post(route('teams.store'), ['name' => 'Ops'])
        ->assertSessionHasNoErrors();
});

test('members cannot create, rename or delete teams', function () {
    $member = personIn($this->organization);
    $team = Team::factory()->for($this->organization)->create();

    $this->actingAs($member)->post(route('teams.store'), ['name' => 'Nope'])->assertForbidden();
    $this->actingAs($member)->put(route('teams.update', $team), ['name' => 'Renamed'])->assertForbidden();
    $this->actingAs($member)->delete(route('teams.destroy', $team))->assertForbidden();
});

test('a lead cannot be someone outside the organization', function () {
    $outsider = User::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('teams.store'), ['name' => 'Design', 'lead_id' => $outsider->id])
        ->assertSessionHasErrors('lead_id');
});

test('leads manage the members of their team', function () {
    $lead = personIn($this->organization);
    $team = Team::factory()->for($this->organization)->ledBy($lead)->create();
    $member = personIn($this->organization);

    $this->actingAs($lead)
        ->post(route('teams.members.store', $team), ['user_id' => $member->id])
        ->assertRedirect(route('teams.show', $team));

    expect($team->hasMember($member))->toBeTrue();

    $this->actingAs($lead)->delete(route('teams.members.destroy', [$team, $member]));

    expect($team->hasMember($member))->toBeFalse();
});

test('leads cannot appoint another lead', function () {
    $lead = personIn($this->organization);
    $team = Team::factory()->for($this->organization)->ledBy($lead)->create();
    $other = personIn($this->organization);

    $this->actingAs($lead)
        ->put(route('teams.lead.update', $team), ['lead_id' => $other->id])
        ->assertForbidden();
});

test('members of a team cannot manage its members', function () {
    $team = Team::factory()->for($this->organization)->create();
    $member = personIn($this->organization);
    $team->members()->attach($member);

    $this->actingAs($member)
        ->post(route('teams.members.store', $team), ['user_id' => personIn($this->organization)->id])
        ->assertForbidden();
});

test('only organization members can join a team', function () {
    $team = Team::factory()->for($this->organization)->create();

    $this->actingAs($this->admin)
        ->post(route('teams.members.store', $team), ['user_id' => User::factory()->create()->id])
        ->assertSessionHasErrors('user_id');
});

test('promoting a member to lead removes them from the member list', function () {
    $team = Team::factory()->for($this->organization)->create();
    $member = personIn($this->organization);
    $team->members()->attach($member);

    $this->actingAs($this->admin)->put(route('teams.lead.update', $team), ['lead_id' => $member->id]);

    expect($team->fresh()->lead_id)->toBe($member->id)
        ->and($team->hasMember($member))->toBeFalse();
});

test('the lead cannot also be added as a member', function () {
    $lead = personIn($this->organization);
    $team = Team::factory()->for($this->organization)->ledBy($lead)->create();

    $this->actingAs($this->admin)
        ->post(route('teams.members.store', $team), ['user_id' => $lead->id])
        ->assertSessionHasErrors('user_id');
});

test('admins can remove the lead', function () {
    $team = Team::factory()->for($this->organization)->ledBy(personIn($this->organization))->create();

    $this->actingAs($this->admin)->put(route('teams.lead.update', $team), ['lead_id' => null]);

    expect($team->fresh()->lead_id)->toBeNull();
});

test('leaving the organization removes the person from its teams', function () {
    $person = personIn($this->organization);
    $led = Team::factory()->for($this->organization)->ledBy($person)->create();
    $joined = Team::factory()->for($this->organization)->create();
    $joined->members()->attach($person);

    $this->actingAs($this->admin)->delete(route('members.destroy', $person));

    expect($led->fresh()->lead_id)->toBeNull()
        ->and($joined->hasMember($person))->toBeFalse();
});

test('teams of another organization are not found', function () {
    $foreign = Team::factory()->create();

    $this->actingAs($this->admin)->get(route('teams.show', $foreign))->assertNotFound();
    $this->actingAs($this->admin)->put(route('teams.update', $foreign), ['name' => 'Mine'])->assertNotFound();
    $this->actingAs($this->admin)->delete(route('teams.destroy', $foreign))->assertNotFound();
    $this->actingAs($this->admin)->post(route('teams.members.store', $foreign), ['user_id' => $this->admin->id])->assertNotFound();
});

test('admins can rename and delete a team', function () {
    $team = Team::factory()->for($this->organization)->create();

    $this->actingAs($this->admin)->put(route('teams.update', $team), ['name' => 'Renamed', 'description' => null]);
    expect($team->fresh()->name)->toBe('Renamed');

    $this->actingAs($this->admin)->delete(route('teams.destroy', $team))->assertRedirect(route('teams.index'));
    expect(Team::find($team->id))->toBeNull();
});

test('the team page shows its lead and members', function () {
    $lead = personIn($this->organization);
    $team = Team::factory()->for($this->organization)->ledBy($lead)->create();
    $team->members()->attach(personIn($this->organization));

    $this->actingAs($this->admin)
        ->get(route('teams.show', $team))
        ->assertInertia(fn (Assert $page) => $page
            ->component('teams/Show')
            ->where('team.lead.id', $lead->id)
            ->has('team.members', 1)
            ->where('can.update', true)
        );
});
