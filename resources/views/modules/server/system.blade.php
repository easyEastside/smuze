<x-layouts.app title="System: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server System</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $server->name }}</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->username }}<span>@</span>{{ $server->host }}:{{ $server->port }}
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
                        <button type="button" onclick="refreshSystem()" class="rounded-lg border border-[#19140035] px-3 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
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
                        <button type="button" onclick="systemAction('{{ route('server.update-packages', $server) }}', 'apt update ausführen?')" class="rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            APT Update
                        </button>
                        <button type="button" onclick="systemAction('{{ route('server.upgrade-packages', $server) }}', 'System-Upgrade ausführen? Dies kann einige Minuten dauern.')" class="rounded-lg bg-[#f59e0b] px-4 py-2 text-sm font-medium text-white hover:bg-[#d97706]">
                            APT Upgrade
                        </button>
                        <button type="button" onclick="systemAction('{{ route('server.restart', $server) }}', 'Server neu starten?')" class="rounded-lg bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                            Neustart
                        </button>
                        <button type="button" onclick="systemAction('{{ route('server.stop', $server) }}', 'Server herunterfahren?')" class="rounded-lg border border-[#19140035] px-4 py-2 text-sm font-medium hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                            Herunterfahren
                        </button>
                    </div>
                    <div id="action-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
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
                            <dt class="text-[#706f6c] dark:text-[#A1A09A]">Auth</dt>
                            <dd class="font-medium">{{ $server->auth_type === 'key' ? 'SSH-Key' : 'Passwort' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#706f6c] dark:text-[#A1A09A]">Sudo</dt>
                            <dd class="font-medium">{{ $server->use_sudo ? 'Ja' : 'Nein' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#706f6c] dark:text-[#A1A09A]">Engine</dt>
                            <dd class="font-medium">{{ strtoupper($server->execution_driver ?? 'ssh') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-[#706f6c] dark:text-[#A1A09A]">Agent</dt>
                            <dd class="font-medium">{{ $server->agent_enabled ? $server->agent_status : 'deaktiviert' }}</dd>
                        </div>
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
                    <p class="mt-2 text-xs leading-5 text-[#706f6c] dark:text-[#A1A09A]">Generiere einen Token für den Polling-Agent. Der Token wird nur direkt nach der Rotation angezeigt.</p>
                    <div class="mt-4 flex flex-col gap-2 text-sm">
                        @if ($server->agent_enabled && $server->agent_status === 'connected')
                            <span class="rounded-lg bg-green-50 px-3 py-2 text-center text-xs font-medium text-green-800 dark:bg-green-950 dark:text-green-200">
                                Agent verbunden
                            </span>
                        @else
                            <button type="button" onclick="installAgent()" class="rounded-lg bg-[#f53003] px-3 py-2 font-medium text-white hover:bg-[#d42a02] dark:bg-[#FF4433] dark:hover:bg-[#e63a2e]">
                                Agent per SSH installieren
                            </button>
                            <button type="button" onclick="rotateAgentToken()" class="rounded-lg bg-[#1b1b18] px-3 py-2 font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                                Nur Token generieren
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
    let refreshInterval = null;
    const systemCacheKey = 'smuze:server:{{ $server->id }}:system-info';

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

    function setUpdateMode(label, className) {
        const mode = document.getElementById('update-mode');
        mode.textContent = label;
        mode.className = className;
    }

    function renderSystemData(data, source = 'initial') {
        window.lastSystemData = data;

        try {
            sessionStorage.setItem(systemCacheKey, JSON.stringify({ data, cached_at: Date.now() }));
        } catch {
            // Ignore unavailable storage.
        }

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

        if (source === 'polling') {
            setUpdateMode('Polling', 'font-medium text-yellow-600 dark:text-yellow-400');
        }
    }

    function showSystemContent() {
        document.getElementById('system-loading').classList.add('hidden');
        document.getElementById('system-error').classList.add('hidden');
        document.getElementById('system-content').classList.remove('hidden');
    }

    function refreshSystem() {
        const loading = document.getElementById('system-loading');
        const content = document.getElementById('system-content');
        const error = document.getElementById('system-error');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        error.classList.add('hidden');

        fetch('{{ route('server.system.refresh', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');

                if (data.error) {
                    error.textContent = data.error;
                    error.classList.remove('hidden');
                    return;
                }

                renderSystemData(data, refreshInterval ? 'polling' : 'initial');
                content.classList.remove('hidden');
            })
            .catch(err => {
                loading.classList.add('hidden');
                error.textContent = 'Verbindungsfehler: ' + err.message;
                error.classList.remove('hidden');
                setConnectionStatus('Offline', 'font-medium text-red-500');
                setUpdateMode('Fehler', 'font-medium text-red-500');
            });
    }

    function testConnection() {
        const btn = document.getElementById('btn-test-connection');
        btn.disabled = true;
        btn.textContent = 'Teste...';

        fetch('{{ route('server.system.test-connection', $server) }}')
            .then(r => r.json())
            .then(data => {
                setConnectionStatus(data.success ? 'Online (' + data.latency_ms + ' ms)' : 'Offline', data.success ? 'font-medium text-green-500' : 'font-medium text-red-500');
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

    async function installAgent() {
        if (!confirm('Agent per SSH installieren? Der bestehende SSH-Zugang wird dafür einmalig genutzt.')) return;

        const result = document.getElementById('action-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm';
        result.textContent = 'Installiere Agent auf dem Server...';
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
                setTimeout(() => window.location.reload(), 2000);
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
        if (!confirm('Agent deaktivieren und auf SSH zurückstellen?')) return;

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

    function systemAction(url, confirmMsg) {
        if (!confirm(confirmMsg)) return;

        const result = document.getElementById('action-result');
        result.className = 'mt-3 rounded-xl p-3 text-sm';
        result.textContent = 'Führe Befehl aus...';
        result.classList.remove('hidden');

        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                result.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                result.textContent = data.message;
            })
            .catch(err => {
                result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
            });
    }

    refreshSystem();
    </script>
    @endpush
</x-layouts.app>
