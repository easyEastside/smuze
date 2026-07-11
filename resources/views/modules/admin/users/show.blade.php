<x-layouts.admin title="User Details">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">User details</h1>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-col items-center text-center">
                @if ($user->avatar_path)
                    <img src="{{ Storage::url($user->avatar_path) }}" alt="" class="size-24 rounded-full object-cover" />
                @else
                    <span class="flex size-24 items-center justify-center rounded-full bg-[#f53003]/10 text-3xl font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </span>
                @endif

                <h2 class="mt-4 text-xl font-semibold">{{ $user->name }}</h2>

                @foreach ($user->roles as $role)
                    <span class="mt-2 rounded-full bg-[#f53003]/10 px-3 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                        {{ $role->name }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Information</p>
                <a href="{{ route('admin.users.edit', $user) }}" class="text-sm font-medium text-[#f53003] hover:text-[#d42a02] dark:text-[#FF4433] dark:hover:text-[#e63a2e]">
                    Edit user
                </a>
            </div>

            <dl class="mt-6 divide-y divide-[#19140012] dark:divide-[#3E3E3A]">
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Email</dt>
                    <dd class="text-sm font-medium">{{ $user->email }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Email verified</dt>
                    <dd class="text-sm font-medium">
                        @if ($user->email_verified_at)
                            <span class="text-green-600 dark:text-green-400">Yes</span>
                        @else
                            <span class="text-[#f53003] dark:text-[#FF4433]">No</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Credits</dt>
                    <dd class="text-sm font-medium">{{ $user->credits }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Registered</dt>
                    <dd class="text-sm font-medium">{{ $user->created_at->format('M j, Y \\a\\t g:i A') }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Last updated</dt>
                    <dd class="text-sm font-medium">{{ $user->updated_at->format('M j, Y \\a\\t g:i A') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">&larr; Back to users</a>
    </div>
</x-layouts.admin>
