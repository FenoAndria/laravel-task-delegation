<?php

use App\Delegation\RiskDetector;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('the demo seeder builds a living organization', function () {
    $this->seed(DemoSeeder::class);

    $organization = Organization::sole();

    expect($organization->members()->count())->toBe(9)
        ->and($organization->teams()->count())->toBe(3)
        ->and($organization->missions()->count())->toBe(7)
        ->and(app(RiskDetector::class)->detect($organization))->not->toBeEmpty();

    foreach (DemoSeeder::PERSONAS as $email) {
        expect(User::where('email', $email)->exists())->toBeTrue();
    }
});

test('every persona reaches the dashboard', function (string $persona) {
    config(['delego.demo' => true]);
    $this->seed(DemoSeeder::class);

    $this->post(route('demo.login', $persona))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs(User::firstWhere('email', DemoSeeder::PERSONAS[$persona]));

    $this->get(route('dashboard'))->assertOk();
})->with(array_keys(DemoSeeder::PERSONAS));

test('one-click login does not exist outside demo mode', function () {
    config(['delego.demo' => false]);
    $this->seed(DemoSeeder::class);

    $this->post(route('demo.login', 'admin'))->assertNotFound();
    $this->assertGuest();
});

test('unknown personas are not found', function () {
    config(['delego.demo' => true]);

    $this->post(route('demo.login', 'root'))->assertNotFound();
});

test('the reset command refuses to run outside demo mode', function () {
    config(['delego.demo' => false]);

    $this->artisan('demo:reset')->assertFailed();
});
