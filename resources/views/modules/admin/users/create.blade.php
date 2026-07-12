<x-layouts.admin title="Create User">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Create user</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Add a new user to the system.
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <form method="POST" action="{{ route('admin.users.store') }}" enctype="multipart/form-data" class="max-w-2xl">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Name</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name') }}"
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
                        value="{{ old('email') }}"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('email') border-[#f53003] @enderror"
                    />
                    @error('email')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Password</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('password') border-[#f53003] @enderror"
                    />
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
                            <option value="{{ $role->id }}" @selected(old('role') == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <label for="avatar" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Avatar (optional)</label>
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

            <div class="mt-8 flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                    Create user
                </button>
                <a href="{{ route('admin.users.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
