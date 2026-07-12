<x-layouts.app title="Achievements">
    <section class="w-full max-w-5xl">
        @if (session('status'))
            <div class="mb-6 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800 shadow-[inset_0_0_0_1px_rgba(22,101,52,0.16)] dark:bg-green-950 dark:text-green-200 dark:shadow-[inset_0_0_0_1px_rgba(187,247,208,0.18)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Achievements</p>
            <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Your achievements</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                        Complete actions across the app to unlock achievements and earn rewards.
                    </p>
                </div>
            </div>
        </div>

        @if ($achievements->isEmpty())
            <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">No achievements available yet.</p>
            </div>
        @else
            <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($achievements as $achievement)
                    @php
                        $isLockedAndHidden = $achievement['is_hidden'] && ! $achievement['is_unlocked'];
                    @endphp

                    @if (! $isLockedAndHidden)
                        <article class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] {{ $achievement['is_unlocked'] ? '' : 'opacity-60' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    @if ($achievement['icon'])
                                        <span class="text-2xl">{{ $achievement['icon'] }}</span>
                                    @else
                                        <span class="flex size-10 items-center justify-center rounded-lg bg-[#f53003]/10 text-lg dark:bg-[#FF4433]/15">
                                            {{ $achievement['is_unlocked'] ? '🏆' : '🔒' }}
                                        </span>
                                    @endif
                                    <div>
                                        <h2 class="text-lg font-semibold">{{ $achievement['name'] }}</h2>
                                        @if ($achievement['description'])
                                            <p class="mt-1 text-sm leading-5 text-[#706f6c] dark:text-[#A1A09A]">{{ $achievement['description'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if ($achievement['is_unlocked'])
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-200">Unlocked</span>
                                    @if ($achievement['reward_credits'] > 0)
                                        <span class="rounded-full bg-[#f53003]/10 px-2.5 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">+{{ $achievement['reward_credits'] }}</span>
                                    @endif
                                </div>
                            @else
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="rounded-full bg-[#19140008] px-3 py-1 text-xs font-medium text-[#706f6c] dark:bg-[#ffffff0f] dark:text-[#A1A09A]">Locked</span>
                                    @if ($achievement['reward_credits'] > 0)
                                        <span class="rounded-full bg-[#f53003]/10 px-2.5 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">+{{ $achievement['reward_credits'] }}</span>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @endif
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.app>
