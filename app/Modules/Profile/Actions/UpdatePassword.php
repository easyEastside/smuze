<?php

namespace App\Modules\Profile\Actions;

use App\Modules\Profile\Requests\UpdatePasswordRequest;

class UpdatePassword
{
    public function handle(UpdatePasswordRequest $request): void
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        $request->session()->regenerate();
    }
}
