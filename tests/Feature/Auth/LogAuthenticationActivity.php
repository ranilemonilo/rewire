<?php

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $activity = Activity::query()->latest('id')->first();

    expect($activity->event)->toBe('login');
    expect($activity->description)->toContain('logged in');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();

    $activity = Activity::query()->latest('id')->first();

    expect($activity->event)->toBe('failed_login');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();

    $activity = Activity::query()->latest('id')->first();

    expect($activity->event)->toBe('logout');
    expect($activity->description)->toContain('logged out');
});