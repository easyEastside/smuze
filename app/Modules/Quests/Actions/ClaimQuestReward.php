<?php

namespace App\Modules\Quests\Actions;

use App\Models\QuestClaim;
use App\Models\User;
use App\Modules\Achievements\Actions\UnlockAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimQuestReward
{
    public function __construct(private readonly ReadDailyQuests $readDailyQuests) {}

    /** @throws ValidationException */
    public function handle(User $user, string $questKey): QuestClaim
    {
        $definitions = ReadDailyQuests::definitions();

        if (! array_key_exists($questKey, $definitions)) {
            throw ValidationException::withMessages(['quest' => 'Please choose a valid daily quest.']);
        }

        return DB::transaction(function () use ($definitions, $questKey, $user): QuestClaim {
            $date = today();
            $definition = $definitions[$questKey];

            if ($this->readDailyQuests->progress($questKey, $user, $date) < $definition['target']) {
                throw ValidationException::withMessages(['quest' => 'Complete this quest before claiming the reward.']);
            }

            if ($this->alreadyClaimed($user, $questKey, $date->toDateString())) {
                throw ValidationException::withMessages(['quest' => 'You have already claimed this quest today.']);
            }

            $claim = QuestClaim::query()->create([
                'user_id' => $user->id,
                'quest_key' => $questKey,
                'reward_credits' => $definition['reward'],
                'claimed_for_date' => $date->toDateString(),
                'claimed_at' => now(),
            ]);

            $user->addCredits(
                amount: $definition['reward'],
                description: "Daily quest reward: {$definition['title']}",
                type: 'quest_reward',
                reference: $claim,
            );

            $this->claimCompletionBonusIfUnlocked($user, $date->toDateString());

            app(UnlockAchievement::class)->handle($user, 'quest_completer');

            return $claim;
        });
    }

    private function claimCompletionBonusIfUnlocked(User $user, string $date): void
    {
        $regularQuestKeys = array_keys(ReadDailyQuests::definitions());
        $claimedRegularQuestCount = QuestClaim::query()
            ->where('user_id', $user->id)
            ->whereDate('claimed_for_date', $date)
            ->whereIn('quest_key', $regularQuestKeys)
            ->count();

        if ($claimedRegularQuestCount !== count($regularQuestKeys)) {
            return;
        }

        if ($this->alreadyClaimed($user, QuestClaim::DAILY_COMPLETION_BONUS, $date)) {
            return;
        }

        $claim = QuestClaim::query()->create([
            'user_id' => $user->id,
            'quest_key' => QuestClaim::DAILY_COMPLETION_BONUS,
            'reward_credits' => ReadDailyQuests::COMPLETION_BONUS_REWARD,
            'claimed_for_date' => $date,
            'claimed_at' => now(),
        ]);

        $user->addCredits(
            amount: ReadDailyQuests::COMPLETION_BONUS_REWARD,
            description: 'Daily quest completion bonus',
            type: 'quest_completion_bonus',
            reference: $claim,
        );
    }

    private function alreadyClaimed(User $user, string $questKey, string $date): bool
    {
        return QuestClaim::query()
            ->where('user_id', $user->id)
            ->where('quest_key', $questKey)
            ->whereDate('claimed_for_date', $date)
            ->exists();
    }
}
