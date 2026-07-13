<x-layouts.app title="Profile">
    <section class="w-full max-w-5xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-5">
                    <div class="flex size-20 items-center justify-center overflow-hidden rounded-2xl bg-[#f53003]/10 text-2xl font-semibold text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }} avatar" class="size-full object-cover">
                        @else
                            {{ str($user->name)->substr(0, 1)->upper() }}
                        @endif
                    </div>

                    <div>
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Profile</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $user->name }}</h1>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $user->email }}</p>
                    </div>
                </div>

                <a href="{{ route('dashboard') }}" class="w-fit rounded-sm border border-[#19140035] px-4 py-2 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                    Back to dashboard
                </a>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="flex flex-col gap-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Account</p>
                        <h2 class="mt-2 text-2xl font-semibold">Profile details</h2>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Update your name and email address.</p>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" class="flex flex-col gap-5">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <label for="name" class="text-sm font-medium">Name</label>
                                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
                                @error('name')
                                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="email" class="text-sm font-medium">Email</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
                                @error('email')
                                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="w-fit rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                            Save profile
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Avatar</p>
                        <h2 class="mt-2 text-2xl font-semibold">Profile photo</h2>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Upload a JPG, PNG, GIF, BMP, or WebP image up to 2 MB.</p>
                    </div>

                    <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                        @csrf
                        @method('PATCH')

                        <div class="flex flex-col gap-2">
                            <label for="avatar" class="text-sm font-medium">Avatar image</label>
                            <input id="avatar" name="avatar" type="file" accept="image/*" required class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none file:mr-4 file:rounded-sm file:border-0 file:bg-[#1b1b18] file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-white dark:border-[#3E3E3A] dark:file:bg-[#eeeeec] dark:file:text-[#1C1C1A]">
                            @error('avatar')
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="w-fit rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                                Upload avatar
                            </button>
                        </div>
                    </form>

                    @if ($user->avatar_path)
                        <form method="POST" action="{{ route('profile.avatar.destroy') }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-sm border border-[#19140035] px-4 py-2 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                Remove avatar
                            </button>
                        </form>
                    @endif
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Security</p>
                        <h2 class="mt-2 text-2xl font-semibold">Change password</h2>
                        <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Confirm your current password before setting a new one.</p>
                    </div>

                    <form method="POST" action="{{ route('profile.password.update') }}" class="flex flex-col gap-5">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div class="flex flex-col gap-2">
                                <label for="current_password" class="text-sm font-medium">Current password</label>
                                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
                                @error('current_password')
                                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="password" class="text-sm font-medium">New password</label>
                                <input id="password" name="password" type="password" required autocomplete="new-password" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
                                @error('password')
                                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-col gap-2">
                                <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
                            </div>
                        </div>

                        <button type="submit" class="w-fit rounded-sm border border-black bg-[#1b1b18] px-5 py-2.5 text-sm font-medium text-white hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white">
                            Update password
                        </button>
                    </form>
                </div>
            </div>

            <aside class="flex flex-col gap-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Credits</p>
                    <p class="mt-2 text-3xl font-bold {{ $user->credits >= 0 ? 'text-green-600 dark:text-green-400' : 'text-[#f53003] dark:text-[#FF4433]' }}">
                        {{ $user->credits }}
                    </p>
                    <a href="{{ route('profile.credits') }}" class="mt-2 inline-block text-sm text-[#f53003] hover:underline dark:text-[#FF4433]">
                        View history &rarr;
                    </a>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
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
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="flex flex-col gap-4">
                        <div>
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Sessions</p>
                            <h2 class="mt-2 text-2xl font-semibold">Active sessions</h2>
                            <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Sessions loaded from the database session table.</p>
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
                            <div class="flex flex-col gap-2 border-b border-[#19140012] p-4 last:border-b-0 dark:border-[#3E3E3A]">
                                <div>
                                    <p class="text-sm font-medium">{{ $session['ip_address'] }}</p>
                                    <p class="mt-1 break-all text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $session['user_agent'] }}</p>
                                </div>
                                <div class="text-sm">
                                    @if ($session['is_current'])
                                        <span class="rounded-full bg-[#f53003]/10 px-2.5 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">Current session</span>
                                    @endif
                                    <p class="mt-2 text-[#706f6c] dark:text-[#A1A09A]">{{ $session['last_activity'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                No database sessions found for this account.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Danger zone</p>
                    <h2 class="mt-2 text-2xl font-semibold">Delete account</h2>
                    <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">This permanently deletes your user account and signs you out.</p>

                    <form method="POST" action="{{ route('profile.destroy') }}" class="mt-5 flex flex-col gap-4">
                        @csrf
                        @method('DELETE')

                        <div class="flex flex-col gap-2">
                            <label for="delete_current_password" class="text-sm font-medium">Current password</label>
                            <input id="delete_current_password" name="current_password" type="password" required autocomplete="current-password" class="rounded-sm border border-[#19140035] bg-transparent px-3 py-2 text-sm outline-none focus:border-[#f53003] dark:border-[#3E3E3A] dark:focus:border-[#FF4433]">
                            @error('current_password')
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-fit rounded-sm border border-[#f53003] bg-[#f53003] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#d92b03] dark:border-[#FF4433] dark:bg-[#FF4433] dark:hover:bg-[#ff5b4f]">
                            Delete account
                        </button>
                    </form>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>
