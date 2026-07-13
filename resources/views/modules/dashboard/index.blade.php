<x-layouts.app title="Dashboard">
    <section class="w-full max-w-5xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div>
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Authenticated dashboard</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Welcome back, {{ $user->name }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                        Monitor account activity and queue health from real application data.
                    </p>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Active sessions</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $systemStats['active_sessions'] }}</p>
                </div>
                <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Queued jobs</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $systemStats['queued_jobs'] }}</p>
                </div>
                <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Failed jobs</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $systemStats['failed_jobs'] }}</p>
                </div>
                <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Job batches</p>
                    <p class="mt-2 text-3xl font-semibold">{{ $systemStats['job_batches'] }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Profile</p>
                <h2 class="mt-2 text-2xl font-semibold">Account settings moved</h2>
                <p class="mt-2 max-w-xl text-sm text-[#706f6c] dark:text-[#A1A09A]">Profile details, avatar, password, sessions, and account deletion now live in the dedicated profile module.</p>
                <a href="{{ route('profile.show') }}" class="mt-5 inline-flex w-fit rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                    Open profile
                </a>
            </div>

            <aside class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Account status</p>
                <dl class="mt-5 flex flex-col gap-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-[#706f6c] dark:text-[#A1A09A]">Registered</dt>
                        <dd class="font-medium">{{ $accountStats['created_at'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-[#706f6c] dark:text-[#A1A09A]">Updated</dt>
                        <dd class="font-medium">{{ $accountStats['updated_at'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-[#706f6c] dark:text-[#A1A09A]">Email verified</dt>
                        <dd class="font-medium">{{ $accountStats['email_verified'] ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-[#706f6c] dark:text-[#A1A09A]">Password reset</dt>
                        <dd class="text-right font-medium">{{ $accountStats['password_reset_requested_at'] ?? 'No open request' }}</dd>
                    </div>
                </dl>
            </aside>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Security</p>
                    <h2 class="mt-2 text-2xl font-semibold">Active sessions</h2>
                    <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Sessions are loaded from the database session table.</p>
                </div>

                <form method="POST" action="{{ route('profile.sessions.destroy-other') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-sm border border-[#19140035] px-4 py-2 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Sign out other sessions
                    </button>
                </form>
            </div>

            <div class="mt-6 overflow-hidden rounded-xl border border-[#19140020] dark:border-[#3E3E3A]">
                @forelse ($sessions as $session)
                    <div class="flex flex-col gap-2 border-b border-[#19140012] p-4 last:border-b-0 dark:border-[#3E3E3A] sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-medium">{{ $session['ip_address'] }}</p>
                            <p class="mt-1 break-all text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $session['user_agent'] }}</p>
                        </div>
                        <div class="shrink-0 text-left text-sm sm:text-right">
                            @if ($session['is_current'])
                                <span class="rounded-full bg-[#f53003]/10 px-2.5 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">Current session</span>
                            @endif
                            <p class="mt-2 text-[#706f6c] dark:text-[#A1A09A]">{{ $session['last_activity'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        No database sessions found for this account. This can happen when the app uses a non-database session driver.
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-layouts.app>
