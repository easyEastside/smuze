<?php

namespace App\Modules\Admin\Users\Actions;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdjustUserCredits
{
    public function handle(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = (int) $validated['amount'];

        if ($amount >= 0) {
            $user->addCredits($amount, $validated['description'] ?? null, 'admin_adjustment');
        } else {
            $user->deductCredits(abs($amount), $validated['description'] ?? null, 'admin_adjustment');
        }

        return to_route('admin.users.show', $user)
            ->with('flash', ['success' => "Credits adjusted by {$amount} for {$user->name}."]);
    }
}
