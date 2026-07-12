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
                    <p class="text-sm font-medium text-white">Shell ausführen</p>
                    <p class="mt-1 text-xs text-[#A1A09A]">Freie Befehle werden direkt auf dem Server-Agent ausgeführt und im Audit protokolliert.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-xs text-[#A1A09A]">
                    <label class="flex items-center gap-2">
                        <input id="terminal-sudo" type="checkbox" checked class="rounded border-white/20 bg-transparent">
                        sudo
                    </label>
                    <label class="flex items-center gap-2">
                        Timeout
                        <input id="terminal-timeout" type="number" min="1" max="3600" value="120" class="w-20 rounded-lg border border-white/15 bg-white/5 px-2 py-1 text-white">
                    </label>
                    <button type="button" onclick="clearTerminal()" class="rounded-lg border border-white/15 px-3 py-1.5 text-xs text-white hover:border-white/30">
                        Leeren
                    </button>
                </div>
            </div>

            <pre id="terminal-output" class="mt-4 min-h-[420px] max-h-[65vh] overflow-auto whitespace-pre-wrap rounded-xl bg-black p-4 font-mono text-sm leading-6 text-[#d8f3dc]"></pre>

            <form id="terminal-form" class="mt-4 flex flex-col gap-3 sm:flex-row" autocomplete="off">
                <label for="terminal-command" class="sr-only">Befehl</label>
                <div class="flex flex-1 items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-3 py-2 font-mono text-sm">
                    <span class="text-[#f53003] dark:text-[#FF4433]">$</span>
                    <input id="terminal-command" name="command" type="text" class="w-full border-0 bg-transparent p-0 text-white outline-none placeholder:text-[#706f6c] focus:ring-0" placeholder="z.B. whoami && pwd" autofocus>
                </div>
                <button id="terminal-submit" type="submit" class="rounded-xl bg-[#EDEDEC] px-5 py-2 text-sm font-medium text-[#1C1C1A] hover:bg-[#dbdbd8] disabled:cursor-wait disabled:opacity-60">
                    Ausführen
                </button>
            </form>
        </div>
    </section>

    @push('scripts')
    <script>
    const terminalForm = document.getElementById('terminal-form');
    const terminalCommand = document.getElementById('terminal-command');
    const terminalOutput = document.getElementById('terminal-output');
    const terminalSubmit = document.getElementById('terminal-submit');
    const terminalHistoryKey = 'smuze:server:{{ $server->id }}:terminal-history';
    let terminalHistory = loadTerminalHistory();
    let terminalHistoryIndex = terminalHistory.length;

    function loadTerminalHistory() {
        try {
            const history = JSON.parse(localStorage.getItem(terminalHistoryKey) || '[]');

            return Array.isArray(history) ? history : [];
        } catch {
            return [];
        }
    }

    function appendTerminal(text, className = '') {
        const span = document.createElement('span');
        if (className) span.className = className;
        span.textContent = text;
        terminalOutput.appendChild(span);
        terminalOutput.scrollTop = terminalOutput.scrollHeight;
    }

    function clearTerminal() {
        terminalOutput.textContent = '';
        appendTerminal('Bereit. Befehle werden frei auf dem Zielserver ausgeführt.\n', 'text-[#A1A09A]');
    }

    function rememberCommand(command) {
        terminalHistory = terminalHistory.filter(item => item !== command);
        terminalHistory.push(command);
        terminalHistory = terminalHistory.slice(-50);
        terminalHistoryIndex = terminalHistory.length;
        localStorage.setItem(terminalHistoryKey, JSON.stringify(terminalHistory));
    }

    terminalCommand.addEventListener('keydown', event => {
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            terminalHistoryIndex = Math.max(0, terminalHistoryIndex - 1);
            terminalCommand.value = terminalHistory[terminalHistoryIndex] || terminalCommand.value;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            terminalHistoryIndex = Math.min(terminalHistory.length, terminalHistoryIndex + 1);
            terminalCommand.value = terminalHistory[terminalHistoryIndex] || '';
        }
    });

    terminalForm.addEventListener('submit', async event => {
        event.preventDefault();

        const command = terminalCommand.value.trim();
        if (!command) return;

        rememberCommand(command);
        terminalCommand.value = '';
        terminalSubmit.disabled = true;
        terminalSubmit.textContent = 'Läuft...';

        appendTerminal(`$ ${command}\n`, 'text-white');

        try {
            const response = await fetch('{{ route('server.agent.execute.stream', $server) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    Accept: 'application/x-ndjson',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    command,
                    timeout: Number(document.getElementById('terminal-timeout').value || 120),
                    use_sudo: document.getElementById('terminal-sudo').checked,
                }),
            });

            if (!response.ok) {
                appendTerminal(`Request fehlgeschlagen (${response.status}).\n`, 'text-red-300');
                return;
            }

            if (!response.body) {
                appendTerminal('Live-Stream wird vom Browser nicht unterstützt.\n', 'text-red-300');
                return;
            }

            await readTerminalStream(response.body);
        } catch (error) {
            appendTerminal(`Fehler: ${error.message}\n`, 'text-red-300');
        } finally {
            terminalSubmit.disabled = false;
            terminalSubmit.textContent = 'Ausführen';
            terminalCommand.focus();
        }
    });

    async function readTerminalStream(body) {
        const reader = body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { value, done } = await reader.read();
            buffer += decoder.decode(value || new Uint8Array(), { stream: !done });

            const lines = buffer.split('\n');
            buffer = lines.pop() || '';

            for (const line of lines) {
                handleTerminalChunk(line);
            }

            if (done) break;
        }

        if (buffer.trim() !== '') {
            handleTerminalChunk(buffer);
        }
    }

    function handleTerminalChunk(line) {
        if (line.trim() === '') return;

        const chunk = JSON.parse(line);

        if (chunk.stream === 'stdout') appendTerminal(chunk.data || '');
        if (chunk.stream === 'stderr') appendTerminal(chunk.data || '', 'text-red-300');
        if (chunk.error) appendTerminal(`${chunk.error}\n`, 'text-red-300');

        if (chunk.done) {
            const exitCode = chunk.exit_code ?? -1;
            appendTerminal(`\n[exit ${exitCode}]\n`, exitCode === 0 && !chunk.error ? 'text-green-300' : 'text-red-300');
        }
    }

    clearTerminal();
    </script>
    @endpush
</x-layouts.app>
