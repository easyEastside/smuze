<x-layouts.app title="Dashboard: {{ $server->name }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.min.css" />

    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server Dashboard</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $server->name }}</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->username }}@{{ $server->host }}:{{ $server->port }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
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

        <div class="mt-6">
            <div class="flex flex-wrap gap-1 border-b border-[#19140020] dark:border-[#3E3E3A]">
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium data-[active=true]:bg-white data-[active=true]:text-[#1b1b18] data-[active=true]:shadow-[inset_0_1px_0_0_#f53003] dark:data-[active=true]:bg-[#161615] dark:data-[active=true]:text-[#EDEDEC]" data-tab="system" data-active="true">
                    System
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="services">
                    Dienste
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="firewall">
                    Firewall
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="apache">
                    Apache
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="mysql">
                    MySQL
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="github">
                    GitHub
                </button>
                <button type="button" class="tab-btn rounded-t-lg px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]" data-tab="terminal">
                    Terminal
                </button>
            </div>
        </div>

        {{-- System Tab --}}
        <div id="tab-system" class="tab-content mt-6" data-tab="system">
            <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Systeminformationen</p>
                            <button type="button" onclick="refreshDashboard()" class="rounded-lg border border-[#19140035] px-3 py-1 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                Aktualisieren
                            </button>
                        </div>

                        <div id="dashboard-loading" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Verbinde zum Server...
                        </div>

                        <div id="dashboard-content" class="mt-6 hidden">
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

                        <div id="dashboard-error" class="mt-4 hidden rounded-xl bg-red-50 p-4 text-sm text-red-800 dark:bg-red-950 dark:text-red-200"></div>
                    </div>

                    {{-- System Actions --}}
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
                    {{-- Server Details --}}
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Server Details</p>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Status</dt>
                                <dd id="conn-status" class="font-medium text-[#706f6c] dark:text-[#A1A09A]">Unbekannt</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Auth</dt>
                                <dd class="font-medium">{{ $server->auth_type === 'key' ? 'SSH-Key' : 'Passwort' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-[#706f6c] dark:text-[#A1A09A]">Sudo</dt>
                                <dd class="font-medium">{{ $server->use_sudo ? 'Ja' : 'Nein' }}</dd>
                            </div>
                            @if ($server->notes)
                                <div class="flex justify-between">
                                    <dt class="text-[#706f6c] dark:text-[#A1A09A]">Notizen</dt>
                                    <dd class="max-w-[160px] text-right font-medium">{{ $server->notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Service Status --}}
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Installierte Dienste</p>
                        <div id="services-list" class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center gap-2 text-[#706f6c] dark:text-[#A1A09A]">-</div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        {{-- Placeholder tabs for future phases --}}
        @foreach (['services', 'firewall', 'apache', 'mysql', 'github', 'terminal'] as $tab)
            <div id="tab-{{ $tab }}" class="tab-content mt-6 hidden" data-tab="{{ $tab }}">
                <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                    <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        @switch($tab)
                            @case('services') Dienstverwaltung (PHP, Node.js, nvm, Composer) — in Kürze verfügbar. @break
                            @case('firewall') UFW-Firewall-Verwaltung — in Kürze verfügbar. @break
                            @case('apache') Apache-Webserver-Verwaltung — in Kürze verfügbar. @break
                            @case('mysql') MySQL-Datenbank-Verwaltung — in Kürze verfügbar. @break
                            @case('github') GitHub-Deployment — in Kürze verfügbar. @break
                            @case('terminal') SSH-Terminal — in Kürze verfügbar. @break
                        @endswitch
                    </p>
                </div>
            </div>
        @endforeach
    </section>

    @push('scripts')
    <script>
    let refreshInterval = null;
    let currentServerId = {{ $server->id }};

    function formatBytes(mb) {
        if (mb >= 1024) return (mb / 1024).toFixed(1) + ' GB';
        return mb + ' MB';
    }

    function getBarColor(pct) {
        if (pct >= 90) return '#f53003';
        if (pct >= 70) return '#f59e0b';
        return '#22c55e';
    }

    function updateUsage(barId, textId, pct, used, total, label) {
        const bar = document.getElementById(barId);
        const text = document.getElementById(textId);
        if (!bar || !text) return;

        const fill = bar.querySelector('div');
        fill.style.width = pct + '%';
        fill.style.backgroundColor = getBarColor(pct);

        const formattedUsed = formatBytes(used);
        const formattedTotal = formatBytes(total);
        text.textContent = `${pct}% | ${formattedUsed} / ${formattedTotal}`;
    }

    function refreshDashboard() {
        const loading = document.getElementById('dashboard-loading');
        const content = document.getElementById('dashboard-content');
        const error = document.getElementById('dashboard-error');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        error.classList.add('hidden');

        fetch('{{ route('server.dashboard.refresh', $server) }}')
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');

                if (data.error) {
                    error.textContent = data.error;
                    error.classList.remove('hidden');
                    return;
                }

                document.getElementById('sys-hostname').textContent = data.hostname || '-';
                document.getElementById('sys-os').textContent = data.os || '-';
                document.getElementById('sys-uptime').textContent = data.uptime || '-';
                document.getElementById('sys-load').textContent = data.load || '-';

                if (data.cpu_percent !== null) {
                    updateUsage('cpu-bar', 'cpu-text', data.cpu_percent, data.cpu_percent, 100, 'CPU');
                } else {
                    document.getElementById('cpu-text').textContent = '-';
                }

                if (data.ram_percent !== null && data.ram_total_mb !== null) {
                    updateUsage('ram-bar', 'ram-text', data.ram_percent, data.ram_used_mb, data.ram_total_mb, 'RAM');
                } else {
                    document.getElementById('ram-text').textContent = '-';
                }

                if (data.disk_percent !== null && data.disk_total_mb !== null) {
                    updateUsage('disk-bar', 'disk-text', data.disk_percent, data.disk_used_mb, data.disk_total_mb, 'Disk');
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

                // Update services list
                const servicesList = document.getElementById('services-list');
                servicesList.innerHTML = '';
                const services = [
                    { key: 'php', label: 'PHP', version: data.php_version },
                    { key: 'apache', label: 'Apache', version: data.apache_version },
                    { key: 'mysql', label: 'MySQL', version: data.mysql_version },
                    { key: 'node', label: 'Node.js', version: data.node_version },
                    { key: 'nvm', label: 'nvm', version: data.nvm_version },
                    { key: 'composer', label: 'Composer', version: data.composer_version },
                ];
                for (const svc of services) {
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between';
                    if (svc.version) {
                        div.innerHTML = `
                            <span class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-green-500"></span>
                                ${svc.label}
                            </span>
                            <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">${svc.version}</span>
                        `;
                    } else {
                        div.innerHTML = `
                            <span class="flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-[#19140035] dark:bg-[#3E3E3A]"></span>
                                ${svc.label}
                            </span>
                            <span class="text-xs text-[#706f6c] dark:text-[#A1A09A]">Nicht installiert</span>
                        `;
                    }
                    servicesList.appendChild(div);
                }

                document.getElementById('conn-status').textContent = 'Online';
                document.getElementById('conn-status').className = 'font-medium text-green-500';

                content.classList.remove('hidden');
            })
            .catch(err => {
                loading.classList.add('hidden');
                error.textContent = 'Verbindungsfehler: ' + err.message;
                error.classList.remove('hidden');
                document.getElementById('conn-status').textContent = 'Offline';
                document.getElementById('conn-status').className = 'font-medium text-red-500';
            });
    }

    function testConnection() {
        const btn = document.getElementById('btn-test-connection');
        btn.disabled = true;
        btn.textContent = 'Teste...';

        fetch('{{ route('server.dashboard.test-connection', $server) }}')
            .then(r => r.json())
            .then(data => {
                const status = document.getElementById('conn-status');
                if (data.success) {
                    status.textContent = 'Online (' + data.latency_ms + ' ms)';
                    status.className = 'font-medium text-green-500';
                } else {
                    status.textContent = 'Offline';
                    status.className = 'font-medium text-red-500';
                }
                btn.textContent = 'Verbindung testen';
                btn.disabled = false;
            })
            .catch(() => {
                btn.textContent = 'Fehler';
                btn.disabled = false;
            });
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
                if (data.success) {
                    result.className = 'mt-3 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                } else {
                    result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                }
                result.textContent = data.message;
            })
            .catch(err => {
                result.className = 'mt-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
            });
    }

    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.dataset.active = 'false';
                b.classList.remove('bg-white', 'text-[#1b1b18]', 'shadow-[inset_0_1px_0_0_#f53003]', 'dark:bg-[#161615]', 'dark:text-[#EDEDEC]');
                b.classList.add('text-[#706f6c]', 'dark:text-[#A1A09A]');
            });
            this.dataset.active = 'true';
            this.classList.remove('text-[#706f6c]', 'dark:text-[#A1A09A]');
            this.classList.add('bg-white', 'text-[#1b1b18]', 'shadow-[inset_0_1px_0_0_#f53003]', 'dark:bg-[#161615]', 'dark:text-[#EDEDEC]');

            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.add('hidden'));
            const tab = document.getElementById('tab-' + this.dataset.tab);
            if (tab) tab.classList.remove('hidden');
        });
    });

    // Auto-refresh every 30s
    refreshDashboard();
    refreshInterval = setInterval(refreshDashboard, 30000);
    </script>
    @endpush
</x-layouts.app>
