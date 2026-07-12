<x-layouts.app title="Leaderboard">
    <section class="w-full max-w-5xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div>
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Leaderboard</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Top users</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                    Users ranked by total credits earned.
                </p>
            </div>
        </div>

        @if ($users->isEmpty())
            <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">No users found.</p>
            </div>
        @else
            <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                @foreach ($users as $index => $user)
                    @php $rank = $index + 1; @endphp

                    <div class="flex items-center gap-4 border-b border-[#19140012] p-4 last:border-b-0 dark:border-[#3E3E3A] sm:gap-6 sm:px-6 sm:py-5 {{ $rank <= 3 ? 'bg-gradient-to-r from-transparent via-transparent to-transparent' : '' }}"
                        @style([
                            'background: linear-gradient(to right, rgba(255,215,0,0.06), rgba(255,215,0,0.02))' => $rank === 1,
                            'background: linear-gradient(to right, rgba(192,192,192,0.06), rgba(192,192,192,0.02))' => $rank === 2,
                            'background: linear-gradient(to right, rgba(205,127,50,0.06), rgba(205,127,50,0.02))' => $rank === 3,
                        ])
                    >
                        <div class="flex w-10 shrink-0 items-center justify-center sm:w-12">
                            @if ($rank === 1)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8 text-yellow-500 drop-shadow-sm">
                                    <path d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 0 1-.416 1.374H16.5v.75a4.5 4.5 0 0 1-9 0v-.75H3.584a.75.75 0 0 1-.416-1.374l9-6Z" />
                                    <path fill-rule="evenodd" d="M6 11.25a.75.75 0 0 1 .75.75v3.75h10.5V12a.75.75 0 0 1 1.5 0v4.5a.75.75 0 0 1-.75.75h-12a.75.75 0 0 1-.75-.75V12a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                                    <path d="M18.75 18.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V19.5a.75.75 0 0 1 .75-.75h13.5Z" />
                                </svg>
                            @elseif ($rank === 2)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8 text-gray-400 drop-shadow-sm">
                                    <path d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 0 1-.416 1.374H16.5v.75a4.5 4.5 0 0 1-9 0v-.75H3.584a.75.75 0 0 1-.416-1.374l9-6Z" />
                                    <path fill-rule="evenodd" d="M6 11.25a.75.75 0 0 1 .75.75v3.75h10.5V12a.75.75 0 0 1 1.5 0v4.5a.75.75 0 0 1-.75.75h-12a.75.75 0 0 1-.75-.75V12a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                                    <path d="M18.75 18.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V19.5a.75.75 0 0 1 .75-.75h13.5Z" />
                                </svg>
                            @elseif ($rank === 3)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8 text-amber-700 drop-shadow-sm">
                                    <path d="M11.584 2.376a.75.75 0 0 1 .832 0l9 6a.75.75 0 0 1-.416 1.374H16.5v.75a4.5 4.5 0 0 1-9 0v-.75H3.584a.75.75 0 0 1-.416-1.374l9-6Z" />
                                    <path fill-rule="evenodd" d="M6 11.25a.75.75 0 0 1 .75.75v3.75h10.5V12a.75.75 0 0 1 1.5 0v4.5a.75.75 0 0 1-.75.75h-12a.75.75 0 0 1-.75-.75V12a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                                    <path d="M18.75 18.75a.75.75 0 0 1 .75.75v.75a.75.75 0 0 1-.75.75H5.25a.75.75 0 0 1-.75-.75V19.5a.75.75 0 0 1 .75-.75h13.5Z" />
                                </svg>
                            @else
                                <span class="text-sm font-semibold text-[#706f6c] dark:text-[#A1A09A]">{{ $rank }}</span>
                            @endif
                        </div>

                        <a href="{{ route('guest-profile.show', $user) }}" class="flex min-w-0 flex-1 items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#f53003] dark:focus:ring-[#FF4433]">
                            @if ($user->avatar_path)
                                <img src="{{ Storage::url($user->avatar_path) }}" alt="{{ $user->name }}" class="size-8 shrink-0 rounded-full object-cover sm:size-10" />
                            @else
                                <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#19140005] text-xs font-medium text-[#706f6c] dark:bg-[#fffaed05] dark:text-[#A1A09A] sm:size-10 sm:text-sm">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif

                            <span class="truncate text-sm font-medium sm:text-base {{ $rank === 1 ? 'text-yellow-700 dark:text-yellow-400' : ($rank === 2 ? 'text-gray-600 dark:text-gray-300' : ($rank === 3 ? 'text-amber-800 dark:text-amber-400' : '')) }}">
                                {{ $user->name }}
                            </span>
                        </a>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <span class="text-sm font-semibold {{ $rank === 1 ? 'text-yellow-600 dark:text-yellow-400' : ($rank === 2 ? 'text-gray-500 dark:text-gray-300' : ($rank === 3 ? 'text-amber-700 dark:text-amber-400' : 'text-[#1b1b18] dark:text-[#EDEDEC]')) }}">
                                {{ number_format($user->credits) }}
                            </span>
                            <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Credits</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.app>
