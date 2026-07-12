<?php

namespace App\Modules\Achievements\Controllers;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\View\View;

class AchievementsController
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $unlockedIds = $user->achievements()->pluck('achievements.id')->all();

        $achievements = Achievement::query()
            ->orderBy('reward_credits')
            ->orderBy('name')
            ->get()
            ->map(fn (Achievement $achievement) => [
                'id' => $achievement->id,
                'key' => $achievement->key,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'reward_credits' => $achievement->reward_credits,
                'is_hidden' => $achievement->is_hidden,
                'is_unlocked' => in_array($achievement->id, $unlockedIds, true),
            ]);

        return view('modules.achievements.index', compact('achievements'));
    }
}
