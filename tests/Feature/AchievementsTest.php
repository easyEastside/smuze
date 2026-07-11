<?php

use App\Models\Achievement;
use App\Models\Purchase;
use App\Models\ShopItem;
use App\Models\User;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(AchievementSeeder::class);
});

test('guest cannot view achievements', function () {
    $this->get(route('achievements.index'))->assertRedirect(route('login', absolute: false));
});

test('user can view achievements page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('achievements.index'))
        ->assertSuccessful()
        ->assertSee('Your achievements')
        ->assertSee('First Purchase')
        ->assertSee('Survey Taker');
});

test('hidden achievement is not visible before unlocking', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('achievements.index'))
        ->assertSuccessful();

    $response->assertDontSee('Streak Master');
});

test('hidden achievement becomes visible after unlocking', function () {
    $user = User::factory()->create();
    $achievement = Achievement::query()->where('key', 'streak_master')->first();

    $user->achievements()->attach($achievement->id, ['unlocked_at' => now()]);

    $this->actingAs($user)
        ->get(route('achievements.index'))
        ->assertSuccessful()
        ->assertSee('Streak Master');
});

test('first purchase achievement unlocks on first shop purchase', function () {
    $user = User::factory()->create(['credits' => 1000]);
    $item = ShopItem::factory()->create(['price' => 10, 'is_active' => true]);

    $this->actingAs($user)
        ->post(route('shop.buy', $item), ['quantity' => 1])
        ->assertRedirect();

    $this->assertDatabaseHas('achievement_user', [
        'user_id' => $user->id,
        'achievement_id' => Achievement::query()->where('key', 'first_purchase')->first()->id,
    ]);
});

test('shopping spree achievement unlocks after 10 purchases', function () {
    $user = User::factory()->create(['credits' => 10000]);
    $item = ShopItem::factory()->create(['price' => 1, 'is_active' => true]);

    for ($i = 0; $i < 9; $i++) {
        Purchase::query()->create([
            'user_id' => $user->id,
            'shop_item_id' => $item->id,
            'quantity' => 1,
            'total_price' => 1,
        ]);
    }

    $this->actingAs($user)
        ->post(route('shop.buy', $item), ['quantity' => 1])
        ->assertRedirect();

    $this->assertDatabaseHas('achievement_user', [
        'user_id' => $user->id,
        'achievement_id' => Achievement::query()->where('key', 'shopping_spree')->first()->id,
    ]);
});

test('bank investment achievement unlocks on first investment', function () {
    $user = User::factory()->create(['credits' => 1000]);

    $this->actingAs($user)
        ->post(route('bank.store'), [
            'amount' => 100,
            'term_hours' => 1,
        ])
        ->assertRedirect(route('bank.index', absolute: false));

    $this->assertDatabaseHas('achievement_user', [
        'user_id' => $user->id,
        'achievement_id' => Achievement::query()->where('key', 'first_investment')->first()->id,
    ]);
});

test('messenger achievement unlocks on first new thread', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)
        ->post(route('messages.store'), [
            'recipient_id' => $otherUser->id,
            'subject' => 'Hello',
            'body' => 'Hi there!',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('achievement_user', [
        'user_id' => $user->id,
        'achievement_id' => Achievement::query()->where('key', 'messenger')->first()->id,
    ]);
});

test('millionaire achievement unlocks when user reaches 1000 credits', function () {
    $user = User::factory()->create(['credits' => 990]);

    $user->addCredits(amount: 10, description: 'Test');

    $this->assertDatabaseHas('achievement_user', [
        'user_id' => $user->id,
        'achievement_id' => Achievement::query()->where('key', 'millionaire')->first()->id,
    ]);
});

test('admin can create achievement', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->post(route('admin.achievements.store'), [
            'key' => 'test_achievement',
            'name' => 'Test Achievement',
            'description' => 'A test achievement.',
            'icon' => '🎯',
            'reward_credits' => 50,
            'is_hidden' => false,
        ])
        ->assertRedirect(route('admin.achievements.index', absolute: false));

    $this->assertDatabaseHas('achievements', [
        'key' => 'test_achievement',
        'name' => 'Test Achievement',
    ]);
});

test('admin can view achievements list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $this->actingAs($admin)
        ->get(route('admin.achievements.index'))
        ->assertSuccessful()
        ->assertSee('Achievements')
        ->assertSee('First Purchase');
});

test('admin can delete achievement', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');
    $achievement = Achievement::query()->where('key', 'first_purchase')->first();

    $this->actingAs($admin)
        ->delete(route('admin.achievements.destroy', $achievement))
        ->assertRedirect(route('admin.achievements.index', absolute: false));

    $this->assertDatabaseMissing('achievements', ['id' => $achievement->id]);
});
