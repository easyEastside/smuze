<?php

use App\Models\Purchase;
use App\Models\ShopItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create(['credits' => 100]);
    $this->item = ShopItem::factory()->create(['price' => 50]);
});

test('inventory page shows empty state', function () {
    $this->actingAs($this->user)
        ->get(route('inventory.index'))
        ->assertSuccessful()
        ->assertSeeText("don't own any items");
});

test('inventory groups same item and source', function () {
    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 2, 'total_price' => 100, 'source' => 'purchase']);
    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 3, 'total_price' => 150, 'source' => 'purchase']);

    $this->actingAs($this->user)
        ->get(route('inventory.index'))
        ->assertSuccessful()
        ->assertSee($this->item->name)
        ->assertSee('x5')
        ->assertSee('Purchased');
});

test('inventory separates purchased and gifted', function () {
    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 2, 'total_price' => 100, 'source' => 'purchase']);

    $gifter = User::factory()->create(['name' => 'Santa']);
    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 1, 'total_price' => 0, 'source' => 'gift', 'gifted_by' => $gifter->id]);

    $response = $this->actingAs($this->user)
        ->get(route('inventory.index'))
        ->assertSuccessful();

    $response->assertSee($this->item->name);
    $response->assertSee('x2');
    $response->assertSee('x1');
    $response->assertSee('Purchased');
    $response->assertSee('Gift');
});

test('user can gift an item to another user from group', function () {
    $recipient = User::factory()->create();

    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 2, 'total_price' => 100, 'source' => 'purchase']);

    $this->actingAs($this->user)
        ->post(route('inventory.gift'), [
            'shop_item_id' => $this->item->id,
            'source' => 'purchase',
            'recipient_id' => $recipient->id,
            'quantity' => 1,
        ])
        ->assertRedirect(route('inventory.index'));

    $this->assertDatabaseHas('purchases', [
        'user_id' => $recipient->id,
        'shop_item_id' => $this->item->id,
        'quantity' => 1,
        'source' => 'gift',
        'gifted_by' => $this->user->id,
    ]);
});

test('user can use an item from inventory group', function () {
    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 3, 'total_price' => 150, 'source' => 'purchase']);

    $this->actingAs($this->user)
        ->post(route('inventory.use'), [
            'shop_item_id' => $this->item->id,
            'source' => 'purchase',
            'quantity' => 2,
        ])
        ->assertRedirect(route('inventory.index'));

    $this->assertDatabaseHas('purchases', [
        'user_id' => $this->user->id,
        'shop_item_id' => $this->item->id,
        'quantity' => 1,
        'source' => 'purchase',
    ]);
});

test('using all quantity removes the purchase', function () {
    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 1, 'total_price' => 50, 'source' => 'purchase']);

    $this->actingAs($this->user)
        ->post(route('inventory.use'), [
            'shop_item_id' => $this->item->id,
            'source' => 'purchase',
        ])
        ->assertRedirect(route('inventory.index'));

    $this->assertDatabaseCount('purchases', 0);
});

test('user cannot use more than they own', function () {
    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 1, 'total_price' => 50, 'source' => 'purchase']);

    $this->actingAs($this->user)
        ->post(route('inventory.use'), [
            'shop_item_id' => $this->item->id,
            'source' => 'purchase',
            'quantity' => 5,
        ])
        ->assertSessionHasErrors(['quantity']);
});

test('user cannot gift more than they own from group', function () {
    $recipient = User::factory()->create();

    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 1, 'total_price' => 50, 'source' => 'purchase']);

    $this->actingAs($this->user)
        ->post(route('inventory.gift'), [
            'shop_item_id' => $this->item->id,
            'source' => 'purchase',
            'recipient_id' => $recipient->id,
            'quantity' => 5,
        ])
        ->assertSessionHasErrors(['quantity']);
});

test('user cannot gift to themselves from group', function () {
    Purchase::create(['user_id' => $this->user->id, 'shop_item_id' => $this->item->id, 'quantity' => 1, 'total_price' => 50, 'source' => 'purchase']);

    $this->actingAs($this->user)
        ->post(route('inventory.gift'), [
            'shop_item_id' => $this->item->id,
            'source' => 'purchase',
            'recipient_id' => $this->user->id,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors(['recipient']);
});

test('unauthenticated user cannot access inventory', function () {
    $this->get(route('inventory.index'))->assertRedirectToRoute('login');
});

test('admin can gift an item to a user', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create()->assignRole('admin');
    $recipient = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.inventory.store'), [
            'user_id' => $recipient->id,
            'shop_item_id' => $this->item->id,
            'quantity' => 3,
        ])
        ->assertRedirect(route('admin.inventory.create'));

    $this->assertDatabaseHas('purchases', [
        'user_id' => $recipient->id,
        'shop_item_id' => $this->item->id,
        'quantity' => 3,
        'source' => 'gift',
        'gifted_by' => $admin->id,
    ]);
});
