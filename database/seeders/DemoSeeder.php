<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\MissionStatus;
use App\Enums\OrganizationRole;
use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Mission;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\MissionEntrusted;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskBlocked;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A believable organization to explore the app with, dated relative to today
 * so the demo never looks stale.
 */
class DemoSeeder extends Seeder
{
    /**
     * The three people the demo lets visitors log in as.
     */
    public const PERSONAS = [
        'admin' => 'maya@task-delegation.test',
        'lead' => 'tom@task-delegation.test',
        'member' => 'sara@task-delegation.test',
    ];

    /** @var array<string, User> */
    private array $people = [];

    private Organization $organization;

    public function run(): void
    {
        // Replaying months of history: nobody is listening, so nothing is broadcast.
        config(['broadcasting.default' => 'null', 'queue.default' => 'sync']);

        $this->organization = Organization::create(['name' => 'Halcyon Studio']);

        $this->person('maya', 'Maya Chen', OrganizationRole::Owner);
        $this->person('tom', 'Tom Rivera', OrganizationRole::Member);
        $this->person('sara', 'Sara Okafor', OrganizationRole::Member);
        $this->person('ines', 'Inès Moreau', OrganizationRole::Admin);
        $this->person('kenji', 'Kenji Watanabe', OrganizationRole::Member);
        $this->person('lucas', 'Lucas Andersen', OrganizationRole::Member);
        $this->person('amara', 'Amara Diallo', OrganizationRole::Member);
        $this->person('noah', 'Noah Fischer', OrganizationRole::Member);
        $this->person('priya', 'Priya Nair', OrganizationRole::Member);

        $design = $this->team('Product Design', 'Research, interface and brand.', 'tom', ['sara', 'amara']);
        $engineering = $this->team('Engineering', 'Web app, API and infrastructure.', 'kenji', ['lucas', 'noah', 'sara']);
        $marketing = $this->team('Marketing', 'Launches, content and growth.', 'priya', []);

        $onboarding = $this->mission($design, 'Redesign the onboarding flow', Priority::High, 9, MissionStatus::InProgress, 21,
            'New users drop before creating their first project. Rework the first five minutes: sign-up, empty states and the first mission.');
        $this->task($onboarding, 'Interview five recent sign-ups', 'sara', TaskStatus::Done, 6, -12);
        $this->task($onboarding, 'Map the current onboarding funnel', 'amara', TaskStatus::Done, 4, -10);
        $this->task($onboarding, 'Wireframe the new first-run screens', 'sara', TaskStatus::InProgress, 10, 3);
        $this->task($onboarding, 'Write empty-state copy', 'amara', TaskStatus::Blocked, 3, 4, 'Waiting for the final product naming from marketing');
        $this->task($onboarding, 'Prototype and test with three users', null, TaskStatus::Todo, 8, 8);
        $this->comment($onboarding, 'sara', 'Interviews are done: the biggest drop is the blank dashboard. Notes are in the research folder.', -9);
        $this->comment($onboarding, 'tom', 'Great insight. Let\'s make the first mission a guided one.', -9);

        $brand = $this->mission($design, 'Refresh the visual identity', Priority::Normal, 30, MissionStatus::Open, 45,
            'Update the logo, palette and type scale ahead of the autumn launch.');
        $this->task($brand, 'Audit current brand assets', 'amara', TaskStatus::Todo, 4, 10);
        $this->task($brand, 'Moodboards for three directions', null, TaskStatus::Todo, 6, 15);

        $api = $this->mission($engineering, 'Public API v1', Priority::Urgent, -2, MissionStatus::InProgress, 30,
            'Partners need read access to missions and tasks. Token auth, rate limits and documentation.');
        $this->task($api, 'Design the resource schema', 'kenji', TaskStatus::Done, 6, -20);
        $this->task($api, 'Token authentication', 'lucas', TaskStatus::Done, 8, -12);
        $this->task($api, 'Rate limiting per token', 'noah', TaskStatus::InProgress, 6, -3);
        $this->task($api, 'Generate the OpenAPI documentation', 'lucas', TaskStatus::InProgress, 10, 1);
        $this->task($api, 'Load test the endpoints', 'noah', TaskStatus::Blocked, 8, 2, 'Staging environment is down since the database migration');
        $this->comment($api, 'kenji', 'We are two days late. Noah, can you pair with Lucas on the docs once staging is back?', -1);
        $this->comment($api, 'maya', 'Partners are fine with Friday. Let\'s not cut the load tests.', 0);

        $billing = $this->mission($engineering, 'Migrate billing to the new provider', Priority::High, 2, MissionStatus::InProgress, 18,
            'Move subscriptions and invoices without double-charging anyone.');
        $this->task($billing, 'Export existing subscriptions', 'lucas', TaskStatus::Done, 4, -6);
        $this->task($billing, 'Map plans to the new provider', 'sara', TaskStatus::InProgress, 5, 1);
        $this->task($billing, 'Webhook handling for renewals', 'noah', TaskStatus::Todo, 8, 2);
        $this->task($billing, 'Dry run on a copy of production', null, TaskStatus::Todo, 6, 2);

        $performance = $this->mission($engineering, 'Cut dashboard load time in half', Priority::Normal, -20, MissionStatus::Completed, 40,
            'The dashboard takes four seconds on large organizations.');
        $this->task($performance, 'Profile the slowest queries', 'kenji', TaskStatus::Done, 4, -35);
        $this->task($performance, 'Add the missing indexes', 'noah', TaskStatus::Done, 3, -30);
        $this->task($performance, 'Cache the workload computation', 'lucas', TaskStatus::Done, 6, -25);
        $this->task($performance, 'Lazy-load the activity feed', 'lucas', TaskStatus::Cancelled, 4, -25);

        $launch = $this->mission($marketing, 'Autumn product launch', Priority::High, 25, MissionStatus::Open, 10,
            'Announce the new onboarding and the public API.');
        $this->task($launch, 'Launch plan and timeline', 'priya', TaskStatus::InProgress, 6, 5);
        $this->task($launch, 'Landing page copy', null, TaskStatus::Todo, 5, 15);

        $this->mission($marketing, 'Customer stories series', Priority::Low, null, MissionStatus::Cancelled, 60,
            'Paused until the launch is out.');

        $this->notifications($api, $onboarding);
    }

    private function person(string $key, string $name, OrganizationRole $role): void
    {
        $user = User::create([
            'name' => $name,
            'email' => "{$key}@task-delegation.test",
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->organization->members()->attach($user, ['role' => $role, 'created_at' => now()->subDays(90)]);
        $user->switchOrganization($this->organization);
        $this->people[$key] = $user;
    }

    /**
     * @param  list<string>  $members
     */
    private function team(string $name, string $description, string $lead, array $members): Team
    {
        $team = $this->organization->teams()->create([
            'name' => $name,
            'description' => $description,
            'lead_id' => $this->people[$lead]->id,
        ]);
        $team->members()->attach(array_map(fn (string $key) => $this->people[$key]->id, $members));

        return $team;
    }

    private function mission(Team $team, string $title, Priority $priority, ?int $dueInDays, MissionStatus $status, int $createdDaysAgo, string $description): Mission
    {
        $createdAt = now()->subDays($createdDaysAgo);

        $mission = $this->organization->missions()->create([
            'team_id' => $team->id,
            'created_by' => $this->people['maya']->id,
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'status' => $status,
            'due_date' => $dueInDays === null ? null : today()->addDays($dueInDays),
            'completed_at' => $status === MissionStatus::Completed ? now()->subDays(abs($dueInDays ?? 0) + 2) : null,
        ]);
        $mission->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

        $this->activity($mission, 'maya', ActivityType::MissionCreated, [], $createdAt);

        if ($status === MissionStatus::Completed) {
            $this->activity($mission, $team->lead ? $this->keyOf($team->lead) : 'maya', ActivityType::MissionCompleted, [], $mission->completed_at ?? now());
        } elseif ($status === MissionStatus::Cancelled) {
            $this->activity($mission, 'maya', ActivityType::MissionCancelled, [], $createdAt->copy()->addDays(5));
        }

        return $mission;
    }

    private function task(Mission $mission, string $title, ?string $assignee, TaskStatus $status, int $hours, int $dueInDays, ?string $blockedReason = null): void
    {
        $lead = $mission->team->lead;
        $createdAt = $mission->created_at?->copy()->addDay() ?? now();

        $task = $mission->tasks()->create([
            'organization_id' => $this->organization->id,
            'assignee_id' => $assignee ? $this->people[$assignee]->id : null,
            'created_by' => $lead?->id,
            'title' => $title,
            'status' => $status,
            'blocked_reason' => $blockedReason,
            'estimate_hours' => $hours,
            'due_date' => today()->addDays($dueInDays),
            'position' => $mission->tasks()->count() + 1,
            'completed_at' => $status === TaskStatus::Done ? now()->subDays(max(1, -$dueInDays)) : null,
        ]);

        // Blocked tasks have been stuck for a few days, so the risk detector notices them.
        $updatedAt = $status === TaskStatus::Blocked ? now()->subDays(3) : $createdAt;
        $task->forceFill(['created_at' => $createdAt, 'updated_at' => $updatedAt])->saveQuietly();

        $actor = $lead ? $this->keyOf($lead) : 'maya';
        $this->activity($mission, $actor, ActivityType::TaskCreated, ['task' => $title], $createdAt, $task);

        if ($assignee) {
            $this->activity($mission, $actor, ActivityType::TaskAssigned, ['task' => $title, 'assignee' => $this->people[$assignee]->name], $createdAt, $task);
        }

        if ($status !== TaskStatus::Todo && $assignee) {
            $this->activity($mission, $assignee, ActivityType::TaskStatusChanged, array_filter([
                'task' => $title,
                'from' => TaskStatus::Todo->value,
                'to' => $status->value,
                'reason' => $blockedReason,
            ]), $status === TaskStatus::Blocked ? now()->subDays(3) : $createdAt->copy()->addDays(2), $task);
        }
    }

    private function comment(Mission $mission, string $author, string $body, int $daysFromNow): void
    {
        $at = now()->addDays($daysFromNow)->subHours(3);
        $comment = Comment::create([
            'organization_id' => $this->organization->id,
            'mission_id' => $mission->id,
            'user_id' => $this->people[$author]->id,
            'body' => $body,
        ]);
        $comment->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function activity(Mission $mission, string $actor, ActivityType $type, array $properties, CarbonInterface $at, ?Task $task = null): void
    {
        Activity::record($mission, $this->people[$actor], $type, $properties, $task)
            ->forceFill(['created_at' => $at])
            ->saveQuietly();
    }

    private function notifications(Mission $api, Mission $onboarding): void
    {
        $blocked = $onboarding->tasks()->where('status', TaskStatus::Blocked)->firstOrFail();
        $mapping = Task::where('title', 'Map plans to the new provider')->firstOrFail();

        $this->people['tom']->notifyNow(new TaskBlocked($blocked, $this->people['amara']), ['database']);
        $this->people['sara']->notifyNow(new TaskAssigned($mapping, $this->people['kenji']), ['database']);
        $this->people['kenji']->notifyNow(new MissionEntrusted($api), ['database']);
    }

    private function keyOf(User $user): string
    {
        return strstr($user->email, '@', true) ?: 'maya';
    }
}
