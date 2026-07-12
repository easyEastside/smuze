<?php

namespace App\Modules\Register\Controllers;

use App\Modules\Register\Actions\RegisterUser;
use App\Modules\Register\Requests\RegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController
{
    public function create(): View
    {
        return view('modules.register.index');
    }

    public function store(RegisterRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        $registerUser->handle($request);

        return redirect()->route('server.index');
    }
}
