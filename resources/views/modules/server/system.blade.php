<x-layouts.app title="System: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server System</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $server->name }}</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="btn-test-connection" onclick="testConnection()" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Verbindung testen
                    </button>
                    <a href="{{ route('server.edit', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Bearbeiten
                    </a>
                    <a href="{{ route('server.terminal', $server) }}" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                        Terminal
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_280px]">
            <div class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">System-Aktionen</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" onclick="systemAction('system.apt_update', 'apt update ausführen?')" class="rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            APT Update
                        </button>
                        <button type="button" onclick="systemAction('system.apt_upgrade', 'System-Upgrade ausführen? Dies kann einige Minuten dauern.')" class="rounded-lg bg-[#f59e0b] px-4 py-2 text-sm font-medium text-white hover:bg-[#d97706]">
                            APT Upgrade
                        </button>
                        <button type="button" onclick="systemAction('system.reboot', 'Server neu starten?')" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                            Neustart
                        </button>
                        <button type="button" onclick="systemAction('system.shutdown', 'Server herunterfahren?')" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                            Herunterfahren
                        </button>
                    </div>
                    <div id="action-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Systeminformationen</p>
                        <button type="button" onclick="fetchMetrics()" class="rounded-lg border border-[#19140035] px-3 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                            Aktualisieren
                        </button>
                    </div>

                    <div id="system-loading" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Verbinde zum Server...
                    </div>

                    <div id="system-content" class="mt-6 hidden">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Hostname</p>
                                <p id="sys-hostname" class="mt-1 text-sm font-medium">-</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Betriebssystem</p>
                                <p id="sys-os" class="mt-1 text-sm font-medium">-</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Uptime</p>
                                <p id="sys-uptime" class="mt-1 text-sm font-medium">-</p>
                            </div>
                            <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                                <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Load</p>
                                <p id="sys-load" class="mt-1 text-sm font-medium">-</p>
                            </div>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">CPU</span>
                                    <span id="cpu-text" class="text-[#706f6c] dark:text-[#A1A09A]">-</span>
                                </div>
                                <div id="cpu-bar" class="mt-1 h-3 rounded-full bg-[#19140020] dark:bg-[#3E3E3A]">
                                    <div class="h-full rounded-full transition-all" style="width: 0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">RAM</span>
                                    <span id="ram-text" class="text-[#706f6c] dark:text-[#A1A09A]">-</span>
                                </div>
                                <div id="ram-bar" class="mt-1 h-3 rounded-full bg-[#19140020] dark:bg-[#3E3E3A]">
                                    <div class="h-full rounded-full transition-all" style="width: 0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">Disk</span>
                                    <span id="disk-text" class="text-[#706f6c] dark:text-[#A1A09A]">-</span>
                                </div>
                                <div id="disk-bar" class="mt-1 h-3 rounded-full bg-[#19140020] dark:bg-[#3E3E3A]">
                                    <div class="h-full rounded-full transition-all" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div id="system-error" class="mt-4 hidden rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200"></div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Verlauf</p>
                        <div class="flex gap-1 text-xs" id="chart-range-buttons">
                            <button type="button" data-range="1h" class="rounded-md border border-[#19140035] px-2 py-1 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">1h</button>
                            <button type="button" data-range="24h" class="rounded-md border border-[#19140035] bg-[#1b1b18] px-2 py-1 text-white hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-[#EDEDEC] dark:text-[#1C1C1A]">24h</button>
                            <button type="button" data-range="7d" class="rounded-md border border-[#19140035] px-2 py-1 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">7d</button>
                        </div>
                    </div>
                    <div class="mt-4">
                        <canvas id="metrics-chart"></canvas>
                    </div>
                    <div id="chart-empty" class="mt-4 hidden text-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        Noch keine Verlaufsdaten vorhanden. Sammle Daten...
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Agent-Audit</p>
                            <p class="mt-1 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">Zuletzt ausgeführte Agent-Kommandos für diesen Server.</p>
                        </div>
                    </div>

                    @if ($agentCommands->isEmpty())
                        <p class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">Noch keine Agent-Kommandos protokolliert.</p>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-[#19140020] text-left text-xs font-medium text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                        <th class="py-2 pr-4 font-medium">Zeit</th>
                                        <th class="py-2 pr-4 font-medium">Status</th>
                                        <th class="py-2 pr-4 font-medium">Quelle</th>
                                        <th class="py-2 pr-4 font-medium">Benutzer</th>
                                        <th class="py-2 pr-4 font-medium">Kommando</th>
                                        <th class="py-2 text-right font-medium">Dauer</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#19140020] dark:divide-[#3E3E3A]">
                                    @foreach ($agentCommands as $command)
                                        <tr>
                                            <td class="py-3 pr-4 whitespace-nowrap text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $command->created_at->diffForHumans() }}</td>
                                            <td class="py-3 pr-4">
                                                @if ($command->success)
                                                    <span class="rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-300">OK</span>
                                                @else
                                                    <span class="rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-950 dark:text-red-300">Fehler {{ $command->exit_code }}</span>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4 text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $command->source }}</td>
                                            <td class="py-3 pr-4 text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $command->user?->name ?? 'System' }}</td>
                                            <td class="max-w-[320px] py-3 pr-4 font-mono text-xs">
                                                <span class="block truncate" title="{{ $command->action ?? $command->command }}">{{ $command->action ?? $command->command }}</span>
                                            </td>
                                            <td class="py-3 text-right text-xs text-[#706f6c] dark:text-[#A1A09A]">{{ $command->duration_ms ?? 0 }} ms</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $agentCommands->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <aside class="space-y-6">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server Details</p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-[#706f6c] dark:text-[#A1A09A]">Status</dt>
                            <dd id="conn-status" class="font-medium text-[#706f6c] dark:text-[#A1A09A]">Unbekannt</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#706f6c] dark:text-[#A1A09A]">Aktualisierung</dt>
                            <dd id="update-mode" class="font-medium text-[#706f6c] dark:text-[#A1A09A]">-</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#706f6c] dark:text-[#A1A09A]">Agent-Endpunkt</dt>
                            <dd class="font-medium">{{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#706f6c] dark:text-[#A1A09A]">Agent</dt>
                            <dd id="agent-status-detail" class="font-medium">{{ $server->agent_enabled ? $server->agent_status : 'deaktiviert' }}</dd>
                        </div>
                        @if ($server->agent_enabled)
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Agent-Version</dt>
                                <dd id="agent-version" class="font-medium">{{ $server->agent_version ?? '-' }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-[#706f6c] dark:text-[#A1A09A]">Agent zuletzt</dt>
                            <dd id="agent-last-seen" class="font-medium">{{ $server->agent_last_seen_at ? $server->agent_last_seen_at->diffForHumans() : '-' }}</dd>
                        </div>
                        @if ($server->notes)
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Notizen</dt>
                                <dd class="max-w-[160px] text-right font-medium">{{ $server->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>



                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Agent Setup</p>
                    <p class="mt-2 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">Generiere ein Install-Kommando und führe es manuell auf dem Server aus. Der Token wird nur direkt nach der Generierung angezeigt.</p>
                    <div id="agent-update-section" class="mt-2 hidden">
                        <span class="rounded-lg bg-yellow-50 px-3 py-2 text-center text-xs font-medium text-yellow-800 dark:bg-yellow-950 dark:text-yellow-200">
                            Update verfügbar: <span id="agent-update-version"></span>
                        </span>
                        <button type="button" onclick="updateAgent()" class="mt-2 w-full rounded-lg bg-[#f59e0b] px-3 py-2 text-sm font-medium text-white hover:bg-[#d97706]">
                            Agent aktualisieren
                        </button>
                    </div>
                    <div class="mt-4 flex flex-col gap-2 text-sm">
                        @if ($server->agent_enabled && $server->agent_status === 'connected')
                            <span class="rounded-lg bg-green-50 px-3 py-2 text-center text-xs font-medium text-green-800 dark:bg-green-950 dark:text-green-200">
                                Agent verbunden
                            </span>
                        @else
                            <button type="button" onclick="installAgent()" class="rounded-lg bg-[#f53003] px-3 py-2 font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                                Install-Kommando generieren
                            </button>
                            <button type="button" onclick="rotateAgentToken()" class="rounded-lg bg-[#1b1b18] px-3 py-2 font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                Token rotieren
                            </button>
                        @endif
                        @if ($server->agent_enabled)
                            <button type="button" onclick="disableAgent()" class="rounded-lg border border-[#19140035] px-3 py-2 font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                Agent deaktivieren
                            </button>
                        @endif
                    </div>
                    <div id="agent-token-box" class="mt-4 hidden rounded-xl border border-[#19140020] bg-[#19140008] p-3 dark:border-[#3E3E3A] dark:bg-[#fffaed08]">
                        <p class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Agent-Konfiguration</p>
                        <pre id="agent-token-output" class="mt-2 overflow-x-auto whitespace-pre-wrap text-xs leading-5 text-[#1b1b18] dark:text-[#EDEDEC]"></pre>
                    </div>
                </div>

            </aside>
        </div>
    </section>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
    const host = '{{ $server->host }}';
    const port = '{{ $server->agent_port ?? config('agent.push_port', 9300) }}';
    const metricsRefreshMs = 15000;
    let isFetchingMetrics = false;
    let hasLoadedMetrics = false;
    let metricsRefreshTimer = null;

    function formatBytes(mb) {
        if (mb >= 1024) return (mb / 1024).toFixed(1) + ' GB';
        return mb + ' MB';
    }

    function getBarColor(pct) {
        if (pct >= 90) return '#f53003';
        if (pct >= 70) return '#f59e0b';
        return '#22c55e';
    }

    function updateUsage(barId, textId, pct, used, total, showSize = true) {
        const bar = document.getElementById(barId);
        const text = document.getElementById(textId);
        if (!bar || !text) return;

        const fill = bar.querySelector('div');
        fill.style.width = pct + '%';
        fill.style.backgroundColor = getBarColor(pct);
        text.textContent = showSize ? `${pct}% | ${formatBytes(used)} / ${formatBytes(total)}` : `${pct}%`;
    }

    function setConnectionStatus(label, className) {
        const status = document.getElementById('conn-status');
        status.textContent = label;
        status.className = className;
    }

    function renderSystemData(data) {
        document.getElementById('sys-hostname').textContent = data.hostname || '-';
        document.getElementById('sys-os').textContent = data.os || '-';
        document.getElementById('sys-uptime').textContent = data.uptime || '-';
        document.getElementById('sys-load').textContent = data.load || '-';

        if (data.cpu_percent !== null && data.cpu_percent !== undefined) {
            updateUsage('cpu-bar', 'cpu-text', data.cpu_percent, data.cpu_percent, 100, false);
        } else {
            document.getElementById('cpu-text').textContent = '-';
        }

        if (data.ram_percent !== null && data.ram_percent !== undefined && data.ram_total_mb !== null && data.ram_total_mb !== undefined) {
            updateUsage('ram-bar', 'ram-text', data.ram_percent, data.ram_used_mb, data.ram_total_mb);
        } else {
            document.getElementById('ram-text').textContent = '-';
        }

        if (data.disk_percent !== null && data.disk_percent !== undefined && data.disk_total_mb !== null && data.disk_total_mb !== undefined) {
            updateUsage('disk-bar', 'disk-text', data.disk_percent, data.disk_used_mb, data.disk_total_mb);
        } else {
            document.getElementById('disk-text').textContent = '-';
        }

        setConnectionStatus('Online', 'font-medium text-green-500');

        const updateMode = document.getElementById('update-mode');
        updateMode.textContent = 'alle 15 Sekunden';
        updateMode.title = 'Zuletzt aktualisiert: ' + new Date().toLocaleTimeString('de-DE');

        const statusDetail = document.getElementById('agent-status-detail');
        if (statusDetail) statusDetail.textContent = 'connected';

        const lastSeen = document.getElementById('agent-last-seen');
        if (lastSeen) lastSeen.textContent = 'gerade eben';
    }

    function fetchMetrics() {
        if (isFetchingMetrics) return;

        const loading = document.getElementById('system-loading');
        const content = document.getElementById('system-content');
        const error = document.getElementById('system-error');
        const showInitialLoading = !hasLoadedMetrics;

        isFetchingMetrics = true;

        if (showInitialLoading) {
            loading.classList.remove('hidden');
            content.classList.add('hidden');
        }

        error.classList.add('hidden');

        fetch('{{ route('server.agent.metrics', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');

                if (data.error) {
                    error.innerHTML = '';
                    error.appendChild(document.createTextNode(data.error));
                    error.appendChild(window.reportError(data.error, 'system.metrics'));
                    error.classList.remove('hidden');

                    const statusDetail = document.getElementById('agent-status-detail');
                    if (statusDetail) statusDetail.textContent = 'disconnected';

                    return;
                }

                renderSystemData(data);
                hasLoadedMetrics = true;
                content.classList.remove('hidden');
                loadChart();
            })
            .catch(err => {
                loading.classList.add('hidden');
                const msg = 'Verbindungsfehler: ' + err.message;
                error.innerHTML = '';
                error.appendChild(document.createTextNode(msg));
                error.appendChild(window.reportError(msg, 'system.metrics'));
                error.classList.remove('hidden');
                setConnectionStatus('Offline', 'font-medium text-red-500');

                const statusDetail = document.getElementById('agent-status-detail');
                if (statusDetail) statusDetail.textContent = 'disconnected';
            })
            .finally(() => {
                isFetchingMetrics = false;
            });
    }

    function testConnection() {
        const btn = document.getElementById('btn-test-connection');
        btn.disabled = true;
        btn.textContent = 'Teste...';

        fetch('{{ route('server.agent.health', $server) }}')
            .then(r => r.json())
            .then(data => {
                const ok = data.status === 'ok';
                setConnectionStatus(ok ? 'Online' : 'Offline', ok ? 'font-medium text-green-500' : 'font-medium text-red-500');
                btn.textContent = 'Verbindung testen';
                btn.disabled = false;
            })
            .catch(() => {
                setConnectionStatus('Offline', 'font-medium text-red-500');
                btn.textContent = 'Verbindung testen';
                btn.disabled = false;
            });
    }

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

    function showActionResult(success, message, source) {
        const result = document.getElementById('action-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        result.innerHTML = '';
        result.appendChild(document.createTextNode(message));
        if (!success && source) {
            result.appendChild(window.reportError(message, source));
        }
        result.classList.remove('hidden');
    }

    function systemAction(action, confirmMsg) {
        if (!confirm(confirmMsg)) return;

        const result = document.getElementById('action-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm';
        result.textContent = 'Führe Action aus...';
        result.classList.remove('hidden');

        fetch('{{ route('server.agent.action', $server) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ action, payload: {} }),
        })
        .then(r => r.json())
        .then(data => {
            const ok = data.success && data.exit_code === 0;
            const output = [data.stdout || '', data.stderr || ''].join('');
            result.className = 'mt-3 rounded-xl p-3 text-sm ' + (ok ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
            result.textContent = ok ? (output || 'Action ausgeführt.') : (data.error || output || 'Fehler bei Ausführung.');
        })
        .catch(err => {
            const msg = 'Fehler: ' + err.message;
            result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            result.innerHTML = '';
            result.appendChild(document.createTextNode(msg));
            result.appendChild(window.reportError(msg, 'system.action'));
        });
    }

    async function installAgent() {
        if (!confirm('Neues Install-Kommando generieren? Der bisherige Agent-Token wird ungültig.')) return;

        const result = document.getElementById('action-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm';
            result.textContent = 'Generiere Install-Kommando...';
        result.classList.remove('hidden');

        try {
            const res = await fetch('{{ route('server.agent.install', $server) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();

            result.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success
                ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200'
                : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
            result.textContent = data.message;

            if (data.success) {
                document.getElementById('agent-token-output').textContent = data.install_command;
                document.getElementById('agent-token-box').classList.remove('hidden');
            }
        } catch (err) {
            const msg = 'Fehler: ' + err.message;
            result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            result.innerHTML = '';
            result.appendChild(document.createTextNode(msg));
            result.appendChild(window.reportError(msg, 'agent.install'));
        }
    }

    async function rotateAgentToken() {
        if (!confirm('Agent-Token neu generieren? Der bisherige Agent muss danach neu konfiguriert werden.')) return;

        try {
            const res = await fetch('{{ route('server.agent.token', $server) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                showActionResult(false, data.message || 'Agent-Token konnte nicht generiert werden.', 'agent.token');
                return;
            }

            document.getElementById('agent-token-output').textContent = data.install_command || [
                'SMUZE_APP_URL=' + JSON.stringify(data.app_url),
                'SMUZE_SERVER_ID=' + JSON.stringify(String(data.server_id)),
                'SMUZE_AGENT_TOKEN=' + JSON.stringify(data.token),
            ].join('\n');
            document.getElementById('agent-token-box').classList.remove('hidden');
            showActionResult(true, 'Agent-Token generiert. Speichere ihn jetzt in der Agent-Konfiguration.');
        } catch (err) {
            showActionResult(false, 'Fehler: ' + err.message, 'agent.token');
        }
    }

    async function disableAgent() {
        if (!confirm('Agent deaktivieren? Der aktuelle Agent-Token wird gelöscht.')) return;

        try {
            const res = await fetch('{{ route('server.agent.disable', $server) }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                showActionResult(false, data.message || 'Agent konnte nicht deaktiviert werden.', 'agent.disable');
                return;
            }

            showActionResult(true, 'Agent deaktiviert. Seite wird aktualisiert...');
            setTimeout(() => window.location.reload(), 800);
        } catch (err) {
            showActionResult(false, 'Fehler: ' + err.message, 'agent.disable');
        }
    }

    async function checkAgentUpdate() {
        if (!document.getElementById('agent-update-section')) return;

        try {
            const res = await fetch('{{ route('server.agent.check-update', $server) }}');
            const data = await res.json();

            if (data.has_update) {
                document.getElementById('agent-update-version').textContent = data.latest_version;
                document.getElementById('agent-update-section').classList.remove('hidden');
            }
        } catch {
            console.warn('Agent update check failed');
        }
    }

    async function updateAgent() {
        if (!confirm('Agent aktualisieren? Der Agent wird heruntergefahren und neu gestartet.')) return;

        const result = document.getElementById('action-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm';
        result.textContent = 'Aktualisiere Agent...';
        result.classList.remove('hidden');

        try {
            const res = await fetch('{{ route('server.agent.update', $server) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();

            result.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success
                ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200'
                : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
            result.textContent = data.message;

            if (data.success) {
                document.getElementById('agent-update-section').classList.add('hidden');
            }
        } catch (err) {
            const msg = 'Fehler: ' + err.message;
            result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            result.innerHTML = '';
            result.appendChild(document.createTextNode(msg));
            result.appendChild(window.reportError(msg, 'agent.update'));
        }
    }

    let metricsChart = null;
    let currentRange = '24h';

    const chartColors = {
        cpu: { line: '#22c55e', fill: 'rgba(34,197,94,0.1)' },
        ram: { line: '#3b82f6', fill: 'rgba(59,130,246,0.1)' },
        disk: { line: '#f59e0b', fill: 'rgba(245,158,11,0.1)' },
        grid: getComputedStyle(document.documentElement).getPropertyValue('--color-border').trim() || 'rgba(0,0,0,0.08)',
        text: getComputedStyle(document.documentElement).getPropertyValue('--color-muted').trim() || '#706f6c',
    };

    function isDarkMode() {
        return document.documentElement.classList.contains('dark');
    }

    function loadChart() {
        const url = '{{ route('server.agent.metrics.history', $server) }}?range=' + currentRange + '&_=' + Date.now();

        fetch(url)
            .then(r => r.json())
            .then(data => {
                const empty = document.getElementById('chart-empty');
                const canvas = document.getElementById('metrics-chart');

                if (!data.labels || data.labels.length < 2) {
                    canvas.classList.add('hidden');
                    empty.classList.remove('hidden');
                    return;
                }

                canvas.classList.remove('hidden');
                empty.classList.add('hidden');

                if (metricsChart) metricsChart.destroy();

                const dark = isDarkMode();
                const gridColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)';
                const textColor = dark ? '#A1A09A' : '#706f6c';

                const fmt = currentRange === '1h'
                    ? l => new Date(l).toLocaleTimeString('de-DE')
                    : l => new Date(l).toLocaleString('de-DE', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });

                metricsChart = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: data.labels.map(fmt),
                        datasets: [
                            {
                                label: 'CPU',
                                data: data.cpu,
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34,197,94,0.1)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 0,
                                borderWidth: 2,
                            },
                            {
                                label: 'RAM',
                                data: data.ram,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59,130,246,0.1)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 0,
                                borderWidth: 2,
                            },
                            {
                                label: 'Disk',
                                data: data.disk,
                                borderColor: '#f59e0b',
                                backgroundColor: 'rgba(245,158,11,0.1)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 0,
                                borderWidth: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 3,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: {
                                labels: {
                                    color: textColor,
                                    boxWidth: 12,
                                    padding: 16,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                },
                            },
                            tooltip: {
                                backgroundColor: dark ? '#161615' : '#fff',
                                titleColor: textColor,
                                bodyColor: textColor,
                                borderColor: gridColor,
                                borderWidth: 1,
                                callbacks: {
                                    label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y + '%',
                                },
                            },
                        },
                        scales: {
                            x: {
                                display: true,
                                grid: { color: gridColor },
                                ticks: { color: textColor, maxTicksLimit: 10 },
                            },
                            y: {
                                min: 0,
                                max: 100,
                                grid: { color: gridColor },
                                ticks: { color: textColor, callback: v => v + '%' },
                            },
                        },
                    },
                });
            })
            .catch(() => {
                const canvas = document.getElementById('metrics-chart');
                const empty = document.getElementById('chart-empty');
                if (canvas) canvas.classList.add('hidden');
                if (empty) empty.classList.remove('hidden');
                console.warn('Chart data could not be loaded');
            });
    }

    document.getElementById('chart-range-buttons')?.addEventListener('click', e => {
        const btn = e.target.closest('[data-range]');
        if (!btn) return;

        currentRange = btn.dataset.range;
        btn.closest('#chart-range-buttons').querySelectorAll('[data-range]').forEach(b => {
            b.className = 'rounded-md border border-[#19140035] px-2 py-1 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]';
        });
        btn.className = 'rounded-md border border-[#19140035] bg-[#1b1b18] px-2 py-1 text-white hover:border-[#1915014a] dark:border-[#3E3E3A] dark:bg-[#EDEDEC] dark:text-[#1C1C1A]';

        loadChart();
    });

    fetchMetrics();
    metricsRefreshTimer = setInterval(fetchMetrics, metricsRefreshMs);
    window.addEventListener('beforeunload', () => clearInterval(metricsRefreshTimer));

    if (@json($server->agent_enabled)) {
        checkAgentUpdate();
    }
    </script>
    @endpush
</x-layouts.app>
