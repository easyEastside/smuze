<x-layouts.app title="Server">
    <section class="w-full max-w-5xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Meine Server</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-[#706f6c] dark:text-[#A1A09A]">
                        Deine Server mit SSH-Verbindungsmöglichkeit.
                    </p>
                </div>
                <a
                    href="{{ route('server.create') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                    </svg>
                    Server hinzufügen
                </a>
            </div>
        </div>

        @if ($servers->isEmpty())
            <div class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                    Du hast noch keine Server. <a href="{{ route('server.create') }}" class="text-[#f53003] hover:underline dark:text-[#FF4433]">Ersten Server hinzufügen</a>
                </p>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($servers as $server)
                    <div class="rounded-2xl bg-white p-5 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="size-3 shrink-0 rounded-full {{ $server->is_reachable ? 'bg-green-500' : 'bg-red-500' }}"></div>
                                <div>
                                    <h2 class="text-lg font-semibold tracking-tight">{{ $server->name }}</h2>
                                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">{{ $server->host }}:{{ $server->port }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('server.edit', $server) }}" class="text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path d="M5.433 13.917l1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65z" />
                                        <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" />
                                    </svg>
                                </a>
                                <form action="{{ route('server.destroy', $server) }}" method="POST" class="inline" onsubmit="return confirm('Server {{ $server->name }} löschen?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[#706f6c] hover:text-[#f53003] dark:text-[#A1A09A] dark:hover:text-[#FF4433]">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5zM10 4c-.84 0-1.673.025-2.5.075V3.75c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25v.325C11.673 4.025 10.84 4 10 4zM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-4 space-y-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            <p><span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Benutzer:</span> {{ $server->username }}</p>
                            <p><span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Authentifizierung:</span> {{ $server->auth_type === 'key' ? 'SSH-Key' : 'Passwort' }}</p>
                            @if ($server->notes)
                                <p><span class="font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Notizen:</span> {{ $server->notes }}</p>
                            @endif
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <a
                                href="{{ route('server.system', $server) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]"
                            >
                                System
                            </a>
                            <button
                                type="button"
                                onclick="navigator.clipboard.writeText('ssh {{ $server->username }}@{{ $server->host }} -p {{ $server->port }}').then(() => { this.textContent = 'Kopiert!'; setTimeout(() => this.textContent = 'SSH verbinden', 2000); })"
                                class="inline-flex items-center gap-2 rounded-lg border border-[#19140035] px-4 py-2 text-sm font-medium transition hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                            >
                                SSH verbinden
                            </button>
                            <span class="inline-flex items-center rounded-lg bg-[#19140008] px-3 py-2 text-xs text-[#706f6c] dark:bg-[#fffaed08] dark:text-[#A1A09A]">
                                {{ $server->is_reachable ? 'Erreichbar' : 'Nicht erreichbar' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.app>
