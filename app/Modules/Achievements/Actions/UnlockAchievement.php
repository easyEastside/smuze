<?php

namespace App\Modules\Achievements\Actions;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UnlockAchievement
{
    public function handle(User $user, string $achievementKey): bool
    {
        $achievement = Achievement::query()->where('key', $achievementKey)->first();

        if ($achievement === null) {
            return false;
        }

        if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
            return false;
        }

        DB::transaction(function () use ($user, $achievement): void {
            $user->achievements()->attach($achievement->id, [
                'unlocked_at' => now(),
            ]);

            if ($achievement->reward_credits > 0) {
                $user->addCredits(
                    amount: $achievement->reward_credits,
                    description: "Achievement unlocked: {$achievement->name}",
                    type: 'achievement_reward',
                    reference: $achievement,
                );
            }
        });

        return true;
    }
}
