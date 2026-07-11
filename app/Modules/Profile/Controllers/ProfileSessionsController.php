<?php

namespace App\Modules\Profile\Controllers;

use App\Modules\Profile\Actions\DeleteOtherSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileSessionsController
{
    public function destroyOther(Request $request, DeleteOtherSessions $deleteOtherSessions): RedirectResponse
    {
        $deletedSessions = $deleteOtherSessions->handle($request);

        return redirect()->route('profile.show')->with('status', $deletedSessions.' other session(s) signed out.');
    }
}
