<x-layouts.app title="Quests">
    <section class="w-full max-w-5xl">
        @error('quest')
            <div class="mb-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 shadow-[inset_0_0_0_1px_rgba(153,27,27,0.16)] dark:bg-red-950 dark:text-red-200 dark:shadow-[inset_0_0_0_1px_rgba(254,202,202,0.18)]">
                {{ $message }}
            </div>
        @enderror

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Daily quests</p>
            <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Earn extra credits today</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                        Complete daily actions across the app, claim each reward, and unlock the completion bonus.
                    </p>
                </div>
                <div class="rounded-xl border border-[#19140020] px-4 py-3 text-sm dark:border-[#3E3E3A]">
                    <span class="text-[#706f6c] dark:text-[#A1A09A]">Bonus</span>
                    <strong class="ml-2">+{{ $bonus['reward'] }} credits</strong>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($quests as $quest)
                <article class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.18em] text-[#f53003] dark:text-[#FF4433]">+{{ $quest['reward'] }} credits</p>
                            <h2 class="mt-2 text-xl font-semibold">{{ $quest['title'] }}</h2>
                            <p class="mt-2 text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">{{ $quest['description'] }}</p>
                        </div>

                        @if ($quest['is_claimed'])
                            <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-200">Claimed</span>
                        @elseif ($quest['is_complete'])
                            <span class="rounded-full bg-[#f53003]/10 px-3 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">Ready</span>
                        @else
                            <span class="rounded-full bg-[#19140008] px-3 py-1 text-xs font-medium text-[#706f6c] dark:bg-[#ffffff0f] dark:text-[#A1A09A]">Open</span>
                        @endif
                    </div>

                    <div class="mt-5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[#706f6c] dark:text-[#A1A09A]">Progress</span>
                            <span class="font-medium">{{ min($quest['progress'], $quest['target']) }} / {{ $quest['target'] }}</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-[#19140012] dark:bg-[#ffffff14]">
                            <div class="h-full rounded-full bg-[#f53003] dark:bg-[#FF4433]" style="width: {{ $quest['is_complete'] ? 100 : 0 }}%"></div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('quests.claim', $quest['key']) }}" class="mt-5">
                        @csrf
                        <button type="submit" @disabled(! $quest['is_complete'] || $quest['is_claimed']) class="w-full rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:border-[#19140020] disabled:bg-[#19140008] disabled:text-[#706f6c] dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white dark:disabled:border-[#3E3E3A] dark:disabled:bg-[#ffffff0f] dark:disabled:text-[#A1A09A]">
                            {{ $quest['is_claimed'] ? 'Reward claimed' : ($quest['is_complete'] ? 'Claim reward' : 'Complete quest first') }}
                        </button>
                    </form>
                </article>
            @endforeach
        </div>

        <aside class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Completion bonus</p>
                    <h2 class="mt-2 text-2xl font-semibold">{{ $bonus['title'] }}</h2>
                    <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $bonus['description'] }}</p>
                </div>
                <div class="rounded-xl border border-[#19140020] px-5 py-4 text-left sm:text-right dark:border-[#3E3E3A]">
                    <p class="text-2xl font-semibold">+{{ $bonus['reward'] }}</p>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        @if ($bonus['is_claimed'])
                            Bonus claimed
                        @elseif ($bonus['is_unlocked'])
                            Bonus unlocked
                        @else
                            Complete all claims
                        @endif
                    </p>
                </div>
            </div>
        </aside>
    </section>
</x-layouts.app>
