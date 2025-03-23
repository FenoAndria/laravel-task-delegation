<?php

use App\Models\Organization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('members of an organization can visit the dashboard', function () {
    $user = User::factory()->create();
    Organization::factory()->withMember($user)->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});
