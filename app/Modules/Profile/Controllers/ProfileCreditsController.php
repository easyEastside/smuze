<?php

namespace App\Modules\Profile\Controllers;

use App\Modules\Profile\Actions\ReadCreditHistory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileCreditsController
{
    public function index(Request $request, ReadCreditHistory $readCreditHistory): View
    {
        return view('modules.profile.credits.index', $readCreditHistory->handle($request));
    }
}
