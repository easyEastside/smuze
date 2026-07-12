<?php

namespace App\Modules\ForgotPassword\Actions;

use App\Modules\ForgotPassword\Requests\ForgotPasswordRequest;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLink
{
    public function handle(ForgotPasswordRequest $request): string
    {
        return Password::sendResetLink($request->only('email'));
    }
}
