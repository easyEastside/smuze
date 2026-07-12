<?php

use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guest cannot view settings page', function () {
    $this->get(route('settings.edit'))->assertRedirect(route('login', absolute: false));
});

test('user can view settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.edit'))
        ->assertSuccessful()
        ->assertSee('Interface settings')
        ->assertSee('Schwebendes Terminal anzeigen')
        ->assertSee('Debug-Ausgaben speichern');
});

test('user can update terminal settings', function () {
    $user = User::factory()->create([
        'show_floating_terminal' => true,
        'write_debug_logs' => true,
    ]);

    $this->actingAs($user)
        ->patch(route('settings.update'), [])
        ->assertRedirect(route('settings.edit', absolute: false));

    expect($user->refresh())
        ->show_floating_terminal->toBeFalse()
        ->write_debug_logs->toBeFalse();
});

test('floating command log is hidden when disabled', function () {
    $user = User::factory()->create(['show_floating_terminal' => false]);
    $server = Server::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('server.services.index', $server))
        ->assertSuccessful()
        ->assertDontSee('floating-command-log');
});

test('floating command log exposes debug preference when enabled', function () {
    $user = User::factory()->create([
        'show_floating_terminal' => true,
        'write_debug_logs' => false,
    ]);
    $server = Server::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('server.services.index', $server))
        ->assertSuccessful()
        ->assertSee('floating-command-log')
        ->assertSee('data-debug-enabled="0"', false);
});
