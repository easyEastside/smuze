<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /** @return array<int, array{key: string, name: string, description: string, icon: string, reward_credits: int, is_hidden: bool}> */
    public static function defaultAchievements(): array
    {
        return [
            [
                'key' => 'first_purchase',
                'name' => 'First Purchase',
                'description' => 'Buy your first item from the shop.',
                'icon' => '🛒',
                'reward_credits' => 10,
                'is_hidden' => false,
            ],
            [
                'key' => 'shopping_spree',
                'name' => 'Shopping Spree',
                'description' => 'Buy 10 items from the shop.',
                'icon' => '🛍️',
                'reward_credits' => 50,
                'is_hidden' => false,
            ],
            [
                'key' => 'first_investment',
                'name' => 'First Investment',
                'description' => 'Make your first bank investment.',
                'icon' => '🏦',
                'reward_credits' => 10,
                'is_hidden' => false,
            ],
            [
                'key' => 'survey_taker',
                'name' => 'Survey Taker',
                'description' => 'Complete your first survey.',
                'icon' => '📋',
                'reward_credits' => 10,
                'is_hidden' => false,
            ],
            [
                'key' => 'quest_completer',
                'name' => 'Quest Completer',
                'description' => 'Claim your first quest reward.',
                'icon' => '⚔️',
                'reward_credits' => 10,
                'is_hidden' => false,
            ],
            [
                'key' => 'messenger',
                'name' => 'Messenger',
                'description' => 'Send your first message.',
                'icon' => '💬',
                'reward_credits' => 10,
                'is_hidden' => false,
            ],
            [
                'key' => 'streak_starter',
                'name' => 'Streak Starter',
                'description' => 'Claim a 3-day login streak.',
                'icon' => '🔥',
                'reward_credits' => 25,
                'is_hidden' => false,
            ],
            [
                'key' => 'millionaire',
                'name' => 'Millionaire',
                'description' => 'Reach 1,000 credits.',
                'icon' => '💎',
                'reward_credits' => 100,
                'is_hidden' => false,
            ],
            [
                'key' => 'streak_master',
                'name' => 'Streak Master',
                'description' => 'Claim a 7-day login streak.',
                'icon' => '🔥',
                'reward_credits' => 100,
                'is_hidden' => true,
            ],
            [
                'key' => 'survey_master',
                'name' => 'Survey Master',
                'description' => 'Complete 10 surveys.',
                'icon' => '📊',
                'reward_credits' => 100,
                'is_hidden' => true,
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::defaultAchievements() as $achievement) {
            Achievement::query()->firstOrCreate(
                ['key' => $achievement['key']],
                $achievement,
            );
        }
    }
}
