<?php

namespace App\Modules\Admin\Achievements\Controllers;

use App\Models\Achievement;
use App\Modules\Admin\Achievements\Actions\DeleteAchievementAction;
use App\Modules\Admin\Achievements\Actions\StoreAchievementAction;
use App\Modules\Admin\Achievements\Actions\UpdateAchievementAction;
use App\Modules\Admin\Achievements\Requests\StoreAchievementRequest;
use App\Modules\Admin\Achievements\Requests\UpdateAchievementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminAchievementsController
{
    public function index(): View
    {
        $achievements = Achievement::query()
            ->withCount('users')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('modules.admin.achievements.index', compact('achievements'));
    }

    public function create(): View
    {
        return view('modules.admin.achievements.create');
    }

    public function store(StoreAchievementRequest $request, StoreAchievementAction $action): RedirectResponse
    {
        $achievement = $action->handle($request->validated());

        return to_route('admin.achievements.index')
            ->with('flash', ['success' => "Achievement {$achievement->name} created successfully."]);
    }

    public function edit(Achievement $achievement): View
    {
        return view('modules.admin.achievements.edit', compact('achievement'));
    }

    public function update(UpdateAchievementRequest $request, Achievement $achievement, UpdateAchievementAction $action): RedirectResponse
    {
        $action->handle($achievement, $request->validated());

        return to_route('admin.achievements.index')
            ->with('flash', ['success' => "Achievement {$achievement->name} updated successfully."]);
    }

    public function destroy(Achievement $achievement, DeleteAchievementAction $action): RedirectResponse
    {
        $name = $achievement->name;

        $action->handle($achievement);

        return to_route('admin.achievements.index')
            ->with('flash', ['success' => "Achievement {$name} deleted successfully."]);
    }
}
