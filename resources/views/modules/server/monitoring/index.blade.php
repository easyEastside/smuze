<x-layouts.app title="Monitoring: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Monitoring</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Prozesse &amp; Services</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} - {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="monitoring-refresh" class="rounded-lg bg-[#1b1b18] px-3 py-1.5 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                        Aktualisieren
                    </button>
                    <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        System
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Prozesse</p>
                        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Top 50 nach CPU-Auslastung.</p>
                    </div>
                    <span id="processes-status" class="text-xs text-[#706f6c] dark:text-[#A1A09A]">-</span>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-[#19140020] text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                            <tr>
                                <th class="sticky left-0 bg-white py-2 pr-3 font-medium dark:bg-[#161615]">Aktion</th>
                                <th class="py-2 pr-3 font-medium">PID</th>
                                <th class="py-2 pr-3 font-medium">User</th>
                                <th class="py-2 pr-3 text-right font-medium">CPU</th>
                                <th class="py-2 pr-3 text-right font-medium">RAM</th>
                                <th class="py-2 font-medium">Command</th>
                            </tr>
                        </thead>
                        <tbody id="processes-body" class="divide-y divide-[#19140020] dark:divide-[#3E3E3A]">
                            <tr><td colspan="6" class="py-4 text-[#706f6c] dark:text-[#A1A09A]">Lade Prozesse...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Services</p>
                        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">Systemd Services read-only.</p>
                    </div>
                    <span id="services-status" class="text-xs text-[#706f6c] dark:text-[#A1A09A]">-</span>
                </div>
                <div id="services-list" class="mt-4 space-y-2 text-sm">
                    <div class="text-[#706f6c] dark:text-[#A1A09A]">Lade Services...</div>
                </div>
            </div>
        </div>

        <div id="monitoring-result" class="mt-6 hidden rounded-xl p-3 text-sm"></div>
    </section>

    @push('scripts')
    <script>
    const refreshButton = document.getElementById('monitoring-refresh');
    const csrfToken = '{{ csrf_token() }}';

    refreshButton.addEventListener('click', () => loadMonitoring());

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;',
        }[char]));
    }

    function parseProcesses(stdout) {
        return stdout.split('\n').map(line => line.trim()).filter(Boolean).map(line => {
            const match = line.match(/^(\d+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s*(.*)$/);
            if (!match) return null;

            return {
                pid: match[1],
                user: match[3],
                stat: match[4],
                cpu: match[5],
                mem: match[6],
                command: match[8] || match[7],
            };
        }).filter(Boolean);
    }

    function parseServices(stdout) {
        return stdout.split('\n').map(line => line.trim()).filter(Boolean).map(line => {
            const match = line.match(/^(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(.*)$/);
            if (!match) return null;

            return {
                unit: match[1],
                load: match[2],
                active: match[3],
                sub: match[4],
                description: match[5],
            };
        }).filter(Boolean);
    }

    function renderProcesses(processes) {
        const body = document.getElementById('processes-body');
        if (processes.length === 0) {
            body.innerHTML = '<tr><td colspan="6" class="py-4 text-[#706f6c] dark:text-[#A1A09A]">Keine Prozesse gefunden.</td></tr>';
            return;
        }

        body.innerHTML = processes.map(process => `
            <tr>
                <td class="sticky left-0 bg-white py-2 pr-3 dark:bg-[#161615]"><button type="button" onclick="killProcess('${escapeHtml(process.pid)}')" class="rounded-lg border border-red-300 px-2 py-1 text-xs text-red-700 hover:border-red-500 dark:border-red-900 dark:text-red-300">Kill</button></td>
                <td class="py-2 pr-3 font-mono">${escapeHtml(process.pid)}</td>
                <td class="py-2 pr-3">${escapeHtml(process.user)}</td>
                <td class="py-2 pr-3 text-right">${escapeHtml(process.cpu)}%</td>
                <td class="py-2 pr-3 text-right">${escapeHtml(process.mem)}%</td>
                <td class="max-w-[320px] truncate py-2 font-mono" title="${escapeHtml(process.command)}">${escapeHtml(process.command)}</td>
            </tr>
        `).join('');
    }

    function serviceClass(active) {
        if (active === 'active') return 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300';
        if (active === 'failed') return 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300';
        return 'bg-[#19140008] text-[#706f6c] dark:bg-[#fffaed0a] dark:text-[#A1A09A]';
    }

    function renderServices(services) {
        const list = document.getElementById('services-list');
        if (services.length === 0) {
            list.innerHTML = '<div class="text-[#706f6c] dark:text-[#A1A09A]">Keine Services gefunden.</div>';
            return;
        }

        list.innerHTML = services.map(service => `
            <div class="rounded-xl border border-[#19140020] p-3 dark:border-[#3E3E3A]">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="font-mono text-xs font-medium">${escapeHtml(service.unit)}</p>
                        <p class="mt-1 text-xs text-[#706f6c] dark:text-[#A1A09A]">${escapeHtml(service.description)}</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <span class="rounded-md px-2 py-0.5 text-xs font-medium ${serviceClass(service.active)}">${escapeHtml(service.active)} / ${escapeHtml(service.sub)}</span>
                        <button type="button" onclick="serviceAction('${escapeHtml(service.unit)}', 'start')" ${service.active === 'active' ? 'disabled' : ''} class="rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Start</button>
                        <button type="button" onclick="serviceAction('${escapeHtml(service.unit)}', 'stop')" ${service.active !== 'active' ? 'disabled' : ''} class="rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] disabled:cursor-not-allowed disabled:opacity-40 dark:border-[#3E3E3A] dark:hover:border-[#62605b]">Stop</button>
                        <button type="button" onclick="serviceAction('${escapeHtml(service.unit)}', 'restart')" class="rounded-lg bg-[#1b1b18] px-2 py-1 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">Restart</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function showResult(success, message) {
        const result = document.getElementById('monitoring-result');
        result.className = 'mt-6 rounded-xl p-3 text-sm ' + (success
            ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200'
            : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        result.textContent = message;
        result.classList.remove('hidden');
    }

    async function postMonitoringAction(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.error || data.stderr || 'Aktion fehlgeschlagen.');
        }

        return data;
    }

    async function serviceAction(service, action) {
        if (!confirm(`${service} wirklich ${action}?`)) return;

        try {
            await postMonitoringAction('{{ route('server.monitoring.services.action', $server) }}', { service, action });
            showResult(true, `Service-Aktion ausgeführt: ${service} ${action}`);
            loadMonitoring();
        } catch (error) {
            showResult(false, error.message);
        }
    }

    async function killProcess(pid) {
        if (!confirm(`Prozess ${pid} mit SIGTERM beenden?`)) return;

        try {
            await postMonitoringAction('{{ route('server.monitoring.processes.kill', $server) }}', { pid });
            showResult(true, `SIGTERM an Prozess ${pid} gesendet.`);
            loadMonitoring();
        } catch (error) {
            showResult(false, error.message);
        }
    }

    async function loadEndpoint(url, statusId, callback) {
        const status = document.getElementById(statusId);
        status.textContent = 'lädt...';

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Agent-Action fehlgeschlagen.');
            }

            callback(data.stdout || '');
            status.textContent = new Date().toLocaleTimeString('de-DE');
        } catch (error) {
            status.textContent = 'Fehler';
            throw error;
        }
    }

    async function loadMonitoring() {
        refreshButton.disabled = true;
        refreshButton.textContent = 'Lädt...';

        await Promise.allSettled([
            loadEndpoint('{{ route('server.monitoring.processes', $server) }}', 'processes-status', stdout => renderProcesses(parseProcesses(stdout))),
            loadEndpoint('{{ route('server.monitoring.services', $server) }}', 'services-status', stdout => renderServices(parseServices(stdout))),
        ]);

        refreshButton.disabled = false;
        refreshButton.textContent = 'Aktualisieren';
    }

    loadMonitoring();
    </script>
    @endpush
</x-layouts.app>
