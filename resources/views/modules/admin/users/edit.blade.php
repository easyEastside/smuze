<x-layouts.admin title="Edit User">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Edit user</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Update user information for <strong>{{ $user->name }}</strong>.
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

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

                    <p class="mt-4 text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Avatar</p>
                </div>

                <div class="mt-6">
                    <input
                        type="file"
                        name="avatar"
                        id="avatar"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        class="mt-1 w-full text-sm text-[#706f6c] file:mr-3 file:rounded-lg file:border-0 file:bg-[#f53003]/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-[#f53003] hover:file:bg-[#f53003]/20 dark:text-[#A1A09A] dark:file:bg-[#FF4433]/15 dark:file:text-[#FF4433] dark:hover:file:bg-[#FF4433]/25"
                    />
                    @error('avatar')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                @if ($user->avatar_path)
                    <div class="mt-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="remove_avatar" value="1" class="rounded border-[#19140020] text-[#f53003] dark:border-[#3E3E3A] dark:bg-[#161615]" />
                            <span class="text-[#706f6c] dark:text-[#A1A09A]">Remove current avatar</span>
                        </label>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Name</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $user->name) }}"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('name') border-[#f53003] @enderror"
                        />
                        @error('name')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Email</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            value="{{ old('email', $user->email) }}"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('email') border-[#f53003] @enderror"
                        />
                        @error('email')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">New password</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('password') border-[#f53003] @enderror"
                        />
                        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Leave blank to keep current password.</p>
                        @error('password')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Role</label>
                        <select
                            name="role"
                            id="role"
                            class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('role') border-[#f53003] @enderror"
                        >
                            <option value="">Select a role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role', $user->roles->first()?->id) == $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                        Update user
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Cancel</a>
                </div>
            </div>
        </div>
    </form>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Credits</p>
        <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
            Current balance: <strong>{{ $user->credits }}</strong>
        </p>
        <form method="POST" action="{{ route('admin.users.credits.adjust', $user) }}" class="mt-4 flex flex-col gap-4">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="amount" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Amount</label>
                    <input
                        type="number"
                        name="amount"
                        id="amount"
                        value="{{ old('amount') }}"
                        placeholder="e.g. 50 or -20"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('amount') border-[#f53003] @enderror"
                    />
                    @error('amount')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Description</label>
                    <input
                        type="text"
                        name="description"
                        id="description"
                        value="{{ old('description') }}"
                        placeholder="Reason for adjustment"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('description') border-[#f53003] @enderror"
                    />
                    @error('description')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <button type="submit" class="w-fit rounded-lg bg-[#f53003] px-6 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                Adjust credits
            </button>
        </form>
    </div>
</x-layouts.admin>
