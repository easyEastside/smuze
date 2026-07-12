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
                data-debug-enabled="{{ auth()->user()->write_debug_logs ? '1' : '0' }}"
                data-session-endpoint="{{ route('server.socket.session', $floatingCommandServer) }}"
                data-csrf-token="{{ csrf_token() }}"
                class="fixed bottom-4 right-4 z-40 hidden w-[calc(100vw-2rem)] max-w-xl overflow-hidden rounded-2xl border border-[#19140020] bg-[#0b0f14] text-[#d6deeb] shadow-2xl dark:border-[#3E3E3A] sm:bottom-6 sm:right-6 sm:w-[34rem]"
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
                        <button type="button" data-command-log-clear class="rounded-lg border border-white/10 px-2.5 py-1 text-xs text-slate-300 hover:border-white/25 hover:text-white">
                            Clear
                        </button>
                        <button type="button" data-command-log-toggle class="rounded-lg border border-white/10 px-2.5 py-1 text-xs text-slate-300 hover:border-white/25 hover:text-white" aria-expanded="true">
                            Minimieren
                        </button>
                    </div>
                </div>
                <div data-command-log-body class="h-80 overflow-hidden p-2">
                    <div data-command-log-output class="h-full overflow-hidden rounded-xl bg-[#0b0f14]"></div>
                </div>
            </aside>
        @endif

        @stack('scripts')
    </body>
</html>
