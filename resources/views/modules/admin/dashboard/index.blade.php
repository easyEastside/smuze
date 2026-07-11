<x-layouts.admin title="Dashboard">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Admin Dashboard</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                System overview and management tools.
            </p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Total users</p>
            <p class="mt-2 text-3xl font-semibold">{{ $userCount }}</p>
        </div>
        <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Roles</p>
            <p class="mt-2 text-3xl font-semibold">{{ $rolesCount }}</p>
        </div>
        <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Permissions</p>
            <p class="mt-2 text-3xl font-semibold">{{ $permissionsCount }}</p>
        </div>
        <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Active sessions</p>
            <p class="mt-2 text-3xl font-semibold">{{ $systemStats['active_sessions'] }}</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Users</p>
            <h2 class="mt-2 text-2xl font-semibold">Recent registrations</h2>
            <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Latest 5 users who signed up.</p>

            <div class="mt-6 overflow-hidden rounded-xl border border-[#19140020] dark:border-[#3E3E3A]">
                @forelse ($recentUsers as $recentUser)
                    <div class="flex flex-col gap-1 border-b border-[#19140012] p-4 last:border-b-0 dark:border-[#3E3E3A] sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium">{{ $recentUser['name'] }}</p>
                            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $recentUser['email'] }}</p>
                        </div>
                        <div class="shrink-0 text-left text-sm sm:text-right">
                            <span class="rounded-full bg-[#f53003]/10 px-2.5 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">{{ $recentUser['role'] }}</span>
                            <p class="mt-1 text-[#706f6c] dark:text-[#A1A09A]">{{ $recentUser['created_at'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        No users found.
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Users by role</p>
            <dl class="mt-5 flex flex-col gap-4 text-sm">
                @forelse ($usersByRole as $role => $count)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-[#706f6c] dark:text-[#A1A09A]">{{ $role }}</dt>
                        <dd class="font-medium">{{ $count }}</dd>
                    </div>
                @empty
                    <div class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        No users with roles.
                    </div>
                @endforelse
            </dl>
        </aside>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">System</p>
            <h2 class="mt-2 text-2xl font-semibold">Queue health</h2>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Queued jobs</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $systemStats['queued_jobs'] }}</p>
                </div>
                <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Failed jobs</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $systemStats['failed_jobs'] }}</p>
                </div>
                <div class="rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Active sessions</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $systemStats['active_sessions'] }}</p>
                </div>
            </div>
        </div>

        <aside class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Active sessions</p>
            <div class="mt-5 overflow-hidden rounded-xl border border-[#19140020] dark:border-[#3E3E3A]">
                @forelse ($sessions as $session)
                    <div class="flex flex-col gap-1 border-b border-[#19140012] p-3 last:border-b-0 dark:border-[#3E3E3A]">
                        <p class="text-sm font-medium">{{ $session['ip_address'] }}</p>
                        <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">User #{{ $session['user_id'] }}</p>
                        <div class="flex items-center gap-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            @if ($session['is_current'])
                                <span class="rounded-full bg-[#f53003]/10 px-2 py-0.5 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">You</span>
                            @endif
                            {{ $session['last_activity'] }}
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        No active sessions.
                    </div>
                @endforelse
            </div>
        </aside>
    </div>
</x-layouts.admin>
