<?php

namespace App\Modules\Profile\Controllers;

use App\Modules\Profile\Actions\ReadProfile;
use App\Modules\Profile\Actions\UpdateProfile;
use App\Modules\Profile\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController
{
    public function show(Request $request, ReadProfile $readProfile): View
    {
        return view('modules.profile.index', $readProfile->handle($request));
    }

    public function update(UpdateProfileRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle($request);

        return redirect()->route('profile.show')->with('status', 'Profile updated.');
    }
}
