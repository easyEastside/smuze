<x-layouts.app title="Logs: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Logs</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Log-Dateien</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} - {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="btn-refresh" onclick="fetchLog()" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                        Aktualisieren
                    </button>
                    <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        System
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[280px_1fr]">
            <aside class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Quelle</p>
                    <div class="mt-4 space-y-1 text-sm">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">System</p>
                        <button type="button" data-source="syslog" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">syslog</button>
                        <button type="button" data-source="auth" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">auth.log</button>
                        <button type="button" data-source="kern" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">kern.log</button>
                        <button type="button" data-source="dmesg" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">dmesg</button>
                    </div>
                    <div class="mt-4 space-y-1 text-sm">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">Webserver</p>
                        <button type="button" data-source="nginx_access" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">Nginx Access</button>
                        <button type="button" data-source="nginx_error" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">Nginx Error</button>
                        <button type="button" data-source="apache_access" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">Apache Access</button>
                        <button type="button" data-source="apache_error" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">Apache Error</button>
                    </div>
                    <div class="mt-4 space-y-1 text-sm">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">Datenbank</p>
                        <button type="button" data-source="mysql_error" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">MySQL Error</button>
                        <button type="button" data-source="mysql_slow" class="log-source-btn block w-full rounded-md px-3 py-1.5 text-left hover:bg-[#f5f5f4] dark:hover:bg-[#2b2b28">MySQL Slow</button>
                    </div>
                    <div class="mt-4 space-y-1 text-sm">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-wide text-[#706f6c] dark:text-[#A1A09A]">Benutzerdefiniert</p>
                        <input type="text" id="custom-path" placeholder="/pfad/zur/datei.log" class="mt-2 w-full rounded-lg border border-[#19140035] px-3 py-1.5 text-sm dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]">
                        <button type="button" onclick="setCustomSource()" class="mt-2 w-full rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            Öffnen
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Optionen</p>
                    <div class="mt-4 space-y-3 text-sm">
                        <div>
                            <label class="text-[#706f6c] dark:text-[#A1A09A]">Zeilen</label>
                            <select id="log-lines" class="mt-1 w-full rounded-lg border border-[#19140035] px-3 py-1.5 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]">
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="200" selected>200</option>
                                <option value="500">500</option>
                                <option value="1000">1000</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[#706f6c] dark:text-[#A1A09A]">Filter</label>
                            <input type="text" id="log-filter" placeholder="grep-Filter..." class="mt-1 w-full rounded-lg border border-[#19140035] px-3 py-1.5 dark:border-[#3E3E3A] dark:bg-[#161615] dark:text-[#EDEDEC]">
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="log-follow" class="rounded border-[#19140035] dark:border-[#3E3E3A]">
                            <label for="log-follow" class="text-[#706f6c] dark:text-[#A1A09A]">Live-Follow (alle 5s)</label>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Ausgabe</p>
                        <p id="log-source-label" class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A">Bitte Log-Quelle auswählen</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="copyLog()" class="rounded-lg border border-[#19140035] px-3 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                            Kopieren
                        </button>
                        <span id="log-line-count" class="text-xs text-[#706f6c] dark:text-[#A1A09A]"></span>
                    </div>
                </div>
                <pre id="log-output" class="mt-4 max-h-[70vh] overflow-auto rounded-xl bg-[#19140008] p-4 font-mono text-xs leading-6 text-[#1b1b18] dark:bg-[#fffaed08] dark:text-[#EDEDEC] whitespace-pre-wrap">-</pre>
                <div id="log-loading" class="mt-4 hidden text-sm text-[#706f6c] dark:text-[#A1A09A]">Lade Log-Daten...</div>
                <div id="log-error" class="mt-4 hidden rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200"></div>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    window.reportError = function (message, source, details = {}) {
        const btn = document.createElement('button');
        btn.className = 'ml-2 rounded-lg border border-[#19140035] px-2 py-0.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]';
        btn.textContent = 'Fehler melden';

        btn.addEventListener('click', async () => {
            btn.disabled = true;
            btn.textContent = '...';
            try {
                const res = await fetch('{{ route('errors.report') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message, source, details }),
                });
                const data = await res.json();
                btn.textContent = data.success ? '✓' : '✗';
            } catch {
                btn.textContent = '✗';
            }
        });

        return btn;
    };

    let currentSource = 'syslog';
    let followTimer = null;

    document.querySelectorAll('.log-source-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.log-source-btn').forEach(b => {
                b.classList.remove('bg-[#f5f5f4]', 'dark:bg-[#2b2b28]', 'font-medium');
            });
            this.classList.add('bg-[#f5f5f4]', 'dark:bg-[#2b2b28]', 'font-medium');
            currentSource = this.dataset.source;
            document.getElementById('log-source-label').textContent = this.textContent.trim();
            stopFollow();
            fetchLog();
        });
    });

    document.querySelector('.log-source-btn')?.classList.add('bg-[#f5f5f4]', 'dark:bg-[#2b2b28]', 'font-medium');
    document.getElementById('log-source-label').textContent = 'syslog';

    function setCustomSource() {
        const path = document.getElementById('custom-path').value.trim();
        if (!path) return;
        currentSource = path;
        document.getElementById('log-source-label').textContent = path;
        document.querySelectorAll('.log-source-btn').forEach(b => {
            b.classList.remove('bg-[#f5f5f4]', 'dark:bg-[#2b2b28]', 'font-medium');
        });
        stopFollow();
        fetchLog();
    }

    function fetchLog() {
        const lines = document.getElementById('log-lines').value;
        const filter = document.getElementById('log-filter').value.trim();

        const output = document.getElementById('log-output');
        const loading = document.getElementById('log-loading');
        const error = document.getElementById('log-error');

        loading.classList.remove('hidden');
        error.classList.add('hidden');
        output.textContent = '';

        fetch('{{ route('server.logs.fetch', $server) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ source: currentSource, lines, filter }),
        })
        .then(r => r.json())
        .then(data => {
            loading.classList.add('hidden');

            if (data.error) {
                error.innerHTML = '';
                error.appendChild(document.createTextNode(data.error));
                error.appendChild(window.reportError(data.error, 'logs.fetch'));
                error.classList.remove('hidden');
                return;
            }

            output.textContent = data.lines.join('\n');
            document.getElementById('log-line-count').textContent = data.total + ' Zeilen';
        })
        .catch(err => {
            loading.classList.add('hidden');
            const msg = 'Fehler: ' + err.message;
            error.innerHTML = '';
            error.appendChild(document.createTextNode(msg));
            error.appendChild(window.reportError(msg, 'logs.fetch'));
            error.classList.remove('hidden');
        });
    }

    function copyLog() {
        const text = document.getElementById('log-output').textContent;
        if (!text || text === '-') return;
        navigator.clipboard.writeText(text).then(() => {
            const btn = event.target;
            const orig = btn.textContent;
            btn.textContent = 'Kopiert!';
            setTimeout(() => btn.textContent = orig, 1500);
        });
    }

    function stopFollow() {
        if (followTimer) {
            clearInterval(followTimer);
            followTimer = null;
        }
        document.getElementById('log-follow').checked = false;
    }

    document.getElementById('log-follow').addEventListener('change', function () {
        if (this.checked) {
            fetchLog();
            followTimer = setInterval(fetchLog, 5000);
        } else {
            clearInterval(followTimer);
            followTimer = null;
        }
    });

    document.getElementById('log-filter').addEventListener('keydown', e => {
        if (e.key === 'Enter') fetchLog();
    });

    document.getElementById('custom-path').addEventListener('keydown', e => {
        if (e.key === 'Enter') setCustomSource();
    });

    fetchLog();
    </script>
    @endpush
</x-layouts.app>
