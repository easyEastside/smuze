<?php

namespace App\Modules\Profile\Actions;

use App\Modules\Profile\Requests\UpdateProfileRequest;

class UpdateProfile
{
    public function handle(UpdateProfileRequest $request): void
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($user->email !== $validated['email']) {
            $user->email_verified_at = null;
        }

        $user->forceFill($validated)->save();
    }
}
