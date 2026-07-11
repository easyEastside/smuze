<x-layouts.app title="Terminal: {{ $server->name }}">
    <div id="websocket-status-bar" class="fixed inset-x-0 top-0 z-50 h-1 bg-red-600 transition-colors duration-200" title="WebSocket getrennt"></div>
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">SSH-Terminal</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $server->name }}</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Interaktive PTY-Session als {{ $server->username }}<span>@</span>{{ $server->host }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span id="terminal-status" class="rounded-full bg-[#19140012] px-3 py-1 text-xs text-[#706f6c] dark:bg-[#fffaed12] dark:text-[#A1A09A]">Nicht verbunden</span>
                    <button type="button" id="terminal-reconnect" onclick="reconnectTerminal()" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Neu verbinden
                    </button>
                    <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        System
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-4 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-6">
            <div id="terminal-container" class="h-[620px] overflow-hidden rounded-xl border border-[#19140020] bg-[#0b0f14] p-2 dark:border-[#3E3E3A]"></div>
            <p id="terminal-error" class="mt-3 hidden rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200"></p>
        </div>
    </section>

    @push('scripts')
    <script>
    function wsInit() {
        SmuzeServerSocket.onStatus((status) => {
            const bar = document.getElementById('websocket-status-bar');
            if (!bar) return;
            const connected = status === 'connected';
            bar.classList.toggle('bg-green-500', connected);
            bar.classList.toggle('bg-red-600', !connected);
            bar.title = connected ? 'WebSocket verbunden' : 'WebSocket getrennt';
        });

        SmuzeServerSocket.connect({{ $server->id }}, '{{ route('server.socket.session', $server) }}', '{{ csrf_token() }}');
    }

    if (typeof SmuzeServerSocket !== 'undefined') {
        wsInit();
    } else {
        document.addEventListener('DOMContentLoaded', wsInit);
    }

    let terminalClient = null;

    function setTerminalStatus(label, state) {
        const status = document.getElementById('terminal-status');
        if (!status) return;

        const classes = {
            idle: 'rounded-full bg-[#19140012] px-3 py-1 text-xs text-[#706f6c] dark:bg-[#fffaed12] dark:text-[#A1A09A]',
            connected: 'rounded-full bg-green-100 px-3 py-1 text-xs text-green-800 dark:bg-green-900 dark:text-green-200',
            connecting: 'rounded-full bg-yellow-100 px-3 py-1 text-xs text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            error: 'rounded-full bg-red-100 px-3 py-1 text-xs text-red-800 dark:bg-red-900 dark:text-red-200',
        };

        status.className = classes[state] || classes.idle;
        status.textContent = label;
    }

    function showTerminalError(message) {
        const error = document.getElementById('terminal-error');
        if (!error) return;

        error.textContent = message;
        error.classList.remove('hidden');
    }

    function hideTerminalError() {
        const error = document.getElementById('terminal-error');
        if (error) error.classList.add('hidden');
    }

    function createTerminalClient() {
        const container = document.getElementById('terminal-container');
        if (!container || !window.SmuzeTerminal) {
            showTerminalError('Terminal-Komponenten konnten nicht geladen werden. Bitte npm run build oder npm run dev ausführen.');
            return null;
        }

        const term = new window.SmuzeTerminal.Terminal({
            cursorBlink: true,
            convertEol: true,
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
            fontSize: 13,
            theme: {
                background: '#0b0f14',
                foreground: '#d6deeb',
                cursor: '#f53003',
                selectionBackground: '#334155',
            },
        });
        const fitAddon = new window.SmuzeTerminal.FitAddon();
        term.loadAddon(fitAddon);
        term.open(container);
        fitAddon.fit();

        let socket = null;
        const resizeObserver = new ResizeObserver(() => {
            fitAddon.fit();
            if (socket && socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify({ channel: 'terminal', type: 'resize', cols: term.cols, rows: term.rows }));
            }
        });

        resizeObserver.observe(container);

        term.onData(data => {
            if (socket && socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify({ channel: 'terminal', type: 'input', data }));
            }
        });

        return {
            connect() {
                hideTerminalError();
                setTerminalStatus('Verbinde...', 'connecting');
                term.reset();
                term.writeln('Starte SSH-Terminal...');

                fetch('{{ route('server.socket.session', $server) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json' },
                })
                    .then(r => {
                        if (!r.ok) throw new Error('Terminal-Session konnte nicht erstellt werden.');
                        return r.json();
                    })
                    .then(data => {
                        const url = new URL(data.websocket_url);
                        url.searchParams.set('token', data.token);
                        url.searchParams.set('cols', term.cols);
                        url.searchParams.set('rows', term.rows);

                        socket = new WebSocket(url.toString());
                        socket.addEventListener('open', () => {
                            setTerminalStatus('Verbunden', 'connected');
                            term.focus();
                            socket.send(JSON.stringify({ channel: 'terminal', type: 'open', cols: term.cols, rows: term.rows }));
                        });
                        socket.addEventListener('message', event => {
                            const payload = JSON.parse(event.data);
                            if (payload.channel === 'terminal' && payload.type === 'output') term.write(payload.data);
                            if (payload.type === 'error') {
                                setTerminalStatus('Fehler', 'error');
                                showTerminalError(payload.message || 'Terminal-Fehler.');
                                term.writeln('\r\n' + (payload.message || 'Terminal-Fehler.'));
                            }
                            if (payload.channel === 'terminal' && payload.type === 'exit') {
                                setTerminalStatus('Beendet', 'idle');
                                term.writeln(`\r\nSession beendet (Exit ${payload.exit_code ?? 'unbekannt'}).`);
                            }
                        });
                        socket.addEventListener('close', () => setTerminalStatus('Getrennt', 'idle'));
                        socket.addEventListener('error', () => {
                            setTerminalStatus('Fehler', 'error');
                            showTerminalError('WebSocket-Verbindung fehlgeschlagen. Läuft npm run terminal oder composer run dev?');
                        });
                    })
                    .catch(err => {
                        setTerminalStatus('Fehler', 'error');
                        showTerminalError('Fehler: ' + err.message);
                        term.writeln('\r\nFehler: ' + err.message);
                    });
            },
            disconnect() {
                if (socket) socket.close();
                socket = null;
            },
            dispose() {
                this.disconnect();
                resizeObserver.disconnect();
                term.dispose();
            },
        };
    }

    function reconnectTerminal() {
        terminalClient?.dispose();
        terminalClient = createTerminalClient();
        terminalClient?.connect();
    }

    reconnectTerminal();
    </script>
    @endpush
</x-layouts.app>
