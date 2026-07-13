<x-layouts.app title="Terminal: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Freies Terminal</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $server->name }}</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        System
                    </a>
                    <a href="{{ route('server.index') }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Zurück
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-2xl bg-[#050505] p-4 text-[#EDEDEC] shadow-[inset_0_0_0_1px_rgba(255,250,237,0.16)] sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4">
                <div>
                    <p class="text-sm font-medium text-white">Interaktive Shell</p>
                    <p class="mt-1 text-xs text-[#A1A09A]">xterm.js verbindet direkt mit dem Server-Agent. Tastenkombinationen, Prompts und ANSI-Ausgaben laufen &uuml;ber eine echte PTY-Sitzung.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs text-[#A1A09A]">
                    <span id="terminal-status" class="rounded-full border border-white/15 px-3 py-1 text-[#A1A09A]">Bereit</span>
                    <button id="terminal-connect" type="button" class="rounded-lg border border-white/15 px-3 py-1.5 text-xs text-white hover:border-white/30">
                        Verbinden
                    </button>
                    <button id="terminal-disconnect" type="button" class="hidden rounded-lg border border-red-400/40 px-3 py-1.5 text-xs text-red-200 hover:border-red-300">
                        Trennen
                    </button>
                </div>
            </div>

            <div
                id="terminal-root"
                class="mt-4 min-h-[520px] overflow-hidden rounded-xl border border-white/10 bg-black p-2"
                data-terminal-token-url="{{ route('server.agent.terminal-token', $server) }}"
                data-csrf-token="{{ csrf_token() }}"
            ></div>
        </div>
    </section>

    @push('scripts')
        @vite('resources/js/terminal.js')
    @endpush
</x-layouts.app>
