<?php

namespace App\Modules\Profile\Controllers;

use App\Modules\Profile\Actions\DeleteAvatar;
use App\Modules\Profile\Actions\UpdateAvatar;
use App\Modules\Profile\Requests\UpdateAvatarRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileAvatarController
{
    public function update(UpdateAvatarRequest $request, UpdateAvatar $updateAvatar): RedirectResponse
    {
        $updateAvatar->handle($request);

        return redirect()->route('profile.show')->with('status', 'Avatar updated.');
    }

    public function destroy(Request $request, DeleteAvatar $deleteAvatar): RedirectResponse
    {
        $deleteAvatar->handle($request);

        return redirect()->route('profile.show')->with('status', 'Avatar removed.');
    }
}
