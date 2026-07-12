<?php

namespace App\Modules\Bank\Actions;

use App\Models\BankInvestment;
use App\Models\Setting;
use App\Models\User;
use App\Modules\Achievements\Actions\UnlockAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBankInvestment
{
    /** @var array<int, float> */
    public const TERM_MULTIPLIERS = [
        1 => 1.00,
        3 => 0.92,
        6 => 0.82,
        12 => 0.68,
        24 => 0.50,
    ];

    /**
     * @throws ValidationException
     */
    public function handle(User $user, int $amount, int $termHours): BankInvestment
    {
        if (! array_key_exists($termHours, self::TERM_MULTIPLIERS)) {
            throw ValidationException::withMessages(['term_hours' => 'Please choose a valid investment duration.']);
        }

        return DB::transaction(function () use ($amount, $termHours, $user): BankInvestment {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if (! $lockedUser->hasCredits($amount)) {
                throw ValidationException::withMessages(['amount' => 'You do not have enough credits to invest this amount.']);
            }

            $baseHourlyRate = Setting::bankBaseHourlyInterestRate();
            $termMultiplier = self::TERM_MULTIPLIERS[$termHours];
            $amountMultiplier = $this->amountMultiplier($amount);
            $interestAmount = $this->calculateInterest($amount, $termHours, $baseHourlyRate, $termMultiplier, $amountMultiplier);
            $startsAt = now();

            $investment = BankInvestment::query()->create([
                'user_id' => $lockedUser->id,
                'principal_amount' => $amount,
                'interest_amount' => $interestAmount,
                'base_hourly_rate' => $baseHourlyRate,
                'term_hours' => $termHours,
                'term_multiplier' => $termMultiplier,
                'amount_multiplier' => $amountMultiplier,
                'starts_at' => $startsAt,
                'matures_at' => $startsAt->copy()->addHours($termHours),
            ]);

            $lockedUser->deductCredits(
                amount: $amount,
                description: "Bank investment for {$termHours}h",
                type: 'bank_investment',
                reference: $investment,
            );

            app(UnlockAchievement::class)->handle($lockedUser, 'first_investment');

            return $investment;
        });
    }

    /** @return array<int, array{minimum: int, multiplier: float}> */
    public static function amountTiers(): array
    {
        return [
            ['minimum' => 1, 'multiplier' => 1.00],
            ['minimum' => 100, 'multiplier' => 1.05],
            ['minimum' => 500, 'multiplier' => 1.15],
            ['minimum' => 1000, 'multiplier' => 1.30],
            ['minimum' => 5000, 'multiplier' => 1.50],
        ];
    }

    public function amountMultiplier(int $amount): float
    {
        $multiplier = 1.00;

        foreach (self::amountTiers() as $tier) {
            if ($amount >= $tier['minimum']) {
                $multiplier = $tier['multiplier'];
            }
        }

        return $multiplier;
    }

    public function calculateInterest(int $amount, int $termHours, float $baseHourlyRate, float $termMultiplier, float $amountMultiplier): int
    {
        return (int) floor($amount * ($baseHourlyRate / 100) * $termHours * $termMultiplier * $amountMultiplier);
    }
}
