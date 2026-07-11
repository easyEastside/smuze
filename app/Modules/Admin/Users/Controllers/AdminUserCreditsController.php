<?php

namespace App\Modules\Admin\Users\Controllers;

use App\Models\User;
use App\Modules\Admin\Users\Actions\AdjustUserCredits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminUserCreditsController
{
    public function adjust(Request $request, User $user, AdjustUserCredits $adjustUserCredits): RedirectResponse
    {
        return $adjustUserCredits->handle($request, $user);
    }
}
