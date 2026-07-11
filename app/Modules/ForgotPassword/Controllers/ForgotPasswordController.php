<?php

namespace App\Modules\ForgotPassword\Controllers;

use App\Modules\ForgotPassword\Actions\SendPasswordResetLink;
use App\Modules\ForgotPassword\Requests\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController
{
    public function create(): View
    {
        return view('modules.forgot-password.index');
    }

    public function store(ForgotPasswordRequest $request, SendPasswordResetLink $sendPasswordResetLink): RedirectResponse
    {
        $status = $sendPasswordResetLink->handle($request);

        return $status === Password::ResetLinkSent
            ? back()->with('status', trans($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => trans($status)]);
    }
}
