<?php

use App\Broadcasting\MissionChannel;
use App\Enums\OrganizationRole;
use App\Enums\TaskStatus;
use App\Events\MissionChanged;
use App\Models\Comment;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TaskBlocked;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->lead = User::factory()->create(['name' => 'Tom Rivera']);
    $this->organization->members()->attach($this->lead, ['role' => OrganizationRole::Member]);
    $this->lead->switchOrganization($this->organization);
    $this->team = Team::factory()->for($this->organization)->ledBy($this->lead)->create();
    $this->mission = Mission::factory()->for($this->team)->create();
});

test('organization members join the mission channel and appear by name', function () {
    expect((new MissionChannel)->join($this->lead, $this->mission))->toBe(['id' => $this->lead->id, 'name' => 'Tom Rivera']);
});

test('outsiders cannot join the mission channel', function () {
    expect((new MissionChannel)->join(User::factory()->create(), $this->mission))->toBeFalse();
});

test('the mission is broadcast as changed on its presence channel', function () {
    $event = new MissionChanged($this->mission->id);

    expect($event->broadcastOn())->toEqual([new PresenceChannel("missions.{$this->mission->id}")])
        ->and($event->broadcastAs())->toBe('mission.changed')
        ->and($event->broadcastWith())->toBe(['mission_id' => $this->mission->id]);
});

test('task changes and comments refresh the people looking at the mission', function () {
    Event::fake([MissionChanged::class]);
    $task = Task::factory()->for($this->mission)->create(['assignee_id' => $this->lead->id]);

    $this->actingAs($this->lead)->put(route('tasks.status.update', $task), ['status' => TaskStatus::InProgress->value]);
    Event::assertDispatched(MissionChanged::class, fn (MissionChanged $event) => $event->missionId === $this->mission->id);

    Event::fake([MissionChanged::class]);
    $this->actingAs($this->lead)->post(route('comments.store', $this->mission), ['body' => 'On it']);
    Event::assertDispatchedTimes(MissionChanged::class, 1);

    Event::fake([MissionChanged::class]);
    Comment::first()?->delete();
    Event::assertDispatchedTimes(MissionChanged::class, 1);
});

test('notifications are also pushed to open tabs', function () {
    $task = Task::factory()->for($this->mission)->create();

    expect((new TaskBlocked($task, $this->lead))->via($this->lead))->toContain('broadcast');
});
