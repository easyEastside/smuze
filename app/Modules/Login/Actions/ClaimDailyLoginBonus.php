<?php

namespace App\Modules\Login\Actions;

use App\Models\DailyLoginBonus;
use App\Models\User;
use App\Modules\Achievements\Actions\UnlockAchievement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClaimDailyLoginBonus
{
    /** @var array<int, int> */
    private const REWARDS_BY_STREAK_DAY = [
        1 => 10,
        2 => 15,
        3 => 20,
        4 => 25,
        5 => 30,
        6 => 40,
        7 => 50,
    ];

    public function handle(User $user): ?DailyLoginBonus
    {
        return DB::transaction(function () use ($user): ?DailyLoginBonus {
            $date = today()->toDateString();

            if ($this->alreadyClaimed($user, $date)) {
                return null;
            }

            $streakDay = $this->streakDay($user, $date);
            $rewardCredits = self::REWARDS_BY_STREAK_DAY[$streakDay];

            $bonus = DailyLoginBonus::query()->create([
                'user_id' => $user->id,
                'streak_day' => $streakDay,
                'reward_credits' => $rewardCredits,
                'claimed_for_date' => $date,
                'claimed_at' => now(),
            ]);

            $user->addCredits(
                amount: $rewardCredits,
                description: "Daily login bonus - day {$streakDay}",
                type: 'daily_login_bonus',
                reference: $bonus,
            );

            if ($streakDay >= 3) {
                app(UnlockAchievement::class)->handle($user, 'streak_starter');
            }

            if ($streakDay >= 7) {
                app(UnlockAchievement::class)->handle($user, 'streak_master');
            }

            return $bonus;
        });
    }

    private function alreadyClaimed(User $user, string $date): bool
    {
        return DailyLoginBonus::query()
            ->where('user_id', $user->id)
            ->whereDate('claimed_for_date', $date)
            ->exists();
    }

    private function streakDay(User $user, string $date): int
    {
        $previousBonus = DailyLoginBonus::query()
            ->where('user_id', $user->id)
            ->whereDate('claimed_for_date', Carbon::parse($date)->subDay()->toDateString())
            ->first(['streak_day']);

        if ($previousBonus === null) {
            return 1;
        }

        return min($previousBonus->streak_day + 1, 7);
    }
}
