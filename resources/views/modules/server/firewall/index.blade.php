<x-layouts.app title="Firewall: {{ $server->name }}">
    <section class="w-full max-w-6xl">
        <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Firewall</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">UFW-Firewall-Verwaltung</h1>
                    <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                        {{ $server->name }} — {{ $server->host }}:{{ $server->agent_port ?? config('agent.push_port', 9300) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btn-refresh-firewall" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Aktualisieren
                    </button>
                    <a href="{{ route('server.system', $server) }}" class="rounded-lg border border-[#19140035] px-3 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                        Zurück zum System
                    </a>
                </div>
            </div>
        </div>

        <div id="fw-loading" class="mt-6 rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Verbinde zum Server...</p>
        </div>

        <div id="fw-install-overlay" class="mt-6 hidden">
            <div class="rounded-2xl bg-white p-12 text-center shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d]">
                <p class="text-lg font-semibold">UFW ist nicht installiert</p>
                <p class="mt-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">Installiere UFW um die Firewall zu verwalten.</p>
                <button type="button" id="btn-install-ufw" class="mt-6 rounded-lg bg-[#1b1b18] px-6 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                    UFW installieren
                </button>
                <div id="fw-install-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
            </div>
        </div>

        <div id="fw-content" class="mt-6 hidden">
            <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
                <div class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Regeln</p>
                                <span id="fw-status-badge" class="rounded-full px-2.5 py-0.5 text-xs font-medium"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btn-enable" data-action-url="{{ route('server.firewall.enable', $server) }}" data-confirm="UFW aktivieren?" class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700">
                                    Aktivieren
                                </button>
                                <button type="button" id="btn-disable" data-action-url="{{ route('server.firewall.disable', $server) }}" data-confirm="UFW wirklich deaktivieren? Der Server ist dann ungeschützt." class="rounded-lg border border-[#19140035] px-3 py-1.5 text-xs hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                    Deaktivieren
                                </button>
                            </div>
                        </div>

                        <div id="fw-rules-empty" class="mt-4 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            Keine Firewall-Regeln vorhanden.
                        </div>

                        <div id="fw-rules-table" class="mt-4 hidden overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-[#19140020] text-xs text-[#706f6c] dark:border-[#3E3E3A] dark:text-[#A1A09A]">
                                        <th class="px-3 py-2 font-medium">Nr.</th>
                                        <th class="px-3 py-2 font-medium">Aktion</th>
                                        <th class="px-3 py-2 font-medium">Port</th>
                                        <th class="px-3 py-2 font-medium">Proto</th>
                                        <th class="px-3 py-2 font-medium">Quelle</th>
                                        <th class="px-3 py-2 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody id="fw-rules-tbody"></tbody>
                            </table>
                        </div>

                        <div id="fw-action-result" class="mt-4 hidden rounded-xl p-3 text-sm"></div>
                    </div>
                </div>

                <aside class="space-y-6">
                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Port verwalten</p>

                        <div class="mt-4">
                            <label class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Port</label>
                            <input type="number" id="fw-port-input" min="1" max="65535" placeholder="z.B. 8080" class="mt-1 block w-full rounded-lg border border-[#19140035] px-3 py-2 text-sm focus:border-[#f53003] focus:outline-none dark:border-[#3E3E3A] dark:bg-[#161615]">
                        </div>

                        <div class="mt-3">
                            <p class="text-xs font-medium text-[#706f6c] dark:text-[#A1A09A]">Protokoll</p>
                            <div class="mt-1 flex gap-4">
                                <label class="flex items-center gap-1.5 text-sm">
                                    <input type="radio" name="fw-proto" value="tcp" checked class="text-[#f53003]">
                                    TCP
                                </label>
                                <label class="flex items-center gap-1.5 text-sm">
                                    <input type="radio" name="fw-proto" value="udp" class="text-[#f53003]">
                                    UDP
                                </label>
                                <label class="flex items-center gap-1.5 text-sm">
                                    <input type="radio" name="fw-proto" value="" class="text-[#f53003]">
                                    Beide
                                </label>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <button type="button" data-port-action="allow" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                                Freigeben
                            </button>
                            <button type="button" data-port-action="deny" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                                Blocken
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <p class="text-sm text-[#f53003] dark:text-[#FF4433]">Schnellzugriff</p>
                        <div class="mt-4 flex flex-col gap-2">
                            @foreach ([
                                ['label' => 'SSH (22)', 'port' => '22'],
                                ['label' => 'HTTP (80)', 'port' => '80'],
                                ['label' => 'HTTPS (443)', 'port' => '443'],
                                ['label' => 'MySQL (3306)', 'port' => '3306'],
                                ['label' => 'PostgreSQL (5432)', 'port' => '5432'],
                            ] as $preset)
                                <button type="button" data-preset-port="{{ $preset['port'] }}" class="rounded-lg border border-[#19140035] px-3 py-2 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]">
                                    {{ $preset['label'] }}
                                </button>
                            @endforeach
                        </div>
                        <div id="fw-preset-result" class="mt-3 hidden rounded-xl p-3 text-sm"></div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow-[inset_0_0_0_1px_rgba(26,26,0,0.16)] dark:bg-[#161615] dark:shadow-[inset_0_0_0_1px_#fffaed2d] sm:p-8">
                        <button type="button" id="btn-allow-standard-ports" class="w-full rounded-lg bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-[#2b2b28] dark:bg-[#EDEDEC] dark:text-[#1C1C1A] dark:hover:bg-[#dbdbd8]">
                            Alle Standard-Ports freigeben
                        </button>
                        <p class="mt-2 text-xs text-[#706f6c] dark:text-[#A1A09A]">
                            22, 80, 443, 3306, 5432, 8080, 3000, 5000
                        </p>
                    </div>

                    <div id="fw-result" class="hidden rounded-xl p-3 text-sm"></div>
                </aside>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    let firewallIsActive = false;
    const csrfToken = '{{ csrf_token() }}';
    const firewallRoutes = {
        status: '{{ route('server.firewall.status', $server) }}',
        rules: '{{ route('server.firewall.rules', $server) }}',
        allow: '{{ route('server.firewall.allow', $server) }}',
        deny: '{{ route('server.firewall.deny', $server) }}',
        allowStandardPorts: '{{ route('server.firewall.allow-standard-ports', $server) }}',
        install: '{{ route('server.firewall.install', $server) }}',
        destroy: '{{ route('server.firewall.destroy', ['server' => $server, 'rule' => '__RULE__']) }}',
    };

    document.getElementById('btn-refresh-firewall').addEventListener('click', refreshFirewall);
    document.getElementById('btn-install-ufw').addEventListener('click', installUfw);
    document.getElementById('btn-enable').addEventListener('click', event => firewallAction(event.currentTarget.dataset.actionUrl, event.currentTarget.dataset.confirm));
    document.getElementById('btn-disable').addEventListener('click', event => firewallAction(event.currentTarget.dataset.actionUrl, event.currentTarget.dataset.confirm));
    document.querySelectorAll('[data-port-action]').forEach(button => {
        button.addEventListener('click', () => firewallPortAction(button.dataset.portAction));
    });
    document.querySelectorAll('[data-preset-port]').forEach(button => {
        button.addEventListener('click', () => presetAllow(button.dataset.presetPort));
    });
    document.getElementById('btn-allow-standard-ports').addEventListener('click', allowAllPorts);

    function textElement(tag, className, text) {
        const element = document.createElement(tag);
        element.className = className;
        element.textContent = text;

        return element;
    }

    function getProto() {
        const el = document.querySelector('input[name="fw-proto"]:checked');
        return el ? el.value : 'tcp';
    }

    function showResult(msg, success) {
        const el = document.getElementById('fw-result');
        el.className = 'rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.replaceChildren();
        el.appendChild(document.createTextNode(msg));
        if (!success) {
            el.appendChild(window.reportError(msg, 'firewall'));
        }
        el.classList.remove('hidden');
    }

    function showActionResult(msg, success) {
        const el = document.getElementById('fw-action-result');
        el.className = 'mt-4 rounded-xl p-3 text-sm ' + (success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
        el.replaceChildren();
        el.appendChild(document.createTextNode(msg));
        if (!success) {
            el.appendChild(window.reportError(msg, 'firewall'));
        }
        el.classList.remove('hidden');
    }

    function setButtonsLoading(loading) {
        document.querySelectorAll('#fw-content button').forEach(b => {
            if (!b.id || (b.id !== 'btn-enable' && b.id !== 'btn-disable')) return;
            b.disabled = loading;
            b.style.opacity = loading ? '0.5' : '';
            b.style.cursor = loading ? 'wait' : '';
        });
    }

    function refreshFirewall() {
        const loading = document.getElementById('fw-loading');
        const content = document.getElementById('fw-content');
        const installOverlay = document.getElementById('fw-install-overlay');

        loading.classList.remove('hidden');
        content.classList.add('hidden');
        installOverlay.classList.add('hidden');

        fetch(firewallRoutes.status)
            .then(r => r.json())
            .then(data => {
                loading.classList.add('hidden');

                if (!data.success) {
                    loading.textContent = 'Fehler: ' + (data.error || 'Unbekannter Fehler');
                    loading.classList.remove('hidden');
                    return;
                }

                if (!data.installed) {
                    installOverlay.classList.remove('hidden');
                    return;
                }

                content.classList.remove('hidden');
                renderStatus(data.active);
                loadRules();
            })
            .catch(err => {
                loading.textContent = 'Verbindungsfehler: ' + err.message;
                loading.classList.remove('hidden');
            });
    }

    function renderStatus(active) {
        firewallIsActive = active;

        const badge = document.getElementById('fw-status-badge');
        const enableBtn = document.getElementById('btn-enable');
        const disableBtn = document.getElementById('btn-disable');

        if (active) {
            badge.textContent = 'Aktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            enableBtn.style.display = 'none';
            disableBtn.style.display = '';
        } else {
            badge.textContent = 'Inaktiv';
            badge.className = 'rounded-full px-2.5 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            enableBtn.style.display = '';
            disableBtn.style.display = 'none';
        }
    }

    function loadRules() {
        fetch(firewallRoutes.rules).then(r => r.json())
            .then(data => {
                const empty = document.getElementById('fw-rules-empty');
                const table = document.getElementById('fw-rules-table');
                const tbody = document.getElementById('fw-rules-tbody');

                if (!data.success || !data.rules || data.rules.length === 0) {
                    empty.textContent = firewallIsActive
                        ? 'Keine Firewall-Regeln vorhanden.'
                        : 'Keine sichtbaren Regeln vorhanden. UFW ist inaktiv; bereits angelegte Regeln werden erst nach dem Aktivieren angezeigt.';
                    empty.classList.remove('hidden');
                    table.classList.add('hidden');
                    return;
                }

                empty.classList.add('hidden');
                tbody.replaceChildren();
                for (const rule of data.rules) {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-[#19140020] dark:border-[#3E3E3A]';

                    const actionCell = document.createElement('td');
                    actionCell.className = 'px-3 py-2';
                    actionCell.appendChild(textElement('span', rule.action === 'ALLOW' ? 'rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200' : 'rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200', rule.action || ''));

                    const deleteCell = document.createElement('td');
                    deleteCell.className = 'px-3 py-2';
                    const deleteButton = textElement('button', 'text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200', 'Löschen');
                    deleteButton.type = 'button';
                    deleteButton.addEventListener('click', () => deleteRule(rule.number, rule.port || ''));
                    deleteCell.appendChild(deleteButton);

                    tr.append(
                        textElement('td', 'px-3 py-2', rule.number),
                        actionCell,
                        textElement('td', 'px-3 py-2', rule.port || ''),
                        textElement('td', 'px-3 py-2', rule.protocol || '-'),
                        textElement('td', 'px-3 py-2', rule.source || ''),
                        deleteCell,
                    );
                    tbody.appendChild(tr);
                }
                table.classList.remove('hidden');
            })
            .catch(err => {
                showActionResult('Fehler beim Laden der Regeln: ' + err.message, false);
            });
    }

    function firewallAction(url, confirmMsg) {
        if (confirmMsg && !confirm(confirmMsg)) return;
        setButtonsLoading(true);
        showActionResult('Führe Befehl aus...', true);

        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } })
            .then(r => r.json())
            .then(data => {
                setButtonsLoading(false);
                showActionResult(data.message, data.success);
                if (data.success) setTimeout(refreshFirewall, 1000);
            })
            .catch(err => {
                setButtonsLoading(false);
                showActionResult('Fehler: ' + err.message, false);
            });
    }

    function firewallPortAction(action) {
        const port = document.getElementById('fw-port-input').value.trim();
        if (!port) { showResult('Bitte einen Port eingeben.', false); return; }
        const proto = getProto();

        if (action === 'deny') {
            let warn = '';
            if (port === '22') {
                warn = 'Du blockierst den Standard-SSH-Port (22).\nWenn du ihn noch nutzt, kann die Verbindung zum Server abbrechen.\n\n';
            }
            if (!confirm(warn + 'Port ' + port + ' wirklich blocken?')) return;
        }

        showResult('Führe Befehl aus...', true);

        const url = action === 'allow'
            ? firewallRoutes.allow
            : firewallRoutes.deny;

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ port: port, protocol: proto }),
        })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                document.getElementById('fw-port-input').value = '';
                if (data.success) setTimeout(refreshFirewall, 1000);
            })
            .catch(err => {
                showResult('Fehler: ' + err.message, false);
            });
    }

    function deleteRule(number, port) {
        if (!confirm('Regel ' + number + ' (Port ' + port + ') wirklich löschen?')) return;
        showActionResult('Lösche Regel...', true);

        fetch(firewallRoutes.destroy.replace('__RULE__', encodeURIComponent(number)), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        })
            .then(r => r.json())
            .then(data => {
                showActionResult(data.message, data.success);
                if (data.success) setTimeout(refreshFirewall, 1000);
            })
            .catch(err => {
                showActionResult('Fehler: ' + err.message, false);
            });
    }

    function presetAllow(port) {
        const el = document.getElementById('fw-preset-result');
        el.className = 'mt-3 rounded-xl p-3 text-sm bg-[#19140008] dark:bg-[#fffaed08]';
        el.textContent = 'Öffne Port ' + port + '...';
        el.classList.remove('hidden');

        fetch(firewallRoutes.allow, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ port: port, protocol: 'tcp' }),
        })
            .then(r => r.json())
            .then(data => {
                el.className = 'mt-3 rounded-xl p-3 text-sm ' + (data.success ? 'bg-green-50 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200');
                el.textContent = data.message;
                if (data.success) setTimeout(refreshFirewall, 1000);
            })
            .catch(err => {
                el.className = 'mt-3 rounded-xl p-3 text-sm bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200';
                el.textContent = 'Fehler: ' + err.message;
            });
    }

    function allowAllPorts() {
        if (!confirm('Folgende Ports werden freigegeben:\n22 (SSH), 80 (HTTP), 443 (HTTPS), 3306 (MySQL),\n5432 (PostgreSQL), 8080, 3000, 5000\n\nFortfahren?')) return;
        showResult('Öffne Standard-Ports...', true);

        fetch(firewallRoutes.allowStandardPorts, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
            .then(r => r.json())
            .then(data => {
                showResult(data.message, data.success);
                if (data.success) setTimeout(refreshFirewall, 1000);
            })
            .catch(err => {
                showResult('Fehler: ' + err.message, false);
            });
    }

    function installUfw() {
        const btn = document.getElementById('btn-install-ufw');
        const result = document.getElementById('fw-install-result');
        btn.disabled = true;
        btn.textContent = 'Installiere...';
        result.className = 'mt-4 rounded-xl bg-[#19140008] p-3 text-sm dark:bg-[#fffaed08]';
        result.textContent = 'UFW wird installiert. Bitte warten...';
        result.classList.remove('hidden');

        fetch(firewallRoutes.install, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    result.className = 'mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-200';
                    result.textContent = 'UFW wurde installiert.';
                    window.showToast('UFW wurde installiert.', 'success');
                    setTimeout(refreshFirewall, 1500);
                } else {
                    result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                    result.textContent = data.message;
                    btn.disabled = false;
                    btn.textContent = 'UFW installieren';
                }
            })
            .catch(err => {
                result.className = 'mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200';
                result.textContent = 'Fehler: ' + err.message;
                btn.disabled = false;
                btn.textContent = 'UFW installieren';
            });
    }

    refreshFirewall();
    </script>
    @endpush
</x-layouts.app>
