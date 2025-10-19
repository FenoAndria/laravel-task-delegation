<?php

use App\Enums\MissionStatus;
use App\Enums\OrganizationRole;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

function aiReplies(string $content): void
{
    Http::fake(['ai.test/*' => Http::response(['choices' => [['message' => ['content' => $content]]]])]);
}

beforeEach(function () {
    config(['delego.ai' => [
        'base_url' => 'https://ai.test/v1',
        'api_key' => 'secret',
        'model' => 'free-model',
        'timeout' => 5,
        'max_per_hour' => 2,
    ]]);

    $this->organization = Organization::factory()->create();
    $this->lead = User::factory()->create();
    $this->member = User::factory()->create();

    foreach ([$this->lead, $this->member] as $user) {
        $this->organization->members()->attach($user, ['role' => OrganizationRole::Member]);
        $user->switchOrganization($this->organization);
    }

    $team = Team::factory()->for($this->organization)->ledBy($this->lead)->create();
    $team->members()->attach($this->member);
    $this->mission = Mission::factory()->for($team)->create(['title' => 'Launch the API']);
    RateLimiter::clear("ai-breakdown:{$this->organization->id}");
});

test('the lead gets a proposed breakdown to review, nothing is created yet', function () {
    aiReplies('{"tasks": [{"title": "Write the docs", "estimate_hours": 6}, {"title": "Load test", "estimate_hours": 50}, {"title": "write the docs"}]}');

    $this->actingAs($this->lead)
        ->from(route('missions.show', $this->mission))
        ->post(route('missions.breakdown', $this->mission))
        ->assertRedirect(route('missions.show', $this->mission))
        ->assertSessionHas('breakdown', [
            'mission_id' => $this->mission->id,
            'tasks' => [
                ['title' => 'Write the docs', 'estimate_hours' => 6],
                ['title' => 'Load test', 'estimate_hours' => 40],
            ],
        ]);

    expect($this->mission->tasks()->count())->toBe(0);

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer secret')
        && $request['model'] === 'free-model'
        && str_contains($request['messages'][1]['content'], 'Launch the API'));
});

test('answers wrapped in prose are still understood', function () {
    aiReplies("Sure! Here you go:\n```json\n{\"tasks\": [{\"title\": \"Ship it\", \"estimate_hours\": 2}]}\n```");

    $this->actingAs($this->lead)
        ->post(route('missions.breakdown', $this->mission))
        ->assertSessionHas('breakdown.tasks.0.title', 'Ship it');
});

test('a broken or empty answer becomes a friendly error', function (string $content) {
    aiReplies($content);

    $this->actingAs($this->lead)
        ->post(route('missions.breakdown', $this->mission))
        ->assertSessionHasErrors('breakdown');
})->with(['not json at all', '{"tasks": []}', '{"tasks": [{"title": ""}]}']);

test('a provider outage becomes a friendly error', function () {
    Http::fake(['ai.test/*' => Http::response('down', 503)]);

    $this->actingAs($this->lead)
        ->post(route('missions.breakdown', $this->mission))
        ->assertSessionHasErrors('breakdown');
});

test('each organization has an hourly quota', function () {
    aiReplies('{"tasks": [{"title": "One", "estimate_hours": 1}]}');

    $this->actingAs($this->lead)->post(route('missions.breakdown', $this->mission))->assertSessionHasNoErrors();
    $this->actingAs($this->lead)->post(route('missions.breakdown', $this->mission))->assertSessionHasNoErrors();
    $this->actingAs($this->lead)->post(route('missions.breakdown', $this->mission))->assertSessionHasErrors('breakdown');
});

test('the lead creates the tasks they kept, as edited', function () {
    $this->actingAs($this->lead)
        ->post(route('missions.breakdown.accept', $this->mission), ['tasks' => [
            ['title' => 'Write the docs (edited)', 'estimate_hours' => 5],
            ['title' => 'Load test', 'estimate_hours' => null],
        ]])
        ->assertRedirect(route('missions.show', $this->mission));

    expect($this->mission->tasks()->pluck('title')->all())->toBe(['Write the docs (edited)', 'Load test'])
        ->and($this->mission->activities()->count())->toBe(2);
});

test('only people who manage the tasks can use the assistant', function () {
    $this->actingAs($this->member)->post(route('missions.breakdown', $this->mission))->assertForbidden();
    $this->actingAs($this->member)->post(route('missions.breakdown.accept', $this->mission), ['tasks' => [['title' => 'x']]])->assertForbidden();
});

test('the assistant is off without configuration or on a closed mission', function () {
    config(['delego.ai.api_key' => null]);

    $this->actingAs($this->lead)->post(route('missions.breakdown', $this->mission))->assertSessionHasErrors('breakdown');
    $this->actingAs($this->lead)
        ->get(route('missions.show', $this->mission))
        ->assertInertia(fn (Assert $page) => $page->where('can.breakdown', false));

    config(['delego.ai.api_key' => 'secret']);
    $this->mission->update(['status' => MissionStatus::Completed]);

    $this->actingAs($this->lead)
        ->get(route('missions.show', $this->mission))
        ->assertInertia(fn (Assert $page) => $page->where('can.breakdown', false));
});

test('the lead sees the assistant on an active mission', function () {
    $this->actingAs($this->lead)
        ->get(route('missions.show', $this->mission))
        ->assertInertia(fn (Assert $page) => $page->where('can.breakdown', true));
});

test('missions of another organization are out of reach', function () {
    $foreign = Mission::factory()->create();

    $this->actingAs($this->lead)->post(route('missions.breakdown', $foreign))->assertNotFound();
    expect(Task::count())->toBe(0);
});
