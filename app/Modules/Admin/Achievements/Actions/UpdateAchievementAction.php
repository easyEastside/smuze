<?php

namespace App\Modules\Admin\Achievements\Actions;

use App\Models\Achievement;

class UpdateAchievementAction
{
    /** @param array<string, mixed> $validated */
    public function handle(Achievement $achievement, array $validated): Achievement
    {
        $achievement->update([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'reward_credits' => $validated['reward_credits'],
            'is_hidden' => $validated['is_hidden'] ?? false,
        ]);

        return $achievement;
    }
}
