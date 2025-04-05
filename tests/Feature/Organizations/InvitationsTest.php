<?php

use App\Enums\InvitationStatus;
use App\Enums\OrganizationRole;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\InvitationSent;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Notification::fake());

function manager(OrganizationRole $role = OrganizationRole::Admin): array
{
    $user = User::factory()->create();
    $organization = Organization::factory()->withMember($user, $role)->create();

    return [$user, $organization];
}

test('admins can invite people by email', function () {
    [$admin, $organization] = manager();

    $this->actingAs($admin)
        ->post(route('invitations.store'), ['email' => 'New.Person@Example.com', 'role' => 'member'])
        ->assertRedirect(route('members.index'));

    $invitation = $organization->invitations()->sole();

    expect($invitation->email)->toBe('new.person@example.com')
        ->and($invitation->role)->toBe(OrganizationRole::Member)
        ->and($invitation->invited_by)->toBe($admin->id)
        ->and($invitation->isOpen())->toBeTrue();

    Notification::assertSentTo(
        new AnonymousNotifiable,
        InvitationSent::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'new.person@example.com',
    );
});

test('members cannot invite people', function () {
    [$member] = manager(OrganizationRole::Member);

    $this->actingAs($member)
        ->post(route('invitations.store'), ['email' => 'someone@example.com', 'role' => 'member'])
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});

test('only owners can invite owners', function () {
    [$admin] = manager();

    $this->actingAs($admin)
        ->post(route('invitations.store'), ['email' => 'someone@example.com', 'role' => 'owner'])
        ->assertSessionHasErrors('role');
});

test('existing members cannot be invited again', function () {
    [$admin, $organization] = manager();
    $member = User::factory()->create(['email' => 'member@example.com']);
    $organization->members()->attach($member, ['role' => OrganizationRole::Member]);

    $this->actingAs($admin)
        ->post(route('invitations.store'), ['email' => 'MEMBER@example.com', 'role' => 'member'])
        ->assertSessionHasErrors('email');
});

test('a person cannot have two pending invitations to the same organization', function () {
    [$admin, $organization] = manager();
    Invitation::factory()->for($organization)->create(['email' => 'someone@example.com']);

    $this->actingAs($admin)
        ->post(route('invitations.store'), ['email' => 'someone@example.com', 'role' => 'member'])
        ->assertSessionHasErrors('email');
});

test('an expired invitation does not block a new one', function () {
    [$admin, $organization] = manager();
    Invitation::factory()->for($organization)->expired()->create(['email' => 'someone@example.com']);

    $this->actingAs($admin)
        ->post(route('invitations.store'), ['email' => 'someone@example.com', 'role' => 'member'])
        ->assertSessionHasNoErrors();
});

test('guests see the invitation and are brought back to it after logging in', function () {
    $invitation = Invitation::factory()->create();

    $this->get(route('invitations.show', $invitation->token))
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/Show')
            ->where('invitation.state', 'pending')
            ->where('isGuest', true)
            ->where('hasAccount', false)
        );

    $user = User::factory()->create(['email' => $invitation->email]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('invitations.show', $invitation->token));
});

test('new users are brought back to the invitation after registering', function () {
    $invitation = Invitation::factory()->create();

    $this->get(route('invitations.show', $invitation->token));

    $this->post(route('register'), [
        'name' => 'Invited Person',
        'email' => $invitation->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('invitations.show', $invitation->token));
});

test('the invited user can accept and joins the organization with the invited role', function () {
    $invitation = Invitation::factory()->create(['role' => OrganizationRole::Admin]);
    $user = User::factory()->create(['email' => $invitation->email]);

    $this->actingAs($user)
        ->post(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('dashboard'));

    expect($user->roleIn($invitation->organization))->toBe(OrganizationRole::Admin)
        ->and($user->fresh()->current_organization_id)->toBe($invitation->organization_id)
        ->and($invitation->fresh()->status)->toBe(InvitationStatus::Accepted);
});

test('an invitation cannot be accepted from another account', function () {
    $invitation = Invitation::factory()->create();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->post(route('invitations.accept', $invitation->token))
        ->assertSessionHasErrors('invitation');

    expect($intruder->belongsToOrganization($invitation->organization))->toBeFalse();
});

test('closed invitations cannot be accepted', function (Invitation $invitation) {
    $user = User::factory()->create(['email' => $invitation->email]);

    $this->actingAs($user)
        ->post(route('invitations.accept', $invitation->token))
        ->assertSessionHasErrors('invitation');

    expect($user->belongsToOrganization($invitation->organization))->toBeFalse();
})->with([
    'expired' => fn () => Invitation::factory()->expired()->create(),
    'cancelled' => fn () => Invitation::factory()->status(InvitationStatus::Cancelled)->create(),
    'declined' => fn () => Invitation::factory()->status(InvitationStatus::Declined)->create(),
]);

test('the invited user can decline', function () {
    $invitation = Invitation::factory()->create();
    $user = User::factory()->create(['email' => $invitation->email]);

    $this->actingAs($user)->post(route('invitations.decline', $invitation->token));

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Declined)
        ->and($user->belongsToOrganization($invitation->organization))->toBeFalse();
});

test('admins can resend an invitation with a fresh expiry', function () {
    [$admin, $organization] = manager();
    $invitation = Invitation::factory()->for($organization)->expired()->create();

    $this->actingAs($admin)
        ->post(route('invitations.resend', $invitation))
        ->assertRedirect(route('members.index'));

    expect($invitation->fresh()->isOpen())->toBeTrue();
    Notification::assertSentTimes(InvitationSent::class, 1);
});

test('admins can cancel an invitation', function () {
    [$admin, $organization] = manager();
    $invitation = Invitation::factory()->for($organization)->create();

    $this->actingAs($admin)->delete(route('invitations.destroy', $invitation));

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Cancelled);
});

test('invitations of another organization cannot be managed', function () {
    [$admin] = manager();
    $foreign = Invitation::factory()->create();

    $this->actingAs($admin)->post(route('invitations.resend', $foreign))->assertNotFound();
    $this->actingAs($admin)->delete(route('invitations.destroy', $foreign))->assertNotFound();
});

test('onboarding lists the invitations waiting for the user', function () {
    $user = User::factory()->create();
    Invitation::factory()->create(['email' => $user->email]);
    Invitation::factory()->expired()->create(['email' => $user->email]);
    Invitation::factory()->create();

    $this->actingAs($user)
        ->get(route('organizations.create'))
        ->assertInertia(fn (Assert $page) => $page->has('invitations', 1));
});

test('unknown invitation tokens are not found', function () {
    $this->get(route('invitations.show', 'nope'))->assertNotFound();
});

test('the invitation email links to the invitation', function () {
    $invitation = Invitation::factory()->create();

    $html = (string) (new InvitationSent($invitation))->toMail(new AnonymousNotifiable)->render();

    expect($html)->toContain(route('invitations.show', $invitation->token))
        ->toContain($invitation->organization->name);
});
