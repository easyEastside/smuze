<x-layouts.admin title="Roles">
    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div>
            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Admin area</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Roles</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                Manage roles and their permissions.
            </p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a
                href="{{ route('admin.roles.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                </svg>
                New role
            </a>

            <form method="GET" action="{{ route('admin.roles.index') }}">
                <div class="relative">
                    <input
                        type="text"
                        name="search"
                        placeholder="Search roles..."
                        value="{{ $search }}"
                        class="w-full rounded-lg border border-[#19140020] bg-white px-3 py-2 pl-9 text-sm text-[#1b1b18] dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC] sm:w-64"
                    />
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-[#706f6c] dark:text-[#A1A09A]">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                    </svg>
                </div>

                @if ($search)
                    <a href="{{ route('admin.roles.index') }}" class="text-sm text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Clear</a>
                @endif
            </form>
        </div>

        <div class="mt-6 overflow-hidden rounded-xl border border-[#19140020] dark:border-[#3E3E3A]">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[#19140012] bg-[#19140005] dark:border-[#3E3E3A] dark:bg-[#fffaed05]">
                        <th class="px-4 py-3 text-left font-medium text-[#706f6c] dark:text-[#A1A09A]">Role</th>
                        <th class="px-4 py-3 text-left font-medium text-[#706f6c] dark:text-[#A1A09A]">Permissions</th>
                        <th class="px-4 py-3 text-left font-medium text-[#706f6c] dark:text-[#A1A09A]">Users</th>
                        <th class="px-4 py-3 text-right font-medium text-[#706f6c] dark:text-[#A1A09A]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr class="border-b border-[#19140012] last:border-b-0 dark:border-[#3E3E3A]">
                            <td class="px-4 py-3 font-medium">{{ $role->name }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-[#f53003]/10 px-2.5 py-1 text-xs font-medium text-[#f53003] dark:bg-[#FF4433]/15 dark:text-[#FF4433]">
                                    {{ $role->permissions_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-[#706f6c] dark:text-[#A1A09A]">{{ $role->users_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Edit</a>
                                @if ($role->name !== 'super-admin')
                                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="ml-3 inline" onsubmit="return confirm('Delete role {{ $role->name }}? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                No roles found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $roles->links() }}
        </div>
    </div>
</x-layouts.admin>
