@props(['active' => null])

<aside
    id="admin-sidebar"
    class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full border-r border-[#19140020] bg-white transition-transform duration-200 dark:border-[#3E3E3A] dark:bg-[#161615] md:static md:inset-auto md:translate-x-0"
>
    <div class="flex h-full flex-col overflow-y-auto py-6">
        <div class="flex items-center justify-between px-4 pb-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-[#f53003] dark:text-[#FF4433]">Admin</p>

            <button
                id="admin-sidebar-close"
                type="button"
                class="flex size-8 items-center justify-center rounded-lg text-[#706f6c] hover:bg-[#19140008] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#fffaed0a] dark:hover:text-white md:hidden"
                aria-label="Close sidebar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                </svg>
            </button>
        </div>

        <nav class="flex flex-1 flex-col gap-1 px-3 text-sm">
            <x-admin.sidebar-link
                :active="request()->routeIs('admin.dashboard')"
                href="{{ route('admin.dashboard') }}"
            >
                Dashboard
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.users.*')"
                href="{{ route('admin.users.index') }}"
            >
                Users
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.achievements.*')"
                href="{{ route('admin.achievements.index') }}"
            >
                Achievements
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.shop-items.*')"
                href="{{ route('admin.shop-items.index') }}"
            >
                Shop Items
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.surveys.*')"
                href="{{ route('admin.surveys.index') }}"
            >
                Surveys
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.inventory.*')"
                href="{{ route('admin.inventory.create') }}"
            >
                Gift Item
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.roles.*')"
                href="{{ route('admin.roles.index') }}"
            >
                Roles
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.permissions.*')"
                href="{{ route('admin.permissions.index') }}"
            >
                Permissions
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.agent')"
                href="{{ route('admin.agent') }}"
            >
                Agent
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.servers.*')"
                href="{{ route('admin.servers.index') }}"
            >
                Servers
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.errors.*')"
                href="{{ route('admin.errors') }}"
            >
                Fehlerberichte
            </x-admin.sidebar-link>

            <x-admin.sidebar-link
                :active="request()->routeIs('admin.settings')"
                href="{{ route('admin.settings') }}"
            >
                Settings
            </x-admin.sidebar-link>
        </nav>
    </div>
</aside>
