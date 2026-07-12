<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('password reset link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertSuccessful();
});

test('password reset links can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class);
});

test('password reset screen can be rendered', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->get(route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]));

    $response->assertSuccessful();
});

test('password can be reset with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('login', absolute: false));
    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertInvalid(['email']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'new-password',
    ])->assertRedirect(route('server.index', absolute: false));

    $this->assertAuthenticated();
});
