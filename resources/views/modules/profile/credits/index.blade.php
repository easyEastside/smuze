<x-layouts.app title="Credits">
    <section class="w-full max-w-5xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Credits</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Your balance</h1>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold {{ $currentCredits >= 0 ? 'text-green-600 dark:text-green-400' : 'text-[#f53003] dark:text-[#FF4433]' }}">
                        {{ $currentCredits }}
                    </p>
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">credits</p>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="mb-6">
                <h2 class="text-2xl font-semibold">Transaction history</h2>
            </div>

            @if ($transactions->isEmpty())
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">No transactions yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#19140020] text-left text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                <th class="pb-3 pr-4 font-medium">Date</th>
                                <th class="pb-3 pr-4 font-medium">Description</th>
                                <th class="pb-3 pr-4 font-medium">Type</th>
                                <th class="pb-3 text-right font-medium">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $transaction)
                                <tr class="border-b border-[#19140012] last:border-b-0 dark:border-[#3E3E3A]">
                                    <td class="py-3 pr-4 text-[#706f6c] dark:text-[#A1A09A]">{{ $transaction->created_at->format('M j, Y H:i') }}</td>
                                    <td class="py-3 pr-4">{{ $transaction->description ?? '-' }}</td>
                                    <td class="py-3 pr-4 text-[#706f6c] dark:text-[#A1A09A]">{{ $transaction->type }}</td>
                                    <td class="py-3 text-right font-medium {{ $transaction->amount >= 0 ? 'text-green-600 dark:text-green-400' : 'text-[#f53003] dark:text-[#FF4433]' }}">
                                        {{ $transaction->amount >= 0 ? '+' : '' }}{{ $transaction->amount }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $transactions->links() }}
                </div>
            @endif

            <div class="mt-6">
                <a href="{{ route('profile.show') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">&larr; Back to profile</a>
            </div>
        </div>
    </section>
</x-layouts.app>
