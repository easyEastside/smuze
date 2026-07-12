<?php

namespace App\Modules\Admin\Achievements\Actions;

use App\Models\Achievement;

class StoreAchievementAction
{
    /** @param array<string, mixed> $validated */
    public function handle(array $validated): Achievement
    {
        return Achievement::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'reward_credits' => $validated['reward_credits'],
            'is_hidden' => $validated['is_hidden'] ?? false,
        ]);
    }
}
