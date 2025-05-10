<?php

use App\Enums\MissionStatus;
use App\Enums\OrganizationRole;
use App\Enums\Priority;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->create();
    $this->organization->members()->attach($this->admin, ['role' => OrganizationRole::Admin]);
    $this->admin->switchOrganization($this->organization);
    $this->team = Team::factory()->for($this->organization)->create();
});

function memberOf(Organization $organization): User
{
    $user = User::factory()->create();
    $organization->members()->attach($user, ['role' => OrganizationRole::Member]);
    $user->switchOrganization($organization);

    return $user;
}

test('admins can entrust a mission to a team', function () {
    $this->actingAs($this->admin)
        ->post(route('missions.store'), [
            'title' => 'Redesign onboarding',
            'description' => 'Make the first five minutes great.',
            'team_id' => $this->team->id,
            'priority' => 'high',
            'due_date' => now()->addWeek()->toDateString(),
        ])
        ->assertRedirect();

    $mission = $this->organization->missions()->sole();

    expect($mission->title)->toBe('Redesign onboarding')
        ->and($mission->status)->toBe(MissionStatus::Open)
        ->and($mission->priority)->toBe(Priority::High)
        ->and($mission->created_by)->toBe($this->admin->id);
});

test('missions can only be entrusted to teams of the organization', function () {
    $foreignTeam = Team::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('missions.store'), ['title' => 'Nope', 'team_id' => $foreignTeam->id, 'priority' => 'normal'])
        ->assertSessionHasErrors('team_id');
});

test('members can view missions but not manage them', function () {
    $member = memberOf($this->organization);
    $mission = Mission::factory()->for($this->team)->create();

    $this->actingAs($member)->get(route('missions.show', $mission))->assertOk();
    $this->actingAs($member)->post(route('missions.store'), ['title' => 'x', 'team_id' => $this->team->id, 'priority' => 'normal'])->assertForbidden();
    $this->actingAs($member)->put(route('missions.update', $mission), ['title' => 'x', 'team_id' => $this->team->id, 'priority' => 'normal'])->assertForbidden();
    $this->actingAs($member)->post(route('missions.cancel', $mission))->assertForbidden();
    $this->actingAs($member)->delete(route('missions.destroy', $mission))->assertForbidden();
});

test('the list shows active missions by default, most urgent first', function () {
    Mission::factory()->for($this->team)->create(['title' => 'Low', 'priority' => Priority::Low]);
    Mission::factory()->for($this->team)->create(['title' => 'Urgent', 'priority' => Priority::Urgent]);
    Mission::factory()->for($this->team)->status(MissionStatus::Completed)->create(['title' => 'Done']);
    Mission::factory()->create(['title' => 'Other organization']);

    $this->actingAs($this->admin)
        ->get(route('missions.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('missions/Index')
            ->has('missions', 2)
            ->where('missions.0.title', 'Urgent')
            ->where('missions.1.title', 'Low')
        );
});

test('the list can be filtered by status and team', function () {
    $otherTeam = Team::factory()->for($this->organization)->create();
    Mission::factory()->for($this->team)->status(MissionStatus::Completed)->create(['title' => 'Done here']);
    Mission::factory()->for($otherTeam)->status(MissionStatus::Completed)->create(['title' => 'Done there']);

    $this->actingAs($this->admin)
        ->get(route('missions.index', ['status' => 'completed', 'team' => $this->team->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('missions', 1)
            ->where('missions.0.title', 'Done here')
            ->where('filters.status', 'completed')
        );
});

test('overdue active missions are flagged', function () {
    $mission = Mission::factory()->for($this->team)->overdue()->create();
    $done = Mission::factory()->for($this->team)->overdue()->status(MissionStatus::Completed)->create();

    expect($mission->isOverdue())->toBeTrue()
        ->and($done->isOverdue())->toBeFalse();
});

test('admins can cancel and reopen a mission', function () {
    $mission = Mission::factory()->for($this->team)->create();

    $this->actingAs($this->admin)->post(route('missions.cancel', $mission));
    expect($mission->fresh()->status)->toBe(MissionStatus::Cancelled);

    $this->actingAs($this->admin)->post(route('missions.cancel', $mission))->assertSessionHasErrors('status');

    $this->actingAs($this->admin)->post(route('missions.reopen', $mission));
    expect($mission->fresh()->status)->toBe(MissionStatus::Open);
});

test('admins can edit and delete a mission', function () {
    $mission = Mission::factory()->for($this->team)->create();

    $this->actingAs($this->admin)->put(route('missions.update', $mission), [
        'title' => 'Updated',
        'team_id' => $this->team->id,
        'priority' => 'urgent',
    ])->assertRedirect(route('missions.show', $mission));

    expect($mission->fresh()->title)->toBe('Updated')
        ->and($mission->fresh()->priority)->toBe(Priority::Urgent);

    $this->actingAs($this->admin)->delete(route('missions.destroy', $mission))->assertRedirect(route('missions.index'));
    expect(Mission::find($mission->id))->toBeNull();
});

test('missions of another organization are not found', function () {
    $foreign = Mission::factory()->create();

    $this->actingAs($this->admin)->get(route('missions.show', $foreign))->assertNotFound();
    $this->actingAs($this->admin)->post(route('missions.cancel', $foreign))->assertNotFound();
    $this->actingAs($this->admin)->delete(route('missions.destroy', $foreign))->assertNotFound();
});
