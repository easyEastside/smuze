<?php

use App\Models\BankInvestment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create(['credits' => 1000]);
});

test('authenticated user can view bank', function () {
    $response = $this->actingAs($this->user)->get(route('bank.index'));

    $response->assertSuccessful();
    $response->assertSee('Invest credits');
    $response->assertSee('Profit calculator');
    $response->assertSee('Calculate your payout');
    $response->assertSee('available credits');
});

test('bank investments show maturity progress', function () {
    $this->travelTo(now()->setMicrosecond(0));

    BankInvestment::query()->create([
        'user_id' => $this->user->id,
        'principal_amount' => 100,
        'interest_amount' => 1,
        'base_hourly_rate' => 1,
        'term_hours' => 2,
        'term_multiplier' => 1,
        'amount_multiplier' => 1.05,
        'starts_at' => now()->subHour(),
        'matures_at' => now()->addHour(),
    ]);

    $response = $this->actingAs($this->user)->get(route('bank.index'));

    $response->assertSuccessful();
    $response->assertSee('Progress');
    $response->assertSee('50%');
    $response->assertSee('role="progressbar"', false);
    $response->assertSee('aria-valuenow="50"', false);
});

test('guest cannot view bank', function () {
    $response = $this->get(route('bank.index'));

    $response->assertRedirect(route('login', absolute: false));
});

test('user can invest credits', function () {
    Setting::setBankBaseHourlyInterestRate(1.0);

    $response = $this->actingAs($this->user)->post(route('bank.store'), [
        'amount' => 1000,
        'term_hours' => 1,
    ]);

    $response->assertRedirect(route('bank.index', absolute: false));

    expect($this->user->refresh()->credits)->toBe(0);

    $investment = BankInvestment::query()->first();

    expect($investment)
        ->principal_amount->toBe(1000)
        ->interest_amount->toBe(13)
        ->term_hours->toBe(1)
        ->status->toBe(BankInvestment::STATUS_ACTIVE);

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $this->user->id,
        'amount' => -1000,
        'type' => 'bank_investment',
    ]);
});

test('user cannot invest more credits than they have', function () {
    $response = $this->actingAs($this->user)->post(route('bank.store'), [
        'amount' => 1001,
        'term_hours' => 1,
    ]);

    $response->assertInvalid(['amount']);

    expect($this->user->refresh()->credits)->toBe(1000);
    expect(BankInvestment::query()->count())->toBe(0);
});

test('user cannot claim investment before maturity', function () {
    $investment = BankInvestment::query()->create([
        'user_id' => $this->user->id,
        'principal_amount' => 100,
        'interest_amount' => 1,
        'base_hourly_rate' => 1,
        'term_hours' => 1,
        'term_multiplier' => 1,
        'amount_multiplier' => 1.05,
        'starts_at' => now(),
        'matures_at' => now()->addHour(),
    ]);

    $response = $this->actingAs($this->user)->post(route('bank.claim', $investment));

    $response->assertInvalid(['investment']);

    expect($this->user->refresh()->credits)->toBe(1000);
    expect($investment->refresh()->claimed_at)->toBeNull();
});

test('user can claim matured investment once', function () {
    $this->user->update(['credits' => 900]);

    $investment = BankInvestment::query()->create([
        'user_id' => $this->user->id,
        'principal_amount' => 100,
        'interest_amount' => 1,
        'base_hourly_rate' => 1,
        'term_hours' => 1,
        'term_multiplier' => 1,
        'amount_multiplier' => 1.05,
        'starts_at' => now()->subHours(2),
        'matures_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($this->user)->post(route('bank.claim', $investment));

    $response->assertRedirect(route('bank.index', absolute: false));

    expect($this->user->refresh()->credits)->toBe(1001);
    expect($investment->refresh()->status)->toBe(BankInvestment::STATUS_CLAIMED);

    $secondResponse = $this->actingAs($this->user)->post(route('bank.claim', $investment));

    $secondResponse->assertInvalid(['investment']);
    expect($this->user->refresh()->credits)->toBe(1001);
});

test('user cannot claim another users investment', function () {
    $otherUser = User::factory()->create();
    $investment = BankInvestment::query()->create([
        'user_id' => $otherUser->id,
        'principal_amount' => 100,
        'interest_amount' => 1,
        'base_hourly_rate' => 1,
        'term_hours' => 1,
        'term_multiplier' => 1,
        'amount_multiplier' => 1.05,
        'starts_at' => now()->subHours(2),
        'matures_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($this->user)->post(route('bank.claim', $investment));

    $response->assertForbidden();
});

test('admin can update bank base hourly interest rate', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.settings.bank.update'), [
        'bank_base_hourly_interest_rate' => 2.5,
    ]);

    $response->assertRedirect(route('admin.settings', absolute: false));

    expect(Setting::bankBaseHourlyInterestRate())->toBe(2.5);
});

test('bank base hourly interest rate is limited', function () {
    $role = Role::create(['name' => 'admin']);
    $permission = Permission::create(['name' => 'access-admin']);
    $role->givePermissionTo($permission);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.settings.bank.update'), [
        'bank_base_hourly_interest_rate' => 11,
    ]);

    $response->assertInvalid(['bank_base_hourly_interest_rate']);
});
