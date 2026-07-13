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
                                <th class="py-2 pr-3 text-right font-medium">CPU %</th>
                                <th class="py-2 pr-3 text-right font-medium">RAM %</th>
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
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-[#19140020] text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                            <tr>
                                <th class="py-2 pr-3 font-medium">Unit</th>
                                <th class="py-2 pr-3 font-medium">Status</th>
                                <th class="py-2 text-right font-medium">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody id="services-body" class="divide-y divide-[#19140020] dark:divide-[#3E3E3A]">
                            <tr><td colspan="3" class="py-4 text-[#706f6c] dark:text-[#A1A09A]">Lade Services...</td></tr>
                        </tbody>
                    </table>
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
    document.getElementById('processes-body').addEventListener('click', event => {
        const button = event.target.closest('[data-pid]');
        if (!button) return;

        killProcess(button.dataset.pid);
    });
    document.getElementById('services-body').addEventListener('click', event => {
        const button = event.target.closest('[data-service][data-action]');
        if (!button || button.disabled) return;

        serviceAction(button.dataset.service, button.dataset.action);
    });

    function parseProcesses(stdout) {
        try {
            return JSON.parse(stdout);
        } catch {
            return [];
        }
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

    function fmtPercent(value) {
        const num = parseFloat(String(value || '0').replace(/[^0-9.,]/g, '').replace(',', '.'));
        return isNaN(num) ? '0.0' : num.toFixed(1);
    }

    function renderProcesses(processes) {
        const body = document.getElementById('processes-body');
        body.replaceChildren();

        if (processes.length === 0) {
            body.appendChild(emptyRow(6, 'Keine Prozesse gefunden.'));
            return;
        }

        processes.forEach(process => {
            const row = document.createElement('tr');

            const actionCell = td('sticky left-0 bg-white py-2 pr-3 dark:bg-[#161615]');
            const killButton = document.createElement('button');
            killButton.type = 'button';
            killButton.dataset.pid = String(process.pid ?? '');
            killButton.className = 'rounded-lg border border-red-300 px-2 py-1 text-xs text-red-700 hover:border-red-500 dark:border-red-900 dark:text-red-300';
            killButton.textContent = 'Kill';
            actionCell.appendChild(killButton);

            const commandCell = td('max-w-[320px] truncate py-2 font-mono', process.command);
            commandCell.title = String(process.command ?? '');

            row.append(
                actionCell,
                td('py-2 pr-3 font-mono', process.pid),
                td('py-2 pr-3', process.user),
                td('py-2 pr-3 text-right', `${fmtPercent(process.cpu)}%`),
                td('py-2 pr-3 text-right', `${fmtPercent(process.mem)}%`),
                commandCell,
            );
            body.appendChild(row);
        });
    }

    function td(className, text = '') {
        const cell = document.createElement('td');
        cell.className = className;
        cell.textContent = String(text ?? '');

        return cell;
    }

    function emptyRow(colspan, message) {
        const row = document.createElement('tr');
        const cell = td('py-4 text-[#706f6c] dark:text-[#A1A09A]', message);
        cell.colSpan = colspan;
        row.appendChild(cell);

        return row;
    }

    function serviceStatus(active, sub) {
        if (active === 'active') return { color: '#22c55e', label: active + ' / ' + sub };
        if (active === 'failed') return { color: '#f53003', label: active + ' / ' + sub };
        return { color: '#706f6c', label: active + ' / ' + sub };
    }

    function renderServices(services) {
        const body = document.getElementById('services-body');
        body.replaceChildren();

        if (services.length === 0) {
            body.appendChild(emptyRow(3, 'Keine Services gefunden.'));
            return;
        }

        services.forEach(service => {
            const st = serviceStatus(service.active, service.sub);
            const row = document.createElement('tr');

            const serviceCell = td('py-3 pr-3');
            const unit = document.createElement('p');
            unit.className = 'font-mono text-xs font-medium';
            unit.textContent = service.unit;
            const description = document.createElement('p');
            description.className = 'mt-0.5 text-xs text-[#706f6c] dark:text-[#A1A09A]';
            description.textContent = service.description;
            serviceCell.append(unit, description);

            const statusCell = td('py-3 pr-3');
            const badge = document.createElement('span');
            badge.className = 'inline-flex items-center gap-1.5 whitespace-nowrap';
            const dot = document.createElement('span');
            dot.className = 'size-2 rounded-full';
            dot.style.background = st.color;
            const label = document.createElement('span');
            label.textContent = st.label;
            badge.append(dot, label);
            statusCell.appendChild(badge);

            const actionCell = td('py-3 text-right');
            const actions = document.createElement('div');
            actions.className = 'inline-flex items-center gap-1';
            actions.append(
                serviceButton(service.unit, 'start', 'Start', service.active === 'active'),
                serviceButton(service.unit, 'stop', 'Stop', service.active !== 'active'),
                serviceButton(service.unit, 'restart', 'Restart', false, 'primary'),
            );
            actionCell.appendChild(actions);

            row.append(serviceCell, statusCell, actionCell);
            body.appendChild(row);
        });
    }

    function serviceButton(service, action, label, disabled, variant = 'default') {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.service = service;
        button.dataset.action = action;
        button.disabled = disabled;
        button.className = variant === 'primary'
            ? 'rounded-lg bg-[#1b1b18] px-2 py-1 text-xs font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]'
            : 'rounded-lg border border-[#19140035] px-2 py-1 text-xs hover:border-[#1915014a] disabled:cursor-not-allowed disabled:opacity-30 dark:border-[#3E3E3A] dark:hover:border-[#62605b]';
        button.textContent = label;

        return button;
    }

    function showResult(success, message) {
        const result = document.getElementById('monitoring-result');
        result.className = 'mt-6 rounded-xl p-3 text-sm ' + (success
            ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200'
            : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        result.replaceChildren();
        result.appendChild(document.createTextNode(message));
        if (!success) {
            result.appendChild(window.reportError(message, 'monitoring'));
        }
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
