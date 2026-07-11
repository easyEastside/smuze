<?php

namespace App\Modules\Bank\Actions;

use App\Models\BankInvestment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimBankInvestment
{
    /**
     * @throws ValidationException
     */
    public function handle(BankInvestment $investment): BankInvestment
    {
        return DB::transaction(function () use ($investment): BankInvestment {
            $lockedInvestment = BankInvestment::query()
                ->with('user')
                ->lockForUpdate()
                ->findOrFail($investment->id);

            if ($lockedInvestment->claimed_at !== null) {
                throw ValidationException::withMessages(['investment' => 'This investment has already been claimed.']);
            }

            if (! $lockedInvestment->isMatured()) {
                throw ValidationException::withMessages(['investment' => 'This investment is not ready yet.']);
            }

            $lockedInvestment->update([
                'claimed_at' => now(),
                'status' => BankInvestment::STATUS_CLAIMED,
            ]);

            $lockedInvestment->user->addCredits(
                amount: $lockedInvestment->payoutAmount(),
                description: "Bank payout: {$lockedInvestment->principal_amount} principal + {$lockedInvestment->interest_amount} interest",
                type: 'bank_payout',
                reference: $lockedInvestment,
            );

            return $lockedInvestment;
        });
    }
}
