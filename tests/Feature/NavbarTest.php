<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('navbar keeps primary links visible and moves secondary links into menus', function () {
    $user = User::factory()->create(['credits' => 25]);

    $view = $this->actingAs($user)->blade('<x-navbar />');

    $view
        ->assertSeeText('Dashboard')
        ->assertSeeText('Shop')
        ->assertSeeText('Bank')
        ->assertSeeText('Inventory')
        ->assertSeeText('More')
        ->assertSeeText('Menu')
        ->assertSeeText('Leaderboard')
        ->assertSeeText('Messages')
        ->assertSeeText('Surveys')
        ->assertSeeText('Quests')
        ->assertSeeText('Profile')
        ->assertSeeText('25 Credits')
        ->assertSeeText('Log out')
        ->assertSee('group-hover:block', false)
        ->assertSee('group-focus-within:block', false)
        ->assertDontSee('id="navbar-desktop-menu" class="absolute right-0 top-full z-40 mt-2', false)
        ->assertSee('data-navbar-menu="desktop"', false)
        ->assertSee('data-navbar-menu="mobile"', false)
        ->assertDontSeeText('Admin');
});

test('navbar shows admin link for users with admin access', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create();
    $admin->assignRole($role);

    $view = $this->actingAs($admin)->blade('<x-navbar />');

    $view->assertSeeText('Admin');
});
