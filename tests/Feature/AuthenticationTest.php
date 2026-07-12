<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertSuccessful();
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('server.index', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertSuccessful();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('server.index', absolute: false));
});

test('users receive daily login bonus once per day', function () {
    $user = User::factory()->create(['credits' => 0]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('server.index', absolute: false));

    expect($user->refresh()->credits)->toBe(10);

    expect($user->dailyLoginBonuses()
        ->where('streak_day', 1)
        ->where('reward_credits', 10)
        ->whereDate('claimed_for_date', today()->toDateString())
        ->exists())->toBeTrue();

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'amount' => 10,
        'type' => 'daily_login_bonus',
        'description' => 'Daily login bonus - day 1',
    ]);

    $this->post('/logout');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('server.index', absolute: false));

    expect($user->refresh()->credits)->toBe(10);
    expect($user->dailyLoginBonuses()->count())->toBe(1);
});

test('users receive a larger daily login bonus on the next streak day', function () {
    $user = User::factory()->create(['credits' => 0]);

    $this->travelTo(now()->startOfDay());

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('server.index', absolute: false));

    $this->post('/logout');

    $this->travelTo(now()->addDay());

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('server.index', absolute: false));

    expect($user->refresh()->credits)->toBe(25);
    expect($user->dailyLoginBonuses()->count())->toBe(2);

    expect($user->dailyLoginBonuses()
        ->where('streak_day', 2)
        ->where('reward_credits', 15)
        ->exists())->toBeTrue();
});

test('daily login bonus reaches full reward at seven day streak and stays capped', function () {
    $user = User::factory()->create(['credits' => 0]);

    $this->travelTo(now()->startOfDay());

    foreach (range(1, 8) as $day) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('server.index', absolute: false));

        $this->post('/logout');
        $this->travelTo(now()->addDay());
    }

    expect($user->refresh()->credits)->toBe(240);

    expect($user->dailyLoginBonuses()
        ->where('streak_day', 7)
        ->where('reward_credits', 50)
        ->count())->toBe(2);
});

test('daily login bonus streak resets after a missed day', function () {
    $user = User::factory()->create(['credits' => 0]);

    $this->travelTo(now()->startOfDay());

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('server.index', absolute: false));

    $this->post('/logout');

    $this->travelTo(now()->addDays(2));

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('server.index', absolute: false));

    expect($user->refresh()->credits)->toBe(20);

    expect($user->dailyLoginBonuses()
        ->where('streak_day', 1)
        ->where('reward_credits', 10)
        ->count())->toBe(2);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertInvalid(['email']);
});

test('authenticated users can access the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('Dashboard');
    $response->assertSee($user->name);
});

test('dashboard shows real account and system data', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'session-one',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest Browser',
        'payload' => '[]',
        'last_activity' => now()->timestamp,
    ]);

    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => '{}',
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);

    DB::table('failed_jobs')->insert([
        'uuid' => 'failed-job-uuid',
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'RuntimeException',
        'failed_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('Active sessions');
    $response->assertSee('Queued jobs');
    $response->assertSee('Failed jobs');
    $response->assertSee('127.0.0.1');
    $response->assertSee('Pest Browser');
});

test('users can sign out other database sessions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    DB::table('sessions')->insert([
        [
            'id' => 'other-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Other Browser',
            'payload' => '[]',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'different-user-session',
            'user_id' => $otherUser->id,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Different Browser',
            'payload' => '[]',
            'last_activity' => now()->timestamp,
        ],
    ]);

    $response = $this->actingAs($user)->delete(route('profile.sessions.destroy-other'));

    $response->assertRedirect(route('profile.show', absolute: false));
    $response->assertSessionHas('status', '1 other session(s) signed out.');

    $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
    $this->assertDatabaseHas('sessions', ['id' => 'different-user-session']);
});

test('guests are redirected from the dashboard to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login', absolute: false));
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
