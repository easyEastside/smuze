<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('guests are redirected from profile to login', function () {
    $response = $this->get('/profile');

    $response->assertRedirect(route('login', absolute: false));
});

test('authenticated users can view their profile', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'profile-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest Browser',
        'payload' => '[]',
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->actingAs($user)->get(route('profile.show'));

    $response->assertSuccessful();
    $response->assertSee('Profile details');
    $response->assertSee($user->name);
    $response->assertSee($user->email);
    $response->assertSee('Pest Browser');
});

test('users can update their profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated User',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirect(route('profile.show', absolute: false));
    $response->assertSessionHas('status', 'Profile updated.');

    expect($user->refresh())
        ->name->toBe('Updated User')
        ->email->toBe('updated@example.com')
        ->email_verified_at->toBeNull();
});

test('users can update their password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect(route('profile.show', absolute: false));
    $response->assertSessionHas('status', 'Password updated.');

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('users must confirm their current password before updating password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patch(route('profile.password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertInvalid(['current_password']);
});

test('users can upload and replace their avatar', function () {
    Storage::fake('public');

    Storage::disk('public')->put('avatars/old-avatar.jpg', 'old avatar');

    $user = User::factory()->create([
        'avatar_path' => 'avatars/old-avatar.jpg',
    ]);

    $oldAvatarPath = $user->avatar_path;

    $response = $this->actingAs($user)->patch(route('profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->create('avatar.jpg', 128, 'image/jpeg'),
    ]);

    $response->assertRedirect(route('profile.show', absolute: false));
    $response->assertSessionHas('status', 'Avatar updated.');

    $user->refresh();

    expect($user->avatar_path)
        ->not->toBeNull()
        ->not->toBe($oldAvatarPath);

    Storage::disk('public')->assertMissing($oldAvatarPath);
    Storage::disk('public')->assertExists($user->avatar_path);
});

test('users can remove their avatar', function () {
    Storage::fake('public');

    Storage::disk('public')->put('avatars/avatar.jpg', 'avatar');

    $user = User::factory()->create([
        'avatar_path' => 'avatars/avatar.jpg',
    ]);

    $avatarPath = $user->avatar_path;

    $response = $this->actingAs($user)->delete(route('profile.avatar.destroy'));

    $response->assertRedirect(route('profile.show', absolute: false));
    $response->assertSessionHas('status', 'Avatar removed.');

    expect($user->refresh()->avatar_path)->toBeNull();

    Storage::disk('public')->assertMissing($avatarPath);
});

test('users can delete their account', function () {
    Storage::fake('public');

    Storage::disk('public')->put('avatars/avatar.jpg', 'avatar');

    $user = User::factory()->create([
        'avatar_path' => 'avatars/avatar.jpg',
    ]);

    DB::table('sessions')->insert([
        'id' => 'account-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest Browser',
        'payload' => '[]',
        'last_activity' => now()->timestamp,
    ]);

    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => 'token',
        'created_at' => now(),
    ]);

    $avatarPath = $user->avatar_path;

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'current_password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertGuest();
    $this->assertModelMissing($user);
    $this->assertDatabaseMissing('sessions', ['id' => 'account-session']);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    Storage::disk('public')->assertMissing($avatarPath);
});

test('users must confirm their current password before deleting account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('profile.destroy'), [
        'current_password' => 'wrong-password',
    ]);

    $response->assertInvalid(['current_password']);
    $this->assertModelExists($user);
});
