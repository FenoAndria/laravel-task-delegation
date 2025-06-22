<?php

use App\Enums\ActivityType;
use App\Enums\OrganizationRole;
use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->create(['name' => 'Ada']);
    $this->organization->members()->attach($this->admin, ['role' => OrganizationRole::Admin]);
    $this->admin->switchOrganization($this->organization);

    $this->member = User::factory()->create(['name' => 'Bob']);
    $this->organization->members()->attach($this->member, ['role' => OrganizationRole::Member]);
    $this->member->switchOrganization($this->organization);

    $this->team = Team::factory()->for($this->organization)->ledBy($this->admin)->create();
    $this->team->members()->attach($this->member);
    $this->mission = Mission::factory()->for($this->team)->create();
});

function activityTypes(Mission $mission): array
{
    return $mission->activities()->orderBy('id')->pluck('type')->map->value->all();
}

test('the life of a mission is recorded', function () {
    $this->actingAs($this->admin)->post(route('tasks.store', $this->mission), ['title' => 'Draft', 'assignee_id' => $this->member->id]);
    $task = $this->mission->tasks()->sole();

    $this->actingAs($this->member)->put(route('tasks.status.update', $task), ['status' => 'in_progress']);
    $this->actingAs($this->member)->put(route('tasks.status.update', $task), ['status' => 'done']);
    $this->actingAs($this->admin)->post(route('missions.complete', $this->mission));

    expect(activityTypes($this->mission))->toBe([
        'task_created',
        'task_assigned',
        'mission_started',
        'task_status_changed',
        'task_status_changed',
        'mission_completed',
    ]);
});

test('activity reads as a sentence', function () {
    $task = Task::factory()->for($this->mission)->create(['title' => 'Draft', 'assignee_id' => $this->member->id, 'status' => TaskStatus::InProgress]);

    $this->actingAs($this->member)->put(route('tasks.status.update', $task), ['status' => 'blocked', 'blocked_reason' => 'Waiting for copy']);
    $this->actingAs($this->admin)->put(route('tasks.update', $task), ['title' => 'Draft', 'assignee_id' => null]);

    $descriptions = $this->mission->activities()->orderBy('id')->get()->map->description()->all();

    expect($descriptions)->toBe([
        'marked "Draft" as blocked: Waiting for copy',
        'unassigned "Draft"',
    ]);
});

test('deleting a task keeps a readable trace', function () {
    $task = Task::factory()->for($this->mission)->create(['title' => 'Obsolete']);

    $this->actingAs($this->admin)->delete(route('tasks.destroy', $task));

    expect($this->mission->activities()->sole()->description())->toBe('deleted the task "Obsolete"');
});

test('cancelling and reopening a mission is recorded', function () {
    $this->actingAs($this->admin)->post(route('missions.cancel', $this->mission));
    $this->actingAs($this->admin)->post(route('missions.reopen', $this->mission));

    expect(activityTypes($this->mission))->toBe([ActivityType::MissionCancelled->value, ActivityType::MissionReopened->value]);
});

test('organization members can comment on a mission', function () {
    $this->actingAs($this->member)
        ->post(route('comments.store', $this->mission), ['body' => 'Who owns the copy?'])
        ->assertRedirect();

    $comment = $this->mission->comments()->sole();

    expect($comment->body)->toBe('Who owns the copy?')
        ->and($comment->user_id)->toBe($this->member->id);
});

test('comments cannot be empty', function () {
    $this->actingAs($this->member)
        ->post(route('comments.store', $this->mission), ['body' => ''])
        ->assertSessionHasErrors('body');
});

test('authors and managers can delete a comment, others cannot', function () {
    $comment = Comment::factory()->for($this->mission)->create(['user_id' => $this->admin->id]);
    $own = Comment::factory()->for($this->mission)->create(['user_id' => $this->member->id]);

    $this->actingAs($this->member)->delete(route('comments.destroy', $comment))->assertForbidden();
    $this->actingAs($this->member)->delete(route('comments.destroy', $own))->assertRedirect();
    $this->actingAs($this->admin)->delete(route('comments.destroy', $comment))->assertRedirect();

    expect($this->mission->comments()->count())->toBe(0);
});

test('the mission page shows comments and activity as one thread', function () {
    $this->actingAs($this->admin)->post(route('missions.cancel', $this->mission));
    $this->travel(1)->minutes();
    $this->actingAs($this->member)->post(route('comments.store', $this->mission), ['body' => 'Why?']);

    $this->actingAs($this->member)
        ->get(route('missions.show', $this->mission))
        ->assertInertia(fn (Assert $page) => $page
            ->has('timeline', 2)
            ->where('timeline.0.kind', 'activity')
            ->where('timeline.0.actor', 'Ada')
            ->where('timeline.0.body', 'cancelled the mission')
            ->where('timeline.1.kind', 'comment')
            ->where('timeline.1.body', 'Why?')
            ->where('timeline.1.can_delete', true)
        );
});

test('missions and comments of another organization are out of reach', function () {
    $foreignMission = Mission::factory()->create();
    $foreignComment = Comment::factory()->create();

    $this->actingAs($this->admin)->post(route('comments.store', $foreignMission), ['body' => 'Hi'])->assertNotFound();
    $this->actingAs($this->admin)->delete(route('comments.destroy', $foreignComment))->assertNotFound();
});
