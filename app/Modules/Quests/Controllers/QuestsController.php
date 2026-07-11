<?php

namespace App\Modules\Quests\Controllers;

use App\Modules\Quests\Actions\ClaimQuestReward;
use App\Modules\Quests\Actions\ReadDailyQuests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestsController
{
    public function index(Request $request, ReadDailyQuests $readDailyQuests): View
    {
        return view('modules.quests.index', $readDailyQuests->handle($request->user()));
    }

    public function claim(Request $request, string $questKey, ClaimQuestReward $claimQuestReward): RedirectResponse
    {
        $claimQuestReward->handle($request->user(), $questKey);

        return to_route('quests.index')->with('status', 'Quest reward claimed.');
    }
}
