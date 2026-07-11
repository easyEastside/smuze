<x-layouts.admin title="Create Server">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Create server</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Add a new server for a user.
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <form method="POST" action="{{ route('admin.servers.store') }}" class="max-w-2xl">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="user_id" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">User</label>
                    <select
                        name="user_id"
                        id="user_id"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('user_id') border-[#f53003] @enderror"
                    >
                        <option value="">Select user...</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
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

                <div class="sm:col-span-2">
                    <label for="host" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Host</label>
                    <input
                        type="text"
                        name="host"
                        id="host"
                        value="{{ old('host') }}"
                        placeholder="example.com or 192.168.1.1"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('host') border-[#f53003] @enderror"
                    />
                    @error('host')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="port" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Port</label>
                    <input
                        type="number"
                        name="port"
                        id="port"
                        value="{{ old('port', 22) }}"
                        min="1"
                        max="65535"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('port') border-[#f53003] @enderror"
                    />
                    @error('port')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Username</label>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        value="{{ old('username') }}"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('username') border-[#f53003] @enderror"
                    />
                    @error('username')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="auth_type" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Authentication type</label>
                    <select
                        name="auth_type"
                        id="auth_type"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('auth_type') border-[#f53003] @enderror"
                    >
                        <option value="key" @selected(old('auth_type') === 'key')>SSH-Key</option>
                        <option value="password" @selected(old('auth_type') === 'password')>Password</option>
                    </select>
                    @error('auth_type')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="credentials" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Credentials</label>
                    <textarea
                        name="credentials"
                        id="credentials"
                        rows="3"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('credentials') border-[#f53003] @enderror"
                    >{{ old('credentials') }}</textarea>
                    @error('credentials')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Notes (optional)</label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="2"
                        class="mt-1 w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] @error('notes') border-[#f53003] @enderror"
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-[#f53003]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-8 flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                    Create server
                </button>
                <a href="{{ route('admin.servers.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
