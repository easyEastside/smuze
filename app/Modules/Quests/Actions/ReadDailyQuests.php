<?php

namespace App\Modules\Quests\Actions;

use App\Models\BankInvestment;
use App\Models\Message;
use App\Models\Purchase;
use App\Models\QuestClaim;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReadDailyQuests
{
    public const COMPLETION_BONUS_REWARD = 50;

    /** @return array<string, array{title: string, description: string, reward: int, progress: int, target: int}> */
    public static function definitions(): array
    {
        return [
            'survey_response_today' => [
                'title' => 'Answer a survey',
                'description' => 'Complete any available survey today.',
                'reward' => 20,
                'progress' => 0,
                'target' => 1,
            ],
            'shop_purchase_today' => [
                'title' => 'Buy an item',
                'description' => 'Purchase one item from the shop today.',
                'reward' => 10,
                'progress' => 0,
                'target' => 1,
            ],
            'bank_investment_today' => [
                'title' => 'Start an investment',
                'description' => 'Invest credits in the bank today.',
                'reward' => 15,
                'progress' => 0,
                'target' => 1,
            ],
            'message_sent_today' => [
                'title' => 'Send a message',
                'description' => 'Send a private message today.',
                'reward' => 10,
                'progress' => 0,
                'target' => 1,
            ],
        ];
    }

    /**
     * @return array{
     *     quests: array<int, array{key: string, title: string, description: string, reward: int, progress: int, target: int, is_complete: bool, is_claimed: bool}>,
     *     bonus: array{key: string, title: string, description: string, reward: int, is_unlocked: bool, is_claimed: bool}
     * }
     */
    public function handle(User $user, ?Carbon $date = null): array
    {
        $date ??= today();
        $claimedKeys = QuestClaim::query()
            ->where('user_id', $user->id)
            ->whereDate('claimed_for_date', $date->toDateString())
            ->pluck('quest_key')
            ->all();

        $quests = collect(self::definitions())
            ->map(function (array $definition, string $key) use ($claimedKeys, $date, $user): array {
                $progress = $this->progress($key, $user, $date);

                return [
                    'key' => $key,
                    'title' => $definition['title'],
                    'description' => $definition['description'],
                    'reward' => $definition['reward'],
                    'progress' => $progress,
                    'target' => $definition['target'],
                    'is_complete' => $progress >= $definition['target'],
                    'is_claimed' => in_array($key, $claimedKeys, true),
                ];
            })
            ->values()
            ->all();

        $regularQuestKeys = array_keys(self::definitions());
        $claimedRegularQuestKeys = array_intersect($regularQuestKeys, $claimedKeys);

        return [
            'quests' => $quests,
            'bonus' => [
                'key' => QuestClaim::DAILY_COMPLETION_BONUS,
                'title' => 'Daily completion bonus',
                'description' => 'Claim every daily quest reward to unlock this bonus.',
                'reward' => self::COMPLETION_BONUS_REWARD,
                'is_unlocked' => count($claimedRegularQuestKeys) === count($regularQuestKeys),
                'is_claimed' => in_array(QuestClaim::DAILY_COMPLETION_BONUS, $claimedKeys, true),
            ],
        ];
    }

    public function progress(string $questKey, User $user, Carbon $date): int
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return match ($questKey) {
            'survey_response_today' => SurveyResponse::query()
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            'shop_purchase_today' => Purchase::query()
                ->where('user_id', $user->id)
                ->purchased()
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            'bank_investment_today' => BankInvestment::query()
                ->where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            'message_sent_today' => Message::query()
                ->where('sender_id', $user->id)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count(),
            default => 0,
        };
    }
}
