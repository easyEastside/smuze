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
                    <a href="{{ route('server.index') }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Zurück
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_280px]">
            <div class="space-y-6">
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

                        <div class="mt-6 rounded-xl border border-[#19140020] p-4 dark:border-[#3E3E3A]">
                            <p class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Details</p>
                            <pre id="sys-details" class="mt-2 overflow-x-auto text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]"></pre>
                        </div>
                    </div>

                    <div id="system-error" class="mt-4 hidden rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200"></div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">System-Aktionen</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" onclick="systemAction('apt update', 'apt update ausführen?')" class="rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            APT Update
                        </button>
                        <button type="button" onclick="systemAction('apt upgrade -y', 'System-Upgrade ausführen? Dies kann einige Minuten dauern.')" class="rounded-lg bg-[#f59e0b] px-4 py-2 text-sm font-medium text-white hover:bg-[#d97706]">
                            APT Upgrade
                        </button>
                        <button type="button" onclick="systemAction('reboot', 'Server neu starten?')" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                            Neustart
                        </button>
                        <button type="button" onclick="systemAction('shutdown -h now', 'Server herunterfahren?')" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                            Herunterfahren
                        </button>
                    </div>
                    <div id="action-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
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
                                                <span class="block truncate" title="{{ $command->command }}">{{ $command->command }}</span>
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
                        @if ($server->agent_last_seen_at)
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Agent zuletzt</dt>
                                <dd class="font-medium">{{ $server->agent_last_seen_at->diffForHumans() }}</dd>
                            </div>
                        @endif
                        @if ($server->notes)
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Notizen</dt>
                                <dd class="max-w-[160px] text-right font-medium">{{ $server->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Module</p>
                    <div class="mt-4 flex flex-col gap-2 text-sm">
                        <a href="{{ route('server.services.index', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-2 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Dienste</a>
                        <a href="{{ route('server.firewall.index', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-2 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Firewall</a>
                        <a href="{{ route('server.apache.index', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-2 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Apache</a>
                        <a href="{{ route('server.mysql.index', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-2 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">MySQL</a>
                        <a href="{{ route('server.github.index', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-2 hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">GitHub</a>
                    </div>
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

                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Installierte Dienste</p>
                    <div id="services-list" class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center gap-2 text-[#706f6c] dark:text-[#A1A09A]">-</div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    @push('scripts')
    <script>
    const host = '{{ $server->host }}';
    const port = '{{ $server->agent_port ?? config('agent.push_port', 9300) }}';

    function formatBytes(mb) {
        if (mb >= 1024) return (mb / 1024).toFixed(1) + ' GB';
        return mb + ' MB';
    }

    function getBarColor(pct) {
        if (pct >= 90) return '#f53003';
        if (pct >= 70) return '#f59e0b';
        return '#22c55e';
    }

    function updateUsage(barId, textId, pct, used, total) {
        const bar = document.getElementById(barId);
        const text = document.getElementById(textId);
        if (!bar || !text) return;

        const fill = bar.querySelector('div');
        fill.style.width = pct + '%';
        fill.style.backgroundColor = getBarColor(pct);
        text.textContent = `${pct}% | ${formatBytes(used)} / ${formatBytes(total)}`;
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
            updateUsage('cpu-bar', 'cpu-text', data.cpu_percent, data.cpu_percent, 100);
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

        const details = [];
        if (data.php_version) details.push('PHP: ' + data.php_version);
        if (data.apache_version) details.push('Apache: ' + data.apache_version);
        if (data.mysql_version) details.push('MySQL: ' + data.mysql_version);
        if (data.node_version) details.push('Node.js: ' + data.node_version);
        if (data.nvm_version) details.push('nvm: ' + data.nvm_version);
        if (data.composer_version) details.push('Composer: ' + data.composer_version);
        document.getElementById('sys-details').textContent = details.join('\n') || 'Keine Detailinformationen verfügbar.';

        const servicesList = document.getElementById('services-list');
        servicesList.innerHTML = '';
        const services = [
            { label: 'PHP', version: data.php_version },
            { label: 'Apache', version: data.apache_version },
            { label: 'MySQL', version: data.mysql_version },
            { label: 'Node.js', version: data.node_version },
            { label: 'nvm', version: data.nvm_version },
            { label: 'Composer', version: data.composer_version },
        ];

        for (const svc of services) {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between';
            div.innerHTML = svc.version
                ? `<span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-green-500"></span>${svc.label}</span><span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">${svc.version}</span>`
                : `<span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-[#19140035] dark:bg-[#3E3E3A]"></span>${svc.label}</span><span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Nicht installiert</span>`;
            servicesList.appendChild(div);
        }

        setConnectionStatus('Online', 'font-medium text-green-500');
    }

    function fetchMetrics() {
        const loading = document.getElementById('system-loading');
        const content = document.getElementById('system-content');
        const error = document.getElementById('system-error');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        error.classList.add('hidden');

        fetch('{{ route('server.agent.metrics', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');

                if (data.error) {
                    error.textContent = data.error;
                    error.classList.remove('hidden');
                    return;
                }

                renderSystemData(data);
                content.classList.remove('hidden');
            })
            .catch(err => {
                loading.classList.add('hidden');
                error.textContent = 'Verbindungsfehler: ' + err.message;
                error.classList.remove('hidden');
                setConnectionStatus('Offline', 'font-medium text-red-500');
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
                btn.textContent = 'Fehler';
                btn.disabled = false;
            });
    }

    function showActionResult(success, message) {
        const result = document.getElementById('action-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        result.textContent = message;
        result.classList.remove('hidden');
    }

    function systemAction(command, confirmMsg) {
        if (!confirm(confirmMsg)) return;

        const result = document.getElementById('action-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm';
        result.textContent = 'Führe Befehl aus...';
        result.classList.remove('hidden');

        fetch('{{ route('server.agent.execute', $server) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ command, timeout: 120, use_sudo: true }),
        })
        .then(r => r.json())
        .then(data => {
            const ok = data.success && data.exit_code === 0;
            const output = data.data ? data.data.map(c => c.data || '').join('') : '';
            result.className = 'mt-3 rounded-xl p-3 text-sm ' + (ok ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
            result.textContent = ok ? (output || 'Befehl ausgeführt.') : (data.error || output || 'Fehler bei Ausführung.');
        })
        .catch(err => {
            result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            result.textContent = 'Fehler: ' + err.message;
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
            result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            result.textContent = 'Fehler: ' + err.message;
        }
    }

    async function rotateAgentToken() {
        if (!confirm('Agent-Token neu generieren? Der bisherige Agent muss danach neu konfiguriert werden.')) return;

        const res = await fetch('{{ route('server.agent.token', $server) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
            showActionResult(false, data.message || 'Agent-Token konnte nicht generiert werden.');
            return;
        }

        document.getElementById('agent-token-output').textContent = data.install_command || [
            'SMUZE_APP_URL=' + JSON.stringify(data.app_url),
            'SMUZE_SERVER_ID=' + JSON.stringify(String(data.server_id)),
            'SMUZE_AGENT_TOKEN=' + JSON.stringify(data.token),
        ].join('\n');
        document.getElementById('agent-token-box').classList.remove('hidden');
        showActionResult(true, 'Agent-Token generiert. Speichere ihn jetzt in der Agent-Konfiguration.');
    }

    async function disableAgent() {
        if (!confirm('Agent deaktivieren? Der aktuelle Agent-Token wird gelöscht.')) return;

        const res = await fetch('{{ route('server.agent.disable', $server) }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
            showActionResult(false, data.message || 'Agent konnte nicht deaktiviert werden.');
            return;
        }

        showActionResult(true, 'Agent deaktiviert. Seite wird aktualisiert...');
        setTimeout(() => window.location.reload(), 800);
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
            // Ignore check errors
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
            result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
            result.textContent = 'Fehler: ' + err.message;
        }
    }

    fetchMetrics();

    if (@json($server->agent_enabled)) {
        checkAgentUpdate();
    }
    </script>
    @endpush
</x-layouts.app>
