<?php

use App\Enums\OrganizationRole;
use App\Enums\TaskStatus;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\CommentPosted;
use App\Notifications\MissionCompleted;
use App\Notifications\MissionEntrusted;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskBlocked;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

function inOrganization(Organization $organization, OrganizationRole $role = OrganizationRole::Member): User
{
    $user = User::factory()->create();
    $organization->members()->attach($user, ['role' => $role]);
    $user->switchOrganization($organization);

    return $user;
}

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->admin = inOrganization($this->organization, OrganizationRole::Admin);
    $this->lead = inOrganization($this->organization);
    $this->member = inOrganization($this->organization);
    $this->team = Team::factory()->for($this->organization)->ledBy($this->lead)->create();
    $this->team->members()->attach($this->member);
});

describe('who gets notified', function () {
    beforeEach(fn () => Notification::fake());

    test('the team lead learns about a new mission, by email too', function () {
        $this->actingAs($this->admin)->post(route('missions.store'), [
            'title' => 'Launch', 'team_id' => $this->team->id, 'priority' => 'high',
        ]);

        Notification::assertSentTo($this->lead, MissionEntrusted::class, fn ($notification, $channels) => $channels === ['database', 'mail']);
        Notification::assertNotSentTo($this->admin, MissionEntrusted::class);
    });

    test('the assignee learns about a task, unless they assigned it to themselves', function () {
        $mission = Mission::factory()->for($this->team)->create();

        $this->actingAs($this->lead)->post(route('tasks.store', $mission), ['title' => 'For Bob', 'assignee_id' => $this->member->id]);
        $this->actingAs($this->lead)->post(route('tasks.store', $mission), ['title' => 'For me', 'assignee_id' => $this->lead->id]);

        Notification::assertSentTo($this->member, TaskAssigned::class);
        Notification::assertNotSentTo($this->lead, TaskAssigned::class);
    });

    test('reassigning a task notifies the new assignee', function () {
        $task = Task::factory()->for(Mission::factory()->for($this->team))->create();

        $this->actingAs($this->lead)->put(route('tasks.update', $task), ['title' => $task->title, 'assignee_id' => $this->member->id]);

        Notification::assertSentTo($this->member, TaskAssigned::class);
    });

    test('the lead learns when a task gets blocked', function () {
        $task = Task::factory()->for(Mission::factory()->for($this->team))->status(TaskStatus::InProgress)->create(['assignee_id' => $this->member->id]);

        $this->actingAs($this->member)->put(route('tasks.status.update', $task), ['status' => 'blocked', 'blocked_reason' => 'No access']);

        Notification::assertSentTo($this->lead, TaskBlocked::class);
    });

    test('the creator learns when their mission is completed', function () {
        $mission = Mission::factory()->for($this->team)->create(['created_by' => $this->admin->id]);
        Task::factory()->for($mission)->status(TaskStatus::Done)->create();

        $this->actingAs($this->lead)->post(route('missions.complete', $mission));

        Notification::assertSentTo($this->admin, MissionCompleted::class);
    });

    test('people involved in a mission learn about new comments, not the author', function () {
        $mission = Mission::factory()->for($this->team)->create(['created_by' => $this->admin->id]);
        Task::factory()->for($mission)->create(['assignee_id' => $this->member->id]);
        $bystander = inOrganization($this->organization);

        $this->actingAs($this->member)->post(route('comments.store', $mission), ['body' => 'Done on my side']);

        Notification::assertSentTo([$this->admin, $this->lead], CommentPosted::class);
        Notification::assertNotSentTo([$this->member, $bystander], CommentPosted::class);
    });
});

test('notifications are listed per organization and counted when unread', function () {
    $mission = Mission::factory()->for($this->team)->create();
    $this->actingAs($this->lead)->post(route('tasks.store', $mission), ['title' => 'Read me', 'assignee_id' => $this->member->id]);

    $elsewhere = Organization::factory()->withMember($this->member)->create();
    $this->member->switchOrganization($this->organization);
    $this->member->notify(new MissionEntrusted(Mission::factory()->for(Team::factory()->for($elsewhere))->create()));

    $this->actingAs($this->member)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/Index')
            ->has('notifications', 1)
            ->where('notifications.0.read', false)
            ->where('unreadNotifications', 1)
        );
});

test('opening a notification marks it as read and follows its link', function () {
    $mission = Mission::factory()->for($this->team)->create();
    $this->actingAs($this->lead)->post(route('tasks.store', $mission), ['title' => 'Open me', 'assignee_id' => $this->member->id]);
    $notification = $this->member->notifications()->sole();

    $this->actingAs($this->member)
        ->get(route('notifications.show', $notification->id))
        ->assertRedirect(route('missions.show', $mission));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('all notifications can be marked as read', function () {
    $mission = Mission::factory()->for($this->team)->create();
    $this->actingAs($this->lead)->post(route('tasks.store', $mission), ['title' => 'One', 'assignee_id' => $this->member->id]);
    $this->actingAs($this->lead)->post(route('tasks.store', $mission), ['title' => 'Two', 'assignee_id' => $this->member->id]);

    $this->actingAs($this->member)->post(route('notifications.read'));

    expect($this->member->unreadNotifications()->count())->toBe(0);
});

test('notifications of other users cannot be opened', function () {
    $mission = Mission::factory()->for($this->team)->create();
    $this->actingAs($this->lead)->post(route('tasks.store', $mission), ['title' => 'Private', 'assignee_id' => $this->member->id]);
    $notification = $this->member->notifications()->sole();

    $this->actingAs($this->admin)->get(route('notifications.show', $notification->id))->assertNotFound();
});

test('notification emails render', function () {
    $mission = Mission::factory()->for($this->team)->create();

    $html = (string) (new MissionEntrusted($mission))->toMail($this->lead)->render();

    expect($html)->toContain($mission->title)->toContain(route('missions.show', $mission));
});
