<?php

namespace App\Modules\Register\Actions;

use App\Models\User;
use App\Modules\Register\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;

class RegisterUser
{
    public function handle(RegisterRequest $request): User
    {
        $user = User::create($request->validated());

        $user->assignRole('user');

        $user->addCredits(10, 'Welcome bonus for registering', 'registration_bonus');

        Auth::login($user);

        $request->session()->regenerate();

        return $user;
    }
}
