<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/server-websocket.js'])
    </head>
    <body class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] antialiased dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        @php
            $floatingCommandServer = request()->route('server');
            $showFloatingTerminal = auth()->check() && auth()->user()->show_floating_terminal;
        @endphp

        <div class="flex min-h-screen flex-col">
            <x-navbar />

            <main class="flex flex-1 items-center justify-center px-6 py-10">
                {{ $slot }}
            </main>
        </div>

        @if ($showFloatingTerminal && $floatingCommandServer instanceof \App\Models\Server && ! request()->routeIs('server.terminal.*'))
            <aside
                id="floating-command-log"
                data-server-id="{{ $floatingCommandServer->id }}"
                data-server-name="{{ $floatingCommandServer->name }}"
                data-server-host="{{ $floatingCommandServer->host }}"
                data-server-username="{{ $floatingCommandServer->username }}"
                data-debug-enabled="{{ auth()->user()->write_debug_logs ? '1' : '0' }}"
                data-session-endpoint="{{ route('server.socket.session', $floatingCommandServer) }}"
                data-csrf-token="{{ csrf_token() }}"
                class="fixed bottom-4 right-4 z-40 hidden w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-[#19140020] bg-[#0b0f14] text-[#d6deeb] shadow-2xl dark:border-[#3E3E3A] sm:bottom-6 sm:right-6"
                aria-label="Schwebendes Terminal"
            >
                <div class="flex items-center justify-between gap-3 border-b border-white/10 bg-white/5 px-4 py-3">
                    <button type="button" data-command-log-toggle class="min-w-0 text-left">
                        <span class="block truncate text-sm font-semibold">Terminal: {{ $floatingCommandServer->name }}</span>
                        <span data-command-log-status class="block text-xs text-slate-400">Bereit</span>
                    </button>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" data-command-log-connect class="rounded-lg border border-white/10 px-2.5 py-1 text-xs text-slate-300 hover:border-white/25 hover:text-white">
                            Verbinden
                        </button>
                        <button type="button" data-command-log-disconnect class="hidden rounded-lg border border-white/10 px-2.5 py-1 text-xs text-slate-300 hover:border-white/25 hover:text-white">
                            Trennen
                        </button>
                        <button type="button" data-command-log-end class="hidden rounded-lg border border-red-400/30 px-2.5 py-1 text-xs text-red-200 hover:border-red-300/60 hover:text-white">
                            Beenden
                        </button>
                        <button type="button" data-command-log-clear class="rounded-lg border border-white/10 px-2.5 py-1 text-xs text-slate-300 hover:border-white/25 hover:text-white">
                            Clear
                        </button>
                        <button type="button" data-command-log-toggle class="rounded-lg border border-white/10 px-2.5 py-1 text-xs text-slate-300 hover:border-white/25 hover:text-white" aria-expanded="true">
                            Minimieren
                        </button>
                    </div>
                </div>
                <div data-command-log-body class="flex flex-col overflow-hidden p-2">
                    <div data-command-log-output class="flex-1 overflow-hidden rounded-xl bg-[#0b0f14]"></div>
                    <div class="mt-1.5 flex items-center gap-2 rounded-xl bg-[#0b0f14] px-3 py-2">
                        <span data-command-log-prompt class="shrink-0 text-sm text-green-400">{{ $floatingCommandServer->username }}@{{ $floatingCommandServer->host }} : </span>
                        <input data-command-log-input type="text" class="min-w-0 flex-1 bg-transparent text-sm text-[#d6deeb] outline-none placeholder:text-slate-500" placeholder="Nicht verbunden..." disabled />
                    </div>
                </div>
                <div
                    data-command-log-resize
                    class="absolute left-0 top-0 z-50 cursor-nwse-resize p-0.5 text-white/30 hover:text-white/60"
                    style="touch-action: none"
                    aria-label="Größe ändern"
                >
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 0v14H0"/>
                        <path d="M10 0v10H0" opacity="0.5"/>
                        <path d="M6 0v6H0" opacity="0.25"/>
                    </svg>
                </div>
            </aside>
        @endif

        @stack('scripts')
    </body>
</html>
