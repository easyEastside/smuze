<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create(['credits' => 0]);
});

test('user starts with zero credits', function () {
    expect($this->user->credits)->toBe(0);
});

test('user can add credits via trait', function () {
    $transaction = $this->user->addCredits(50, 'Test addition', 'test');

    expect($this->user->refresh()->credits)->toBe(50);

    expect($transaction)
        ->amount->toBe(50)
        ->description->toBe('Test addition')
        ->type->toBe('test')
        ->user_id->toBe($this->user->id);
});

test('user can deduct credits via trait', function () {
    $this->user->addCredits(100, 'Setup', 'test');

    $transaction = $this->user->deductCredits(30, 'Test deduction', 'test');

    expect($this->user->refresh()->credits)->toBe(70);

    expect($transaction)
        ->amount->toBe(-30)
        ->description->toBe('Test deduction')
        ->type->toBe('test');
});

test('credits can go negative', function () {
    $transaction = $this->user->deductCredits(50, 'Going negative', 'test');

    expect($this->user->refresh()->credits)->toBe(-50);

    expect($transaction)
        ->amount->toBe(-50);
});

test('hasCredits returns correct values', function () {
    $this->user->addCredits(100, 'Setup', 'test');

    expect($this->user->hasCredits(100))->toBeTrue();
    expect($this->user->hasCredits(50))->toBeTrue();
    expect($this->user->hasCredits(101))->toBeFalse();
    expect($this->user->hasCredits(-50))->toBeTrue(); // is always true if balance >= -50
});

test('credit transactions are recorded with correct relationships', function () {
    $this->user->addCredits(100, 'Bonus', 'registration_bonus');

    expect($this->user->creditTransactions()->count())->toBe(1);

    $transaction = $this->user->creditTransactions()->first();

    expect($transaction->user->id)->toBe($this->user->id);
});

test('registration gives welcome bonus', function () {
    Role::create(['name' => 'user']);

    $response = $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('server.index', absolute: false));

    $user = User::where('email', 'new@example.com')->first();

    expect($user->credits)->toBe(10);

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'amount' => 10,
        'type' => 'registration_bonus',
        'description' => 'Welcome bonus for registering',
    ]);
});

test('authenticated user can view their credit history', function () {
    $this->user->addCredits(50, 'First deposit', 'test');
    $this->user->deductCredits(10, 'Purchase', 'test');

    $response = $this->actingAs($this->user)->get(route('profile.credits'));

    $response->assertSuccessful();
    $response->assertSee('50');
    $response->assertSee('First deposit');
    $response->assertSee('Purchase');
});

test('guest cannot view credit history', function () {
    $response = $this->get(route('profile.credits'));

    $response->assertRedirect(route('login', absolute: false));
});

test('admin can adjust user credits', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.users.credits.adjust', $this->user), [
        'amount' => 75,
        'description' => 'Admin bonus',
    ]);

    $response->assertRedirect(route('admin.users.show', $this->user, absolute: false));

    expect($this->user->refresh()->credits)->toBe(75);

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $this->user->id,
        'amount' => 75,
        'type' => 'admin_adjustment',
        'description' => 'Admin bonus',
    ]);
});

test('admin can deduct user credits', function () {
    $this->user->addCredits(100, 'Setup', 'test');

    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.users.credits.adjust', $this->user), [
        'amount' => -30,
        'description' => 'Penalty',
    ]);

    $response->assertRedirect(route('admin.users.show', $this->user, absolute: false));

    expect($this->user->refresh()->credits)->toBe(70);

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $this->user->id,
        'amount' => -30,
        'type' => 'admin_adjustment',
        'description' => 'Penalty',
    ]);
});

test('user without admin permission cannot adjust credits', function () {
    $response = $this->actingAs($this->user)->post(route('admin.users.credits.adjust', $this->user), [
        'amount' => 50,
    ]);

    $response->assertForbidden();
});

test('credit adjustment requires valid amount', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.users.credits.adjust', $this->user), [
        'amount' => 'not-a-number',
    ]);

    $response->assertInvalid(['amount']);
});

test('profile page shows credit balance', function () {
    $this->user->addCredits(42, 'Setup', 'test');

    $response = $this->actingAs($this->user)->get(route('profile.show'));

    $response->assertSuccessful();
    $response->assertSee('42');
    $response->assertSee('View history');
    $response->assertSee(route('profile.credits', absolute: false));
});
