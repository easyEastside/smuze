<?php

namespace App\Modules\Logout\Controllers;

use App\Modules\Logout\Actions\LogoutUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LogoutController
{
    public function destroy(Request $request, LogoutUser $logoutUser): RedirectResponse
    {
        $logoutUser->handle($request);

        return redirect('/');
    }
}
