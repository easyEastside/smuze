@php
    $linkClass = 'rounded-sm px-3 py-1.5 text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-white';
    $activeLinkClass = 'rounded-sm bg-[#1b1b18] px-3 py-1.5 text-white dark:bg-[#EDEDEC] dark:text-[#1C1C1A]';
    $disabledLinkClass = 'cursor-not-allowed rounded-sm px-3 py-1.5 text-[#b7b5ad] dark:text-[#62605b]';
    $dropdownLinkClass = 'block rounded-sm px-3 py-1.5 text-[#706f6c] hover:bg-[#f5f5f4] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-[#2b2b28] dark:hover:text-white';

    $unimportantLinks = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard'],
        ['label' => 'Shop', 'route' => 'shop.index', 'active' => 'shop.*'],
        ['label' => 'Bank', 'route' => 'bank.index', 'active' => 'bank.*'],
        ['label' => 'Inventory', 'route' => 'inventory.index', 'active' => 'inventory.*'],
        ['label' => 'Achievements', 'route' => 'achievements.index', 'active' => 'achievements.*'],
        ['label' => 'Leaderboard', 'route' => 'leaderboard', 'active' => 'leaderboard'],
        ['label' => 'Messages', 'route' => 'messages.index', 'active' => 'messages.*'],
        ['label' => 'Surveys', 'route' => 'surveys.index', 'active' => 'surveys.*'],
        ['label' => 'Quests', 'route' => 'quests.index', 'active' => 'quests.*'],
        ['label' => 'Profile', 'route' => 'profile.show', 'active' => 'profile.show'],
        ['label' => 'Settings', 'route' => 'settings.edit', 'active' => 'settings.*'],
        ['label' => auth()->user()->credits.' Credits', 'route' => 'profile.credits', 'active' => 'profile.credits'],
    ];

    $currentServer = request()->route('server');

    $serverLinks = [
        ['label' => 'System', 'route' => 'server.system', 'active' => 'server.system'],
        ['label' => 'Terminal', 'route' => 'server.terminal', 'active' => 'server.terminal'],
        ['label' => 'Firewall', 'route' => 'server.firewall.index', 'active' => 'server.firewall.*'],
        ['label' => 'Dienste', 'route' => 'server.services.index', 'active' => 'server.services.*'],
        ['label' => 'GitHub', 'route' => 'server.github.index', 'active' => 'server.github.*'],
    ];

    $webhostingLinks = [
        ['label' => 'Apache', 'route' => 'server.apache.index', 'active' => 'server.apache.*'],
        ['label' => 'Nginx', 'route' => 'server.nginx.index', 'active' => 'server.nginx.*'],
        ['label' => 'MySQL', 'route' => 'server.mysql.index', 'active' => 'server.mysql.*'],
    ];
@endphp

<header class="border-b border-[#19140020] bg-white/80 backdrop-blur dark:border-[#3E3E3A] dark:bg-[#161615]/80">
    <nav class="relative mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-4" aria-label="Primary navigation">
        <a href="{{ url('/') }}" class="shrink-0 text-sm font-semibold tracking-tight">
            {{ config('app.name', 'Laravel') }}
        </a>

        <div class="hidden items-center gap-1 text-sm lg:flex">
            <a href="{{ route('server.index') }}" class="{{ request()->routeIs('server.index') ? $activeLinkClass : $linkClass }}">Server</a>

            @foreach ($serverLinks as $link)
                @if ($currentServer)
                    <a href="{{ route($link['route'], $currentServer) }}" class="{{ request()->routeIs($link['active']) ? $activeLinkClass : $linkClass }}">{{ $link['label'] }}</a>
                @else
                    <span class="{{ $disabledLinkClass }}" aria-disabled="true" title="Bitte zuerst Server auswählen">{{ $link['label'] }}</span>
                @endif
            @endforeach

            <div class="group relative">
                <button type="button" class="{{ request()->routeIs('server.apache.*', 'server.nginx.*', 'server.mysql.*') ? $activeLinkClass : $linkClass }} inline-flex items-center gap-1">
                    Webhosting
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="absolute left-0 top-full z-40 hidden w-48 rounded-xl border border-[#19140020] bg-white p-2 text-sm shadow-lg group-hover:block group-focus-within:block dark:border-[#3E3E3A] dark:bg-[#161615]">
                    @foreach ($webhostingLinks as $link)
                        @if ($currentServer)
                            <a href="{{ route($link['route'], $currentServer) }}" class="{{ $dropdownLinkClass }} {{ request()->routeIs($link['active']) ? 'font-medium' : '' }}">{{ $link['label'] }}</a>
                        @else
                            <span class="block {{ $disabledLinkClass }}" aria-disabled="true" title="Bitte zuerst Server auswählen">{{ $link['label'] }}</span>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="group relative">
                <button type="button" class="{{ request()->routeIs('dashboard', 'shop.*', 'bank.*', 'inventory.*', 'achievements.*', 'leaderboard', 'messages.*', 'surveys.*', 'quests.*', 'profile.*', 'settings.*') ? $activeLinkClass : $linkClass }} inline-flex items-center gap-1">
                    Unwichtig
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="absolute left-0 top-full z-40 hidden w-48 rounded-xl border border-[#19140020] bg-white p-2 text-sm shadow-lg group-hover:block group-focus-within:block dark:border-[#3E3E3A] dark:bg-[#161615]">
                    @foreach ($unimportantLinks as $link)
                        <a href="{{ route($link['route']) }}" class="{{ $dropdownLinkClass }} {{ request()->routeIs($link['active']) ? 'font-medium' : '' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            @can('access-admin')
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? $activeLinkClass : $linkClass }}">Admin</a>
            @endcan

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="{{ $linkClass }}">Log out</button>
            </form>
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
                <a href="{{ route('server.index') }}" class="{{ request()->routeIs('server.index') ? $activeLinkClass : $linkClass }}">Server</a>

                @foreach ($serverLinks as $link)
                    @if ($currentServer)
                        <a href="{{ route($link['route'], $currentServer) }}" class="{{ request()->routeIs($link['active']) ? $activeLinkClass : $linkClass }}">{{ $link['label'] }}</a>
                    @else
                        <span class="{{ $disabledLinkClass }}" aria-disabled="true" title="Bitte zuerst Server auswählen">{{ $link['label'] }}</span>
                    @endif
                @endforeach

                <div class="relative">
                    <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" class="{{ request()->routeIs('server.apache.*', 'server.nginx.*', 'server.mysql.*') ? $activeLinkClass : $linkClass }} inline-flex w-full items-center justify-between gap-1">
                        Webhosting
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="ml-3 mt-1 hidden space-y-1 border-l border-[#19140020] pl-3 dark:border-[#3E3E3A]">
                        @foreach ($webhostingLinks as $link)
                            @if ($currentServer)
                                <a href="{{ route($link['route'], $currentServer) }}" class="block {{ $dropdownLinkClass }} {{ request()->routeIs($link['active']) ? 'font-medium' : '' }}">{{ $link['label'] }}</a>
                            @else
                                <span class="block {{ $disabledLinkClass }}" aria-disabled="true" title="Bitte zuerst Server auswählen">{{ $link['label'] }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="relative">
                    <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden')" class="{{ request()->routeIs('dashboard', 'shop.*', 'bank.*', 'inventory.*', 'achievements.*', 'leaderboard', 'messages.*', 'surveys.*', 'quests.*', 'profile.*', 'settings.*') ? $activeLinkClass : $linkClass }} inline-flex w-full items-center justify-between gap-1">
                        Unwichtig
                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="ml-3 mt-1 hidden space-y-1 border-l border-[#19140020] pl-3 dark:border-[#3E3E3A]">
                        @foreach ($unimportantLinks as $link)
                            <a href="{{ route($link['route']) }}" class="block {{ $dropdownLinkClass }} {{ request()->routeIs($link['active']) ? 'font-medium' : '' }}">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>

                @can('access-admin')
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? $activeLinkClass : $linkClass }}">
                        Admin
                    </a>
                @endcan

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
