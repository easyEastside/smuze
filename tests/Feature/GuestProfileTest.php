<?php

use App\Models\Purchase;
use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guests are redirected from guest profiles to login', function () {
    $profileUser = User::factory()->create();

    $this->get(route('guest-profile.show', $profileUser))->assertRedirectToRoute('login');
});

test('authenticated users can view another user profile', function () {
    $viewer = User::factory()->create();
    $profileUser = User::factory()->create([
        'name' => 'Visited User',
        'email' => 'private@example.com',
        'credits' => 1250,
    ]);
    $profileUser->assignRole(Role::create(['name' => 'moderator']));

    $shopItem = ShopItem::factory()->create([
        'name' => 'Golden Ticket',
        'short_description' => 'Rare collectible',
    ]);

    Purchase::create([
        'user_id' => $profileUser->id,
        'shop_item_id' => $shopItem->id,
        'quantity' => 2,
        'total_price' => 100,
        'source' => 'purchase',
    ]);

    $this->actingAs($viewer)
        ->get(route('guest-profile.show', $profileUser))
        ->assertSuccessful()
        ->assertSee('Guest profile')
        ->assertSee('Visited User')
        ->assertSee('Moderator')
        ->assertSee('1,250')
        ->assertSee('Golden Ticket')
        ->assertSee('x2')
        ->assertDontSee('private@example.com');
});

test('users visiting their own guest profile are redirected to their profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('guest-profile.show', $user))
        ->assertRedirect(route('profile.show', absolute: false));
});

test('leaderboard links to guest profiles', function () {
    $viewer = User::factory()->create();
    $profileUser = User::factory()->create(['name' => 'Leaderboard User', 'credits' => 25]);

    $this->actingAs($viewer)
        ->get(route('leaderboard'))
        ->assertSuccessful()
        ->assertSee(route('guest-profile.show', $profileUser, absolute: false))
        ->assertSee('Leaderboard User');
});
