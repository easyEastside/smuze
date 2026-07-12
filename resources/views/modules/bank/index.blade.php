<x-layouts.app title="Bank">
    <section class="w-full max-w-5xl">
        @if (session('status'))
            <div class="mb-6 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800 shadow-[inset_0_0_0_1px_rgba(22,101,52,0.16)] dark:bg-green-950 dark:text-green-200 dark:shadow-[inset_0_0_0_1px_rgba(187,247,208,0.18)]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 shadow-[inset_0_0_0_1px_rgba(220,38,38,0.16)] dark:bg-red-950 dark:text-red-200 dark:shadow-[inset_0_0_0_1px_rgba(252,165,165,0.18)]">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Bank</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Invest credits</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                        Reinvest often for the best hourly return. Larger deposits receive stronger amount multipliers.
                    </p>
                </div>
                <div class="rounded-xl bg-[#19140005] px-5 py-4 text-right dark:bg-[#fffaed05]">
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $currentCredits }}</p>
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">available credits</p>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_1.2fr]">
            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Start investment</p>
                <form method="POST" action="{{ route('bank.store') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="amount" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Amount</label>
                        <input id="amount" type="number" name="amount" value="{{ old('amount', 100) }}" min="10" class="mt-2 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]" />
                    </div>

                    <div>
                        <label for="term_hours" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Duration</label>
                        <select id="term_hours" name="term_hours" class="mt-2 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]">
                            @foreach ($termOptions as $option)
                                <option value="{{ $option['hours'] }}" @selected((int) old('term_hours', 1) === $option['hours'])>
                                    {{ $option['hours'] }}h - {{ number_format($option['multiplier'] * 100) }}% hourly efficiency
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                        Invest credits
                    </button>
                </form>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Profit calculator</p>
                <h2 class="mt-2 text-2xl font-semibold">Calculate your payout</h2>
                <p class="mt-3 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                    Base rate: {{ number_format($baseHourlyRate, 2) }}% per hour. One-hour runs have the best hourly return.
                </p>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="calculator_amount" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Amount</label>
                        <input id="calculator_amount" type="number" value="100" min="10" class="mt-2 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]" />
                    </div>

                    <div>
                        <label for="calculator_term_hours" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Duration</label>
                        <select id="calculator_term_hours" class="mt-2 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]">
                            @foreach ($termOptions as $option)
                                <option value="{{ $option['hours'] }}">{{ $option['hours'] }}h</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-[#19140005] p-5 dark:bg-[#fffaed05]">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">Interest</p>
                            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">+<span id="calculator_interest">1</span></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">Payout</p>
                            <p class="mt-1 text-2xl font-bold"><span id="calculator_payout">101</span></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">Multiplier</p>
                            <p class="mt-1 text-2xl font-bold"><span id="calculator_multiplier">1.05</span>x</p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">
                        Final payouts are calculated on the server when you invest or claim.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <h2 class="text-2xl font-semibold">Your investments</h2>

            @if ($investments->isEmpty())
                <p class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">No bank investments yet.</p>
            @else
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#19140020] text-left text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                <th class="pb-3 pr-4 font-medium">Amount</th>
                                <th class="pb-3 pr-4 font-medium">Interest</th>
                                <th class="pb-3 pr-4 font-medium">Progress</th>
                                <th class="pb-3 pr-4 font-medium">Ready at</th>
                                <th class="pb-3 pr-4 font-medium">Status</th>
                                <th class="pb-3 text-right font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($investments as $investment)
                                @php
                                    $progressPercentage = $investment->progressPercentage();
                                @endphp

                                <tr class="border-b border-[#19140012] last:border-b-0 dark:border-[#3E3E3A]">
                                    <td class="py-3 pr-4 font-medium">{{ $investment->principal_amount }}</td>
                                    <td class="py-3 pr-4 text-green-600 dark:text-green-400">+{{ $investment->interest_amount }}</td>
                                    <td class="min-w-40 py-3 pr-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-3 flex-1 overflow-hidden rounded-full bg-[#19140010] dark:bg-[#fffaed12]" role="progressbar" aria-label="Investment progress {{ $progressPercentage }} percent" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressPercentage }}">
                                                <div class="h-full rounded-full bg-[#f53003] dark:bg-[#FF4433]" style="width: {{ $progressPercentage }}%"></div>
                                            </div>
                                            <span class="w-10 text-right text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">{{ $progressPercentage }}%</span>
                                        </div>
                                    </td>
                                    <td class="py-3 pr-4 text-[#706f6c] dark:text-[#A1A09A]">{{ $investment->matures_at->format('M j, H:i') }}</td>
                                    <td class="py-3 pr-4 text-[#706f6c] dark:text-[#A1A09A]">{{ ucfirst($investment->status) }}</td>
                                    <td class="py-3 text-right">
                                        @if ($investment->status === \App\Models\BankInvestment::STATUS_ACTIVE)
                                            <form method="POST" action="{{ route('bank.claim', $investment) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm font-medium hover:border-[#1915014a] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#3E3E3A] dark:hover:border-[#62605b]" @disabled(! $investment->isMatured())>
                                                    Claim {{ $investment->payoutAmount() }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[#706f6c] dark:text-[#A1A09A]">Claimed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $investments->links() }}
                </div>
            @endif
        </div>
    </section>

    <script>
        (() => {
            const baseHourlyRate = @json($baseHourlyRate);
            const termMultipliers = @json($termOptions->mapWithKeys(fn (array $option): array => [$option['hours'] => $option['multiplier']]));
            const amountTiers = @json($amountTiers);
            const amountInput = document.getElementById('calculator_amount');
            const termInput = document.getElementById('calculator_term_hours');
            const interestOutput = document.getElementById('calculator_interest');
            const payoutOutput = document.getElementById('calculator_payout');
            const multiplierOutput = document.getElementById('calculator_multiplier');

            const amountMultiplier = (amount) => amountTiers.reduce((multiplier, tier) => amount >= tier.minimum ? tier.multiplier : multiplier, 1);

            const updateCalculator = () => {
                const amount = Math.max(parseInt(amountInput.value, 10) || 0, 0);
                const hours = parseInt(termInput.value, 10) || 1;
                const termMultiplier = termMultipliers[hours] || 1;
                const multiplier = amountMultiplier(amount);
                const interest = Math.floor(amount * (baseHourlyRate / 100) * hours * termMultiplier * multiplier);

                interestOutput.textContent = interest.toLocaleString();
                payoutOutput.textContent = (amount + interest).toLocaleString();
                multiplierOutput.textContent = multiplier.toFixed(2);
            };

            amountInput.addEventListener('input', updateCalculator);
            termInput.addEventListener('change', updateCalculator);
            updateCalculator();
        })();
    </script>
</x-layouts.app>
