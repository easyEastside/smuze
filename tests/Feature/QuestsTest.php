<?php

use App\Models\BankInvestment;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\Purchase;
use App\Models\QuestClaim;
use App\Models\ShopItem;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function completeAllDailyQuests(User $user): void
{
    SurveyResponse::factory()->for(Survey::factory())->for($user)->create();

    Purchase::query()->create([
        'user_id' => $user->id,
        'shop_item_id' => ShopItem::factory()->create()->id,
        'quantity' => 1,
        'total_price' => 10,
    ]);

    BankInvestment::query()->create([
        'user_id' => $user->id,
        'principal_amount' => 100,
        'interest_amount' => 1,
        'base_hourly_rate' => 1,
        'term_hours' => 1,
        'term_multiplier' => 1,
        'amount_multiplier' => 1,
        'starts_at' => now(),
        'matures_at' => now()->addHour(),
    ]);

    $otherUser = User::factory()->create();
    $thread = MessageThread::query()->create([
        'participant_one_id' => $user->id,
        'participant_two_id' => $otherUser->id,
        'subject' => 'Daily check-in',
    ]);

    Message::query()->create([
        'message_thread_id' => $thread->id,
        'sender_id' => $user->id,
        'body' => 'Hello there.',
    ]);
}

test('guest cannot view quests', function () {
    $this->get(route('quests.index'))->assertRedirect(route('login', absolute: false));
});

test('user can view daily quests', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('quests.index'))
        ->assertSuccessful()
        ->assertSee('Daily quests')
        ->assertSee('Answer a survey')
        ->assertSee('Daily completion bonus');
});

test('user cannot claim an incomplete quest', function () {
    $user = User::factory()->create(['credits' => 0]);

    $this->actingAs($user)
        ->post(route('quests.claim', 'survey_response_today'))
        ->assertInvalid(['quest']);

    expect($user->refresh()->credits)->toBe(0);
    expect(QuestClaim::query()->count())->toBe(0);
});

test('user can claim a completed quest reward once per day', function () {
    $user = User::factory()->create(['credits' => 0]);
    SurveyResponse::factory()->for(Survey::factory())->for($user)->create();

    $this->actingAs($user)
        ->post(route('quests.claim', 'survey_response_today'))
        ->assertRedirect(route('quests.index', absolute: false));

    expect($user->refresh()->credits)->toBe(20);

    $this->assertDatabaseHas('quest_claims', [
        'user_id' => $user->id,
        'quest_key' => 'survey_response_today',
        'reward_credits' => 20,
    ]);

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'amount' => 20,
        'type' => 'quest_reward',
    ]);

    $this->actingAs($user)
        ->post(route('quests.claim', 'survey_response_today'))
        ->assertInvalid(['quest']);

    expect($user->refresh()->credits)->toBe(20);
});

test('daily completion bonus is awarded after all daily quest rewards are claimed', function () {
    $user = User::factory()->create(['credits' => 0]);
    completeAllDailyQuests($user);

    foreach (['survey_response_today', 'shop_purchase_today', 'bank_investment_today'] as $questKey) {
        $this->actingAs($user)
            ->post(route('quests.claim', $questKey))
            ->assertRedirect(route('quests.index', absolute: false));
    }

    expect($user->refresh()->credits)->toBe(45);
    expect(QuestClaim::query()->where('quest_key', QuestClaim::DAILY_COMPLETION_BONUS)->exists())->toBeFalse();

    $this->actingAs($user)
        ->post(route('quests.claim', 'message_sent_today'))
        ->assertRedirect(route('quests.index', absolute: false));

    expect($user->refresh()->credits)->toBe(105);

    $this->assertDatabaseHas('quest_claims', [
        'user_id' => $user->id,
        'quest_key' => QuestClaim::DAILY_COMPLETION_BONUS,
        'reward_credits' => 50,
    ]);

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'amount' => 50,
        'type' => 'quest_completion_bonus',
    ]);
});

test('completion bonus is not awarded twice', function () {
    $user = User::factory()->create(['credits' => 0]);
    completeAllDailyQuests($user);

    foreach (['survey_response_today', 'shop_purchase_today', 'bank_investment_today', 'message_sent_today'] as $questKey) {
        $this->actingAs($user)->post(route('quests.claim', $questKey));
    }

    expect($user->refresh()->credits)->toBe(105);

    $this->actingAs($user)
        ->post(route('quests.claim', 'message_sent_today'))
        ->assertInvalid(['quest']);

    expect($user->refresh()->credits)->toBe(105);
    expect(QuestClaim::query()->where('quest_key', QuestClaim::DAILY_COMPLETION_BONUS)->count())->toBe(1);
});
