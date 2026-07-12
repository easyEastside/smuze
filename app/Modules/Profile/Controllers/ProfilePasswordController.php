<?php

namespace App\Modules\Profile\Controllers;

use App\Modules\Profile\Actions\UpdatePassword;
use App\Modules\Profile\Requests\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;

class ProfilePasswordController
{
    public function update(UpdatePasswordRequest $request, UpdatePassword $updatePassword): RedirectResponse
    {
        $updatePassword->handle($request);

        return redirect()->route('profile.show')->with('status', 'Password updated.');
    }
}
