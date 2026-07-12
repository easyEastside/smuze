<?php

namespace App\Modules\Profile\Actions;

use Illuminate\Http\Request;

class ReadCreditHistory
{
    /** @return array<string, mixed> */
    public function handle(Request $request): array
    {
        $user = $request->user();

        $transactions = $user->creditTransactions()
            ->orderByDesc('created_at')
            ->paginate(15);

        return [
            'transactions' => $transactions,
            'currentCredits' => $user->credits,
        ];
    }
}
