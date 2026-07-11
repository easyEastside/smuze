@php
    $linkClass = 'rounded-sm px-3 py-1.5 text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white';
    $activeLinkClass = 'rounded-sm bg-[#1b1b18] px-3 py-1.5 text-white dark:bg-[#EDEDEC] dark:text-[#1C1C1A]';

    $primaryLinks = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard'],
        ['label' => 'Shop', 'route' => 'shop.index', 'active' => 'shop.*'],
        ['label' => 'Bank', 'route' => 'bank.index', 'active' => 'bank.*'],
        ['label' => 'Inventory', 'route' => 'inventory.index', 'active' => 'inventory.*'],
    ];

    $secondaryLinks = [
        ['label' => 'Achievements', 'route' => 'achievements.index', 'active' => 'achievements.*'],
        ['label' => 'Leaderboard', 'route' => 'leaderboard', 'active' => 'leaderboard'],
        ['label' => 'Messages', 'route' => 'messages.index', 'active' => 'messages.*'],
        ['label' => 'Surveys', 'route' => 'surveys.index', 'active' => 'surveys.*'],
        ['label' => 'Quests', 'route' => 'quests.index', 'active' => 'quests.*'],
        ['label' => 'Profile', 'route' => 'profile.show', 'active' => 'profile.show'],
        ['label' => auth()->user()->credits.' Credits', 'route' => 'profile.credits', 'active' => 'profile.credits'],
    ];
@endphp

<header class="border-b border-[#19140020] bg-white/80 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#161615]/80">
    <nav class="relative mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-4" aria-label="Primary navigation">
        <a href="{{ url('/') }}" class="shrink-0 text-sm font-semibold tracking-tight">
            {{ config('app.name', 'Laravel') }}
        </a>

        <div class="hidden items-center gap-1 text-sm lg:flex">
            @foreach ($primaryLinks as $link)
                <a href="{{ route($link['route']) }}" class="{{ request()->routeIs($link['active']) ? $activeLinkClass : $linkClass }}">
                    {{ $link['label'] }}
                </a>
            @endforeach

            @can('access-admin')
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? $activeLinkClass : $linkClass }}">
                    Admin
                </a>
            @endcan

            <div class="group relative">
                <button
                    type="button"
                    class="rounded-sm border border-[#19140035] px-3 py-1.5 text-[#706f6c] hover:border-[#1915014a] hover:text-[#1b1b18] dark:border-[#3E3E3A] dark:text-[#A1A09A] dark:hover:border-[#62605b] dark:hover:text-white"
                    data-navbar-toggle="desktop"
                    aria-expanded="false"
                    aria-controls="navbar-desktop-menu"
                >
                    More
                </button>

                <div id="navbar-desktop-menu" class="absolute right-0 top-full z-40 hidden w-48 rounded-xl border border-[#19140020] bg-white p-2 text-sm shadow-lg group-hover:block group-focus-within:block dark:border-[#3E3E3A] dark:bg-[#161615]" data-navbar-menu="desktop">
                    @foreach ($secondaryLinks as $link)
                        <a href="{{ route($link['route']) }}" class="block {{ request()->routeIs($link['active']) ? $activeLinkClass : $linkClass }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach

                    <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-[#19140020] pt-2 dark:border-[#3E3E3A]">
                        @csrf
                        <button type="submit" class="w-full rounded-sm px-3 py-1.5 text-left text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white">
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 lg:hidden">
            <a href="{{ route('profile.credits') }}" class="rounded-sm px-3 py-1.5 text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white">
                {{ auth()->user()->credits }} Credits
            </a>

            <button
                type="button"
                class="rounded-sm border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                data-navbar-toggle="mobile"
                aria-expanded="false"
                aria-controls="navbar-mobile-menu"
            >
                Menu
            </button>
        </div>

        <div id="navbar-mobile-menu" class="absolute left-4 right-4 top-full z-40 mt-2 hidden rounded-xl border border-[#19140020] bg-white p-3 text-sm shadow-lg dark:border-[#3E3E3A] dark:bg-[#161615] lg:hidden" data-navbar-menu="mobile">
            <div class="grid gap-1">
                @foreach ($primaryLinks as $link)
                    <a href="{{ route($link['route']) }}" class="{{ request()->routeIs($link['active']) ? $activeLinkClass : $linkClass }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach

                @can('access-admin')
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? $activeLinkClass : $linkClass }}">
                        Admin
                    </a>
                @endcan

                @foreach ($secondaryLinks as $link)
                    <a href="{{ route($link['route']) }}" class="{{ request()->routeIs($link['active']) ? $activeLinkClass : $linkClass }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach

                <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-[#19140020] pt-2 dark:border-[#3E3E3A]">
                    @csrf
                    <button type="submit" class="w-full rounded-sm px-3 py-1.5 text-left text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </nav>
</header>
