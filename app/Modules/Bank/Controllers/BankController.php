<?php

namespace App\Modules\Bank\Controllers;

use App\Models\BankInvestment;
use App\Models\Setting;
use App\Modules\Bank\Actions\ClaimBankInvestment;
use App\Modules\Bank\Actions\CreateBankInvestment;
use App\Modules\Bank\Requests\StoreBankInvestmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankController
{
    public function index(Request $request, CreateBankInvestment $createBankInvestment): View
    {
        $user = $request->user();
        $baseHourlyRate = Setting::bankBaseHourlyInterestRate();

        return view('modules.bank.index', [
            'amountTiers' => CreateBankInvestment::amountTiers(),
            'baseHourlyRate' => $baseHourlyRate,
            'currentCredits' => $user->credits,
            'investments' => $user->bankInvestments()->latest()->paginate(10),
            'termOptions' => collect(CreateBankInvestment::TERM_MULTIPLIERS)->map(function (float $termMultiplier, int $hours) use ($baseHourlyRate, $createBankInvestment): array {
                return [
                    'hours' => $hours,
                    'multiplier' => $termMultiplier,
                    'example_interest' => $createBankInvestment->calculateInterest(100, $hours, $baseHourlyRate, $termMultiplier, 1.05),
                ];
            }),
        ]);
    }

    public function store(StoreBankInvestmentRequest $request, CreateBankInvestment $createBankInvestment): RedirectResponse
    {
        $createBankInvestment->handle(
            user: $request->user(),
            amount: $request->integer('amount'),
            termHours: $request->integer('term_hours'),
        );

        return redirect()->route('bank.index')->with('status', 'Investment started successfully.');
    }

    public function claim(Request $request, BankInvestment $bankInvestment, ClaimBankInvestment $claimBankInvestment): RedirectResponse
    {
        abort_unless($bankInvestment->user_id === $request->user()->id, 403);

        $claimBankInvestment->handle($bankInvestment);

        return redirect()->route('bank.index')->with('status', 'Investment claimed successfully.');
    }
}
