<?php

namespace App\Modules\Admin\Achievements\Actions;

use App\Models\Achievement;

class DeleteAchievementAction
{
    public function handle(Achievement $achievement): void
    {
        $achievement->delete();
    }
}
