<?php

use App\Delegation\AssigneeSuggester;
use App\Delegation\RiskDetector;
use App\Delegation\WorkloadCalculator;
use App\Enums\OrganizationRole;
use App\Enums\TaskStatus;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['delego.workload.weekly_capacity_hours' => 20, 'delego.workload.default_estimate_hours' => 3]);

    $this->organization = Organization::factory()->create();
    $this->lead = User::factory()->create(['name' => 'Lea']);
    $this->busy = User::factory()->create(['name' => 'Busy']);
    $this->free = User::factory()->create(['name' => 'Free']);

    foreach ([$this->lead, $this->busy, $this->free] as $user) {
        $this->organization->members()->attach($user, ['role' => OrganizationRole::Member]);
        $user->switchOrganization($this->organization);
    }

    $this->team = Team::factory()->for($this->organization)->ledBy($this->lead)->create();
    $this->team->members()->attach([$this->busy->id, $this->free->id]);
    $this->mission = Mission::factory()->for($this->team)->create(['due_date' => null]);
});

function openTask(Mission $mission, User $assignee, ?int $hours, array $attributes = []): Task
{
    return Task::factory()->for($mission)->create([
        'assignee_id' => $assignee->id,
        'estimate_hours' => $hours,
        'status' => TaskStatus::InProgress,
        ...$attributes,
    ]);
}

test('workload counts open estimated hours, with a default for unestimated tasks', function () {
    openTask($this->mission, $this->busy, 16);
    openTask($this->mission, $this->busy, null);
    openTask($this->mission, $this->busy, 8, ['status' => TaskStatus::Done]);
    openTask($this->mission, $this->free, 4);

    $workloads = app(WorkloadCalculator::class)->forTeam($this->team)->keyBy(fn ($workload) => $workload->user->name);

    expect($workloads['Busy']->hours)->toBe(19)
        ->and($workloads['Busy']->openTasks)->toBe(2)
        ->and($workloads['Busy']->level())->toBe('balanced')
        ->and($workloads['Free']->level())->toBe('available')
        ->and($workloads['Lea']->hours)->toBe(0);
});

test('workload spans every team of the organization', function () {
    $otherTeam = Team::factory()->for($this->organization)->create();
    openTask(Mission::factory()->for($otherTeam)->create(), $this->busy, 25);

    $busy = app(WorkloadCalculator::class)->forTeam($this->team)->firstWhere('user.id', $this->busy->id);

    expect($busy->level())->toBe('overloaded');
});

test('the suggestion favours the person with the most room', function () {
    openTask($this->mission, $this->busy, 18);
    openTask($this->mission, $this->lead, 6);

    $suggestions = app(AssigneeSuggester::class)->suggest($this->mission);

    expect($suggestions->pluck('user.name')->all())->toBe(['Free', 'Lea', 'Busy'])
        ->and($suggestions[0]['reason'])->toBe('No open work');
});

test('overdue work pushes a person down the suggestions', function () {
    openTask($this->mission, $this->busy, 18);
    openTask($this->mission, $this->free, 2, ['due_date' => now()->subDays(2)]);
    openTask($this->mission, $this->lead, 6);

    $first = app(AssigneeSuggester::class)->suggest($this->mission)->first();

    expect($first['user']['name'])->toBe('Lea');
});

test('risks flag overdue and soon-due missions, stuck tasks and overloads', function () {
    $overdue = Mission::factory()->for($this->team)->create(['title' => 'Late', 'due_date' => now()->subDay()]);
    $soon = Mission::factory()->for($this->team)->create(['title' => 'Soon', 'due_date' => now()->addDays(2)]);
    Task::factory()->for($soon)->create(['title' => 'Unowned']);
    $blocked = openTask($this->mission, $this->busy, 2, ['status' => TaskStatus::Blocked, 'blocked_reason' => 'No access', 'title' => 'Stuck']);
    $blocked->forceFill(['updated_at' => now()->subDays(4)])->saveQuietly();
    openTask($this->mission, $this->free, 30);

    $risks = app(RiskDetector::class)->detect($this->organization);
    $kinds = $risks->pluck('kind')->all();

    expect($risks->first()['level'])->toBe('high')
        ->and($risks->first()['title'])->toBe('"Late" is overdue')
        ->and($kinds)->toContain('mission_due_soon', 'tasks_unassigned', 'task_blocked', 'member_overloaded')
        ->and($risks->firstWhere('kind', 'task_blocked')['detail'])->toBe('No access');

    expect($overdue->isOverdue())->toBeTrue();
});

test('a team without lead is a risk for its missions', function () {
    $team = Team::factory()->for($this->organization)->create();
    $mission = Mission::factory()->for($team)->create(['title' => 'Orphan']);

    $risks = app(RiskDetector::class)->detect($this->organization, $mission);

    expect($risks->pluck('kind')->all())->toBe(['mission_without_lead']);
});

test('a healthy organization has no risks', function () {
    openTask($this->mission, $this->free, 4);

    expect(app(RiskDetector::class)->detect($this->organization))->toBeEmpty();
});

test('the dashboard gives the delegation overview', function () {
    openTask($this->mission, $this->free, 4);
    Mission::factory()->for($this->team)->create(['due_date' => now()->subDay()]);

    $this->actingAs($this->free)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.active_missions', 2)
            ->where('stats.my_open_tasks', 1)
            ->has('risks', 1)
            ->has('missions', 2)
            ->has('workload', 3)
        );
});

test('the mission page suggests assignees to the lead only', function () {
    $this->actingAs($this->lead)
        ->get(route('missions.show', $this->mission))
        ->assertInertia(fn (Assert $page) => $page->has('suggestions', 3)->has('risks', 0));

    $this->actingAs($this->free)
        ->get(route('missions.show', $this->mission))
        ->assertInertia(fn (Assert $page) => $page->has('suggestions', 0));
});

test('the team page shows the workload of its people', function () {
    $this->actingAs($this->lead)
        ->get(route('teams.show', $this->team))
        ->assertInertia(fn (Assert $page) => $page->has('workload', 3));
});
