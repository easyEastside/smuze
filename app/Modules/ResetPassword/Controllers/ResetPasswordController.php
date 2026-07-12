<?php

namespace App\Modules\ResetPassword\Controllers;

use App\Modules\ResetPassword\Actions\ResetUserPassword;
use App\Modules\ResetPassword\Requests\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ResetPasswordController
{
    public function create(string $token): View
    {
        return view('modules.reset-password.index', ['token' => $token]);
    }

    public function store(ResetPasswordRequest $request, ResetUserPassword $resetUserPassword): RedirectResponse
    {
        $status = $resetUserPassword->handle($request);

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', trans($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => trans($status)]);
    }
}
