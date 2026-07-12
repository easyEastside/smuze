<?php

namespace App\Modules\Login\Controllers;

use App\Modules\Login\Actions\AttemptLogin;
use App\Modules\Login\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController
{
    public function create(): View
    {
        return view('modules.login.index');
    }

    public function store(LoginRequest $request, AttemptLogin $attemptLogin): RedirectResponse
    {
        $attemptLogin->handle($request);

        return redirect()->intended(route('server.index', absolute: false));
    }
}
