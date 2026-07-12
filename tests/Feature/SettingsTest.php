<?php

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
        ->assertSee('Interface settings');
});
