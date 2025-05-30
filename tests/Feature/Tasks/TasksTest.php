<?php

use App\Enums\MissionStatus;
use App\Enums\OrganizationRole;
use App\Enums\TaskStatus;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function joinOrganization(Organization $organization, OrganizationRole $role = OrganizationRole::Member): User
{
    $user = User::factory()->create();
    $organization->members()->attach($user, ['role' => $role]);
    $user->switchOrganization($organization);

    return $user;
}

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = joinOrganization($this->organization, OrganizationRole::Admin);
    $this->lead = joinOrganization($this->organization);
    $this->member = joinOrganization($this->organization);
    $this->team = Team::factory()->for($this->organization)->ledBy($this->lead)->create();
    $this->team->members()->attach($this->member);
    $this->mission = Mission::factory()->for($this->team)->create();
});

function taskFor(Mission $mission, array $attributes = [], ?TaskStatus $status = null): Task
{
    return Task::factory()->for($mission)->status($status ?? TaskStatus::Todo)->create($attributes);
}

test('the lead breaks a mission down into tasks', function () {
    $this->actingAs($this->lead)
        ->post(route('tasks.store', $this->mission), ['title' => 'Write the brief', 'assignee_id' => $this->member->id])
        ->assertRedirect(route('missions.show', $this->mission));

    $task = $this->mission->tasks()->sole();

    expect($task->title)->toBe('Write the brief')
        ->and($task->assignee_id)->toBe($this->member->id)
        ->and($task->status)->toBe(TaskStatus::Todo)
        ->and($task->organization_id)->toBe($this->organization->id);
});

test('organization managers can add tasks too, but plain members cannot', function () {
    $this->actingAs($this->admin)->post(route('tasks.store', $this->mission), ['title' => 'By admin'])->assertSessionHasNoErrors();
    $this->actingAs($this->member)->post(route('tasks.store', $this->mission), ['title' => 'By member'])->assertForbidden();
});

test('tasks are assigned to the team lead or its members only', function () {
    $outsider = joinOrganization($this->organization);

    $this->actingAs($this->lead)
        ->post(route('tasks.store', $this->mission), ['title' => 'Nope', 'assignee_id' => $outsider->id])
        ->assertSessionHasErrors('assignee_id');

    $this->actingAs($this->lead)
        ->post(route('tasks.store', $this->mission), ['title' => 'Mine', 'assignee_id' => $this->lead->id])
        ->assertSessionHasNoErrors();
});

test('tasks cannot be added to a closed mission', function () {
    $this->mission->update(['status' => MissionStatus::Cancelled]);

    $this->actingAs($this->lead)
        ->post(route('tasks.store', $this->mission), ['title' => 'Too late'])
        ->assertSessionHasErrors('title');
});

test('assignees move their own tasks along the workflow', function () {
    $task = taskFor($this->mission, ['assignee_id' => $this->member->id]);

    $this->actingAs($this->member)->put(route('tasks.status.update', $task), ['status' => 'in_progress']);
    expect($task->fresh()->status)->toBe(TaskStatus::InProgress);

    $this->actingAs($this->member)->put(route('tasks.status.update', $task), ['status' => 'done']);
    expect($task->fresh()->status)->toBe(TaskStatus::Done)
        ->and($task->fresh()->completed_at)->not->toBeNull();
});

test('other members cannot move a task that is not theirs', function () {
    $task = taskFor($this->mission, ['assignee_id' => $this->lead->id]);

    $this->actingAs($this->member)
        ->put(route('tasks.status.update', $task), ['status' => 'in_progress'])
        ->assertForbidden();
});

test('the workflow rejects invalid transitions', function () {
    $task = taskFor($this->mission, ['assignee_id' => $this->member->id]);

    $this->actingAs($this->member)
        ->put(route('tasks.status.update', $task), ['status' => 'done'])
        ->assertSessionHasErrors('status');

    expect($task->fresh()->status)->toBe(TaskStatus::Todo);
});

test('blocking a task requires a reason, cleared once unblocked', function () {
    $task = taskFor($this->mission, ['assignee_id' => $this->member->id], TaskStatus::InProgress);

    $this->actingAs($this->member)
        ->put(route('tasks.status.update', $task), ['status' => 'blocked'])
        ->assertSessionHasErrors('blocked_reason');

    $this->actingAs($this->member)->put(route('tasks.status.update', $task), ['status' => 'blocked', 'blocked_reason' => 'Waiting for the API keys']);
    expect($task->fresh()->blocked_reason)->toBe('Waiting for the API keys');

    $this->actingAs($this->member)->put(route('tasks.status.update', $task), ['status' => 'in_progress']);
    expect($task->fresh()->blocked_reason)->toBeNull();
});

test('starting the first task starts the mission', function () {
    $task = taskFor($this->mission, ['assignee_id' => $this->member->id]);

    $this->actingAs($this->member)->put(route('tasks.status.update', $task), ['status' => 'in_progress']);

    expect($this->mission->fresh()->status)->toBe(MissionStatus::InProgress);
});

test('tasks of a closed mission cannot move', function () {
    $task = taskFor($this->mission, ['assignee_id' => $this->member->id]);
    $this->mission->update(['status' => MissionStatus::Cancelled]);

    $this->actingAs($this->member)
        ->put(route('tasks.status.update', $task), ['status' => 'in_progress'])
        ->assertSessionHasErrors('status');
});

test('the lead completes the mission once every task is done or cancelled', function () {
    $done = taskFor($this->mission, [], TaskStatus::Done);
    $open = taskFor($this->mission, [], TaskStatus::InProgress);

    $this->actingAs($this->lead)->post(route('missions.complete', $this->mission))->assertSessionHasErrors('status');

    $open->update(['status' => TaskStatus::Cancelled]);

    $this->actingAs($this->lead)->post(route('missions.complete', $this->mission))->assertSessionHasNoErrors();

    expect($this->mission->fresh()->status)->toBe(MissionStatus::Completed)
        ->and($this->mission->fresh()->completed_at)->not->toBeNull();
});

test('a mission without any finished task cannot be completed', function () {
    taskFor($this->mission, [], TaskStatus::Cancelled);

    $this->actingAs($this->lead)->post(route('missions.complete', $this->mission))->assertSessionHasErrors('status');
    $this->actingAs($this->lead)->post(route('missions.complete', Mission::factory()->for($this->team)->create()))->assertSessionHasErrors('status');
});

test('plain members cannot complete a mission', function () {
    taskFor($this->mission, [], TaskStatus::Done);

    $this->actingAs($this->member)->post(route('missions.complete', $this->mission))->assertForbidden();
});

test('the lead edits, reassigns and deletes tasks', function () {
    $task = taskFor($this->mission);

    $this->actingAs($this->lead)->put(route('tasks.update', $task), ['title' => 'Renamed', 'assignee_id' => $this->member->id, 'estimate_hours' => 4]);

    expect($task->fresh()->title)->toBe('Renamed')
        ->and($task->fresh()->assignee_id)->toBe($this->member->id)
        ->and($task->fresh()->estimate_hours)->toBe(4);

    $this->actingAs($this->member)->delete(route('tasks.destroy', $task))->assertForbidden();
    $this->actingAs($this->lead)->delete(route('tasks.destroy', $task))->assertRedirect(route('missions.show', $this->mission));
    expect(Task::find($task->id))->toBeNull();
});

test('my tasks lists open work and recently finished tasks of the current organization', function () {
    taskFor($this->mission, ['assignee_id' => $this->member->id, 'title' => 'Doing'], TaskStatus::InProgress);
    taskFor($this->mission, ['assignee_id' => $this->member->id, 'title' => 'Waiting']);
    taskFor($this->mission, ['assignee_id' => $this->member->id, 'title' => 'Old', 'completed_at' => now()->subMonth()], TaskStatus::Done);
    taskFor($this->mission, ['assignee_id' => $this->lead->id, 'title' => 'Not mine']);
    Task::factory()->create(['assignee_id' => $this->member->id, 'title' => 'Other organization']);

    $this->actingAs($this->member)
        ->get(route('tasks.mine'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('tasks/Mine')
            ->where('groups.0.status', 'in_progress')
            ->where('groups.0.tasks.0.title', 'Doing')
            ->where('groups.1.tasks.0.title', 'Waiting')
            ->has('groups.3.tasks', 0)
        );
});

test('the mission page shows tasks, progress and what the viewer can do', function () {
    taskFor($this->mission, [], TaskStatus::Done);
    taskFor($this->mission, ['assignee_id' => $this->member->id]);
    taskFor($this->mission, [], TaskStatus::Cancelled);

    $this->actingAs($this->member)
        ->get(route('missions.show', $this->mission))
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks', 3)
            ->where('mission.progress', ['done' => 1, 'total' => 2])
            ->where('can.manageTasks', false)
            ->where('tasks.1.transitions.0.value', 'in_progress')
            ->has('tasks.0.transitions', 0)
        );
});

test('tasks of another organization are not found', function () {
    $foreign = Task::factory()->create();

    $this->actingAs($this->admin)->put(route('tasks.status.update', $foreign), ['status' => 'in_progress'])->assertNotFound();
    $this->actingAs($this->admin)->delete(route('tasks.destroy', $foreign))->assertNotFound();
});
